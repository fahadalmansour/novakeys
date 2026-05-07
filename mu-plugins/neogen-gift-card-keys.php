<?php
/**
 * Plugin Name: NeoGen Gift Card Keys
 * Description: Per-order-item gift-card code storage with at-rest encryption (AES-256-CTR over a key derived from wp_salt('logged_in')). Operators populate codes via the order-edit screen metabox; customers retrieve them via the My Account → Gift Card Keys endpoint registered below.
 * Version: 1.38.0
 * Author: Fahad Almansour
 *
 * Source: /tmp/neogen-design/neogen-store/project/account.jsx (Gift Card Keys tab, lines 163–240).
 *
 * Per-line-item meta:
 *   _ng_gift_card_code        string (encrypted base64)
 *   _ng_gift_card_status      pending | active | consumed
 *   _ng_gift_card_expires_at  int (UNIX, optional)
 *   _ng_gift_card_brand       string (Apple, Spotify, Steam, …)
 *   _ng_gift_card_region      string (KSA, UAE, US, UK, …)
 */

defined('ABSPATH') || exit;

/**
 * Derive a 32-byte key from WP salts. Stable across requests, scoped
 * to the site so a stolen DB without the salts can't decrypt codes.
 */
function ng_gck_key() {
    return hash( 'sha256', wp_salt( 'logged_in' ) . '|ng-gift-card-key|v1', true );
}

function ng_gck_encrypt( $plain ) {
    $plain = (string) $plain;
    if ( $plain === '' ) { return ''; }
    if ( ! function_exists( 'openssl_encrypt' ) ) { return $plain; } // fail soft
    $iv  = random_bytes( 16 );
    $ct  = openssl_encrypt( $plain, 'aes-256-ctr', ng_gck_key(), OPENSSL_RAW_DATA, $iv );
    if ( $ct === false ) { return $plain; }
    return 'enc:v1:' . base64_encode( $iv . $ct );
}

function ng_gck_decrypt( $cipher ) {
    $cipher = (string) $cipher;
    if ( strpos( $cipher, 'enc:v1:' ) !== 0 ) {
        return $cipher; // legacy plaintext or empty
    }
    if ( ! function_exists( 'openssl_decrypt' ) ) { return ''; }
    $blob = base64_decode( substr( $cipher, 7 ), true );
    if ( $blob === false || strlen( $blob ) < 17 ) { return ''; }
    $iv = substr( $blob, 0, 16 );
    $ct = substr( $blob, 16 );
    $pt = openssl_decrypt( $ct, 'aes-256-ctr', ng_gck_key(), OPENSSL_RAW_DATA, $iv );
    return $pt === false ? '' : $pt;
}

/**
 * Set a gift-card code on a specific order item. Used by admin or by
 * a future fulfilment integration. Encrypts at rest.
 */
function ng_gift_card_set_code( $order_id, $item_id, $code, $extras = [] ) {
    $order = wc_get_order( (int) $order_id );
    if ( ! $order instanceof WC_Order ) { return false; }
    $item = $order->get_item( (int) $item_id );
    if ( ! $item instanceof WC_Order_Item_Product ) { return false; }

    $code = trim( (string) $code );
    if ( $code === '' ) {
        $item->delete_meta_data( '_ng_gift_card_code' );
        $item->delete_meta_data( '_ng_gift_card_status' );
    } else {
        $item->update_meta_data( '_ng_gift_card_code',   ng_gck_encrypt( $code ) );
        $item->update_meta_data( '_ng_gift_card_status', isset( $extras['status'] ) ? sanitize_key( $extras['status'] ) : 'active' );
    }
    if ( isset( $extras['expires_at'] ) ) {
        $item->update_meta_data( '_ng_gift_card_expires_at', (int) $extras['expires_at'] );
    }
    if ( isset( $extras['brand'] ) ) {
        $item->update_meta_data( '_ng_gift_card_brand', sanitize_text_field( $extras['brand'] ) );
    }
    if ( isset( $extras['region'] ) ) {
        $item->update_meta_data( '_ng_gift_card_region', sanitize_text_field( $extras['region'] ) );
    }
    $item->save_meta_data();
    return true;
}

