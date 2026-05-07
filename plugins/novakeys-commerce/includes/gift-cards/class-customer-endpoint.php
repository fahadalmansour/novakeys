<?php
/**
 * Customer-facing My Account → Gift Card Keys endpoint.
 *
 * URL: `/my-account/gift-card-keys/` (slug fixed — public URL contract).
 * Renders the user's purchased gift-card codes with status pills,
 * copy-to-clipboard, and bilingual EN/AR labels.
 *
 * Security: gated by `is_user_logged_in()`; the underlying read in
 * {@see Store::get_keys_for_user()} filters by `customer_id` so users
 * cannot see other people's codes. All dynamic output passes through
 * `esc_html()` / `esc_attr()` / `esc_url()`.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NK_GCK_ENDPOINT' ) ) {
	define( 'NK_GCK_ENDPOINT', 'gift-card-keys' );
}
if ( ! defined( 'NG_GCK_ENDPOINT' ) ) {
	define( 'NG_GCK_ENDPOINT', NK_GCK_ENDPOINT ); // Legacy alias.
}

/**
 * Customer endpoint.
 *
 * @since 0.1.0
 */
final class Customer_Endpoint {

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( function_exists( 'ng_get_gift_card_keys' ) ) {
			return; // Legacy mu-plugin still loaded — avoid double-registration.
		}
		add_action( 'init', array( __CLASS__, 'register_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'register_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ) );
		add_filter( 'woocommerce_endpoint_' . NK_GCK_ENDPOINT . '_title', array( __CLASS__, 'endpoint_title' ) );
		add_action( 'woocommerce_account_' . NK_GCK_ENDPOINT . '_endpoint', array( __CLASS__, 'render' ) );
	}

	/**
	 * Register the rewrite endpoint and flush rewrites once.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_endpoint(): void {
		add_rewrite_endpoint( NK_GCK_ENDPOINT, EP_ROOT | EP_PAGES );
		if ( '1' !== get_option( 'nk_gck_endpoint_v1' ) && '1' !== get_option( 'ng_gck_endpoint_v1' ) ) {
			flush_rewrite_rules( false );
			update_option( 'nk_gck_endpoint_v1', '1', false );
		}
	}

	/**
	 * Add the endpoint to WC's whitelisted query vars.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $vars Existing WC query vars.
	 * @return array<string, string>
	 */
	public static function register_query_var( $vars ): array {
		$vars[ NK_GCK_ENDPOINT ] = NK_GCK_ENDPOINT;
		return (array) $vars;
	}

	/**
	 * Insert the endpoint into the My Account menu (after Orders).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $items Existing menu items.
	 * @return array<string, string>
	 */
	public static function add_menu_item( $items ): array {
		$is_ar = function_exists( 'is_rtl' ) && is_rtl();
		$label = $is_ar ? 'بطاقاتي' : __( 'Gift Card Keys', 'novakeys-commerce' );
		$new   = array();
		foreach ( (array) $items as $key => $val ) {
			$new[ $key ] = $val;
			if ( 'orders' === $key ) {
				$new[ NK_GCK_ENDPOINT ] = $label;
			}
		}
		if ( ! isset( $new[ NK_GCK_ENDPOINT ] ) ) {
			$new[ NK_GCK_ENDPOINT ] = $label;
		}
		return $new;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	public static function endpoint_title(): string {
		return ( function_exists( 'is_rtl' ) && is_rtl() ) ? 'بطاقاتي' : __( 'Gift Card Keys', 'novakeys-commerce' );
	}

	/**
	 * Render the endpoint body.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render(): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Please log in to view your gift-card keys.', 'novakeys-commerce' ) . '</p>';
			return;
		}

		$is_ar = function_exists( 'is_rtl' ) && is_rtl();
		$L     = self::labels( $is_ar );
		$keys  = Store::get_keys_for_user( get_current_user_id() );

		echo '<p>' . esc_html( $L['intro'] ) . '</p>';
		if ( empty( $keys ) ) {
			echo '<p><em>' . esc_html( $L['empty'] ) . '</em></p>';
			return;
		}

		echo self::styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS literal, no user input.
		?>
		<div class="ng-gck-list"<?php echo $is_ar ? ' dir="rtl"' : ''; ?>>
			<?php foreach ( $keys as $k ) : ?>
				<?php
				$status_key = $k['is_expired'] ? 'expired' : $k['status'];
				$pill_label = $L[ $status_key ] ?? ucfirst( (string) $status_key );
				$expires    = $k['expires_at'] ? date_i18n( get_option( 'date_format' ), $k['expires_at'] ) : $L['never'];
				$can_show   = $k['has_code']
					&& in_array( $k['status'], array( 'active', 'consumed' ), true )
					&& ! $k['is_expired'];
				?>
				<div class="ng-gck-card">
					<div class="ng-gck-row">
						<span><strong><?php echo esc_html( $L['order'] ); ?>:</strong> <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'view-order' ) . $k['order_id'] ); ?>">#<?php echo esc_html( (string) $k['order_number'] ); ?></a></span>
						<span><strong><?php echo esc_html( $L['product'] ); ?>:</strong> <?php echo esc_html( (string) $k['product_name'] ); ?></span>
						<?php if ( '' !== (string) $k['brand'] ) : ?>
							<span><strong><?php echo esc_html( $L['brand'] ); ?>:</strong> <?php echo esc_html( (string) $k['brand'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== (string) $k['region'] ) : ?>
							<span><strong><?php echo esc_html( $L['region'] ); ?>:</strong> <?php echo esc_html( (string) $k['region'] ); ?></span>
						<?php endif; ?>
						<span><strong><?php echo esc_html( $L['status'] ); ?>:</strong> <span class="ng-gck-pill is-<?php echo esc_attr( (string) $status_key ); ?>"><?php echo esc_html( (string) $pill_label ); ?></span></span>
						<?php if ( $k['expires_at'] ) : ?>
							<span><strong><?php echo esc_html( $L['expires'] ); ?>:</strong> <?php echo esc_html( (string) $expires ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $can_show ) : ?>
						<div class="ng-gck-code">
							<code><?php echo esc_html( (string) $k['code'] ); ?></code>
							<button type="button" class="ng-gck-copy" data-code="<?php echo esc_attr( (string) $k['code'] ); ?>"><?php echo esc_html( $L['copy'] ); ?></button>
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
				var orig = btn.innerHTML;
				btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true" style="width:14px;height:14px;display:inline-block;vertical-align:middle"><path d="m5 12 5 5L20 7"/></svg>';
				setTimeout(function(){ btn.innerHTML = orig; }, 1200);
			});
		});
		</script>
		<?php
	}

	/**
	 * Bilingual label set.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_ar Whether the request is RTL/Arabic.
	 * @return array<string, string>
	 */
	private static function labels( bool $is_ar ): array {
		if ( $is_ar ) {
			return array(
				'intro'   => 'تظهر هنا الأكواد الخاصة ببطاقات الهدايا التي اشتريتها. تُسلَّم الأكواد بعد تأكيد الدفع.',
				'empty'   => 'لا توجد لديك بطاقات هدايا بعد. تصفح المتجر لإضافة منتجات إلى سلتك.',
				'pending' => 'قيد التحضير',
				'expired' => 'منتهية الصلاحية',
				'revoked' => 'ملغاة',
				'order'   => 'الطلب',
				'product' => 'المنتج',
				'brand'   => 'العلامة',
				'region'  => 'المنطقة',
				'code'    => 'الكود',
				'status'  => 'الحالة',
				'expires' => 'تنتهي في',
				'never'   => '—',
				'copy'    => 'انسخ الكود',
			);
		}
		return array(
			'intro'   => __( 'Codes for any gift cards you have purchased appear here. Codes are released once payment is confirmed.', 'novakeys-commerce' ),
			'empty'   => __( 'No gift cards yet. Browse the store to add some.', 'novakeys-commerce' ),
			'pending' => __( 'Pending', 'novakeys-commerce' ),
			'expired' => __( 'Expired', 'novakeys-commerce' ),
			'revoked' => __( 'Revoked', 'novakeys-commerce' ),
			'order'   => __( 'Order', 'novakeys-commerce' ),
			'product' => __( 'Product', 'novakeys-commerce' ),
			'brand'   => __( 'Brand', 'novakeys-commerce' ),
			'region'  => __( 'Region', 'novakeys-commerce' ),
			'code'    => __( 'Code', 'novakeys-commerce' ),
			'status'  => __( 'Status', 'novakeys-commerce' ),
			'expires' => __( 'Expires', 'novakeys-commerce' ),
			'never'   => '—',
			'copy'    => __( 'Copy code', 'novakeys-commerce' ),
		);
	}

	/**
	 * Inline `<style>` block. Static CSS — no user input. Kept inline
	 * during the phase-2 transition; phase 3 moves these tokens into
	 * theme.json.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	private static function styles(): string {
		return <<<CSS
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
CSS;
	}
}

Customer_Endpoint::register();