/**
 * Return all gift-card keys for a user across their orders. Decrypts
 * codes on read. Suitable for the My Account "بطاقاتي" tab.
 */
function ng_get_gift_card_keys( $user_id = 0 ) {
    $user_id = (int) ( $user_id ?: get_current_user_id() );
    if ( ! $user_id ) { return []; }
    if ( ! function_exists( 'wc_get_orders' ) ) { return []; }
    $orders = wc_get_orders( [
        'customer_id' => $user_id,
        'status'      => [ 'completed', 'processing' ],
        'limit'       => 50,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ] );
    if ( empty( $orders ) ) { return []; }

    $now  = current_time( 'timestamp', true );
    $keys = [];
    foreach ( $orders as $order ) {
        if ( ! $order instanceof WC_Order ) { continue; }
        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
            $cipher = (string) $item->get_meta( '_ng_gift_card_code', true );
            $product = $item->get_product();
            $is_gc   = false;
            if ( $product instanceof WC_Product ) {
                if ( function_exists( 'ng_gift_card_is_candidate_product' ) ) {
                    $is_gc = (bool) ng_gift_card_is_candidate_product( $product );
                } else {
                    $cats = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'slugs' ] );
                    $is_gc = ! is_wp_error( $cats ) && in_array( 'gift-cards', $cats, true );
                }
            }
            if ( ! $is_gc && $cipher === '' ) { continue; }
            $expires_at = (int) $item->get_meta( '_ng_gift_card_expires_at', true );
            $keys[] = [
                'order_id'      => $order->get_id(),
                'order_number'  => $order->get_order_number(),
                'item_id'       => $item_id,
                'product_id'    => $item->get_product_id(),
                'product_name'  => $item->get_name(),
                'product_sku'   => $product ? $product->get_sku() : '',
                'brand'         => (string) $item->get_meta( '_ng_gift_card_brand',  true ),
                'region'        => (string) $item->get_meta( '_ng_gift_card_region', true ),
                'code'          => $cipher === '' ? '' : ng_gck_decrypt( $cipher ),
                'has_code'      => $cipher !== '',
                'status'        => (string) ( $item->get_meta( '_ng_gift_card_status', true ) ?: ( $cipher === '' ? 'pending' : 'active' ) ),
                'expires_at'    => $expires_at,
                'is_expired'    => $expires_at > 0 && $now > $expires_at,
                'purchase_date' => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
            ];
        }
    }
    return $keys;
}

/* ---------------------------------------------------------------------
 * Admin UI — order-edit metabox so operators can paste codes per item.
 * ------------------------------------------------------------------- */

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'ng-order-gift-card-keys',
        'NeoGen — Gift Card Keys',
        'ng_gck_admin_box',
        'shop_order',
        'normal',
        'default'
    );
    // HPOS / new orders screen
    if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' ) ) {
        add_meta_box(
            'ng-order-gift-card-keys',
            'NeoGen — Gift Card Keys',
            'ng_gck_admin_box',
            wc_get_page_screen_id( 'shop-order' ),
            'normal',
            'default'
        );
    }
} );

function ng_gck_admin_box( $post_or_order ) {
    $order = is_a( $post_or_order, 'WP_Post' ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
    if ( ! $order instanceof WC_Order ) { echo '<p>—</p>'; return; }
    wp_nonce_field( 'ng_gck_save_' . $order->get_id(), 'ng_gck_nonce' );
    ?>
    <p style="font-size:12px;color:#666;margin:0 0 10px;">Gift-card codes are encrypted at rest. Customers see the (decrypted) code under <em>My Account → Gift Card Keys</em> (<code>/my-account/gift-card-keys/</code>) once the order is <code>processing</code> or <code>completed</code>.</p>
    <table class="widefat striped">
      <thead>
        <tr>
          <th style="width:32%;">Item</th>
          <th>Code</th>
          <th style="width:120px;">Status</th>
          <th style="width:140px;">Expires (YYYY-MM-DD)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) :
            if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
            $cipher  = (string) $item->get_meta( '_ng_gift_card_code', true );
            $code    = $cipher === '' ? '' : ng_gck_decrypt( $cipher );
            $status  = (string) ( $item->get_meta( '_ng_gift_card_status', true ) ?: 'pending' );
            $expires = (int)    $item->get_meta( '_ng_gift_card_expires_at', true );
            $exp_str = $expires ? gmdate( 'Y-m-d', $expires ) : '';
        ?>
          <tr>
            <td><code style="font-size:11px;"><?php echo esc_html( $item->get_name() ); ?></code></td>
            <td><input type="text" name="ng_gck[<?php echo (int) $item_id; ?>][code]" value="<?php echo esc_attr( $code ); ?>" style="width:100%;font-family:monospace;" placeholder="XXXX-XXXX-XXXX-XXXX"></td>
            <td>
              <select name="ng_gck[<?php echo (int) $item_id; ?>][status]" style="width:100%;">
                <?php foreach ( [ 'pending', 'active', 'consumed' ] as $s ) : ?>
                  <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( $s ); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="date" name="ng_gck[<?php echo (int) $item_id; ?>][expires]" value="<?php echo esc_attr( $exp_str ); ?>" style="width:100%;"></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
}

add_action( 'woocommerce_process_shop_order_meta', function ( $order_id ) {
    if ( ! isset( $_POST['ng_gck_nonce'] )
         || ! wp_verify_nonce( $_POST['ng_gck_nonce'], 'ng_gck_save_' . $order_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_shop_orders' ) ) { return; }
    $rows = isset( $_POST['ng_gck'] ) && is_array( $_POST['ng_gck'] ) ? wp_unslash( $_POST['ng_gck'] ) : [];
    $order = wc_get_order( (int) $order_id );
    if ( ! $order instanceof WC_Order ) { return; }
    foreach ( $rows as $item_id => $row ) {
        $code    = isset( $row['code'] )    ? sanitize_text_field( $row['code'] )    : '';
        $status  = isset( $row['status'] )  ? sanitize_key( $row['status'] )         : 'pending';
        $exp_str = isset( $row['expires'] ) ? sanitize_text_field( $row['expires'] ) : '';
        $exp_ts  = $exp_str ? strtotime( $exp_str . ' 23:59:59 UTC' ) : 0;
        ng_gift_card_set_code(
            $order_id, (int) $item_id, $code,
            [
                'status'     => $status,
                'expires_at' => $exp_ts ?: 0,
            ]
        );
    }
} );

/* ---------------------------------------------------------------------
 * Refund / cancellation: revoke any issued codes so a refunded customer
 * can't keep redeeming. Status flips to 'revoked'; the encrypted code is
 * preserved for audit. Customers see a strikethrough in their account.
 * ------------------------------------------------------------------- */

add_action( 'woocommerce_order_status_changed', function ( $order_id, $old_status, $new_status ) {
    if ( ! in_array( $new_status, [ 'refunded', 'cancelled', 'failed' ], true ) ) {
        return;
    }
    $order = wc_get_order( (int) $order_id );
    if ( ! $order instanceof WC_Order ) { return; }
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
        if ( (string) $item->get_meta( '_ng_gift_card_code', true ) === '' ) { continue; }
        if ( (string) $item->get_meta( '_ng_gift_card_status', true ) === 'revoked' ) { continue; }
        $item->update_meta_data( '_ng_gift_card_status', 'revoked' );
        $item->save_meta_data();
    }
    $order->add_order_note( sprintf(
        'Gift-card codes revoked because order status changed from %s to %s.',
        $old_status, $new_status
    ) );
}, 10, 3 );

/* ---------------------------------------------------------------------
 * Customer-facing My Account → Gift Card Keys endpoint.
 * URL: /my-account/gift-card-keys/
 * ------------------------------------------------------------------- */

const NG_GCK_ENDPOINT = 'gift-card-keys';

add_action( 'init', function () {
    add_rewrite_endpoint( NG_GCK_ENDPOINT, EP_ROOT | EP_PAGES );
    if ( get_option( 'ng_gck_endpoint_v1' ) !== '1' ) {
        flush_rewrite_rules( false );
        update_option( 'ng_gck_endpoint_v1', '1', false );
    }
} );

add_filter( 'woocommerce_get_query_vars', function ( $vars ) {
    $vars[ NG_GCK_ENDPOINT ] = NG_GCK_ENDPOINT;
    return $vars;
} );

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    $is_ar = function_exists( 'is_rtl' ) && is_rtl();
    $label = $is_ar ? 'بطاقاتي' : 'Gift Card Keys';
    $new   = [];
    foreach ( $items as $key => $val ) {
        $new[ $key ] = $val;
        if ( 'orders' === $key ) {
            $new[ NG_GCK_ENDPOINT ] = $label;
        }
    }
    if ( ! isset( $new[ NG_GCK_ENDPOINT ] ) ) {
        $new[ NG_GCK_ENDPOINT ] = $label;
    }
    return $new;
} );

add_filter( 'woocommerce_endpoint_' . NG_GCK_ENDPOINT . '_title', function () {
    return ( function_exists( 'is_rtl' ) && is_rtl() ) ? 'بطاقاتي' : 'Gift Card Keys';
} );

add_action( 'woocommerce_account_' . NG_GCK_ENDPOINT . '_endpoint', function () {
    if ( ! is_user_logged_in() ) {
        echo '<p>' . esc_html__( 'Please log in to view your gift-card keys.', 'novakeys' ) . '</p>';
        return;
    }
    $is_ar = function_exists( 'is_rtl' ) && is_rtl();
    $L = $is_ar ? [
        'intro'    => 'تظهر هنا الأكواد الخاصة ببطاقات الهدايا التي اشتريتها. تُسلَّم الأكواد بعد تأكيد الدفع.',
        'empty'    => 'لا توجد لديك بطاقات هدايا بعد. تصفح المتجر لإضافة منتجات إلى سلتك.',
        'pending'  => 'قيد التحضير',
        'expired'  => 'منتهية الصلاحية',
        'revoked'  => 'ملغاة',
        'order'    => 'الطلب',
        'product'  => 'المنتج',
        'brand'    => 'العلامة',
        'region'   => 'المنطقة',
        'code'     => 'الكود',
        'status'   => 'الحالة',
        'expires'  => 'تنتهي في',
        'never'    => '—',
        'copy'     => 'انسخ الكود',
    ] : [
        'intro'    => 'Codes for any gift cards you have purchased appear here. Codes are released once payment is confirmed.',
        'empty'    => 'No gift cards yet. Browse the store to add some.',
        'pending'  => 'Pending',
        'expired'  => 'Expired',
        'revoked'  => 'Revoked',
        'order'    => 'Order',
        'product'  => 'Product',
        'brand'    => 'Brand',
        'region'   => 'Region',
        'code'     => 'Code',
        'status'   => 'Status',
        'expires'  => 'Expires',
        'never'    => '—',
        'copy'     => 'Copy code',
    ];
    $keys = ng_get_gift_card_keys( get_current_user_id() );
    echo '<p>' . esc_html( $L['intro'] ) . '</p>';
    if ( empty( $keys ) ) {
        echo '<p><em>' . esc_html( $L['empty'] ) . '</em></p>';
        return;
    }
    ?>
    <style>
      .ng-gck-list{display:grid;gap:14px;margin:14px 0 0;}
      .ng-gck-card{border:1px solid #E3DDD4;border-radius:12px;padding:14px 16px;background:#fff;}
      .ng-gck-row{display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:#78746E;margin-bottom:8px;}
      .ng-gck-row strong{color:#181714;font-weight:600;}
      .ng-gck-code{display:flex;gap:8px;align-items:center;background:#FAFAF8;border:1px dashed #C8C2B8;border-radius:8px;padding:10px 12px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:14px;color:#181714;letter-spacing:.04em;}
      .ng-gck-code code{flex:1;background:none;padding:0;color:inherit;font:inherit;word-break:break-all;}
      .ng-gck-copy{background:#181714;color:#fff;border:0;border-radius:6px;padding:6px 12px;font-size:12px;cursor:pointer;font-family:inherit;}
      .ng-gck-copy:hover{background:#38BDF8;}
      .ng-gck-pill{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 9px;border-radius:100px;}
      .ng-gck-pill.is-active{background:#F0FDF4;color:#16A34A;}
      .ng-gck-pill.is-pending{background:#FEF9C3;color:#A16207;}
      .ng-gck-pill.is-consumed{background:#F1F5F9;color:#64748B;}
      .ng-gck-pill.is-revoked,.ng-gck-pill.is-expired{background:#FEE2E2;color:#B91C1C;}
      .ng-gck-pending{font-style:italic;color:#A16207;}
    </style>
    <div class="ng-gck-list"<?php if ( $is_ar ) echo ' dir="rtl"'; ?>>
    <?php foreach ( $keys as $k ) :
        $status_key = $k['is_expired'] ? 'expired' : $k['status'];
        $pill_label = isset( $L[ $status_key ] ) ? $L[ $status_key ] : ucfirst( $status_key );
        $expires    = $k['expires_at'] ? date_i18n( get_option( 'date_format' ), $k['expires_at'] ) : $L['never'];
        $can_show   = $k['has_code']
            && in_array( $k['status'], [ 'active', 'consumed' ], true )
            && ! $k['is_expired'];
    ?>
      <div class="ng-gck-card">
        <div class="ng-gck-row">
          <span><strong><?php echo esc_html( $L['order'] ); ?>:</strong> <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'view-order' ) . $k['order_id'] ); ?>">#<?php echo esc_html( $k['order_number'] ); ?></a></span>
          <span><strong><?php echo esc_html( $L['product'] ); ?>:</strong> <?php echo esc_html( $k['product_name'] ); ?></span>
          <?php if ( $k['brand'] !== '' ) : ?><span><strong><?php echo esc_html( $L['brand'] ); ?>:</strong> <?php echo esc_html( $k['brand'] ); ?></span><?php endif; ?>
          <?php if ( $k['region'] !== '' ) : ?><span><strong><?php echo esc_html( $L['region'] ); ?>:</strong> <?php echo esc_html( $k['region'] ); ?></span><?php endif; ?>
          <span><strong><?php echo esc_html( $L['status'] ); ?>:</strong> <span class="ng-gck-pill is-<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $pill_label ); ?></span></span>
          <?php if ( $k['expires_at'] ) : ?><span><strong><?php echo esc_html( $L['expires'] ); ?>:</strong> <?php echo esc_html( $expires ); ?></span><?php endif; ?>
        </div>
        <?php if ( $can_show ) : ?>
          <div class="ng-gck-code">
            <code><?php echo esc_html( $k['code'] ); ?></code>
            <button type="button" class="ng-gck-copy" data-code="<?php echo esc_attr( $k['code'] ); ?>"><?php echo esc_html( $L['copy'] ); ?></button>
          </div>
        <?php elseif ( ! $k['has_code'] ) : ?>
          <div class="ng-gck-pending"><?php echo esc_html( $L['pending'] ); ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
    <script>
    document.querySelectorAll('.ng-gck-copy').forEach(function(btn){
      btn.addEventListener('click', function(){
        var code = btn.getAttribute('data-code') || '';
        if (!code) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(code);
        } else {
          var ta = document.createElement('textarea');
          ta.value = code; document.body.appendChild(ta); ta.select();
          try { document.execCommand('copy'); } catch(e){}
          document.body.removeChild(ta);
        }
        var orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function(){ btn.textContent = orig; }, 1200);
      });
    });
    </script>
    <?php
} );
