<?php
/**
 * Gift-card key admin metabox on the WC order-edit screen.
 *
 * Operators paste codes per line item; values are encrypted at rest by
 * {@see Vault::encrypt()}. Save handler verifies the nonce, capability,
 * and delegates to {@see Store::set_code()}.
 *
 * HPOS-aware: registers the metabox against `wc_get_page_screen_id()`
 * when WC's HPOS data store is loaded.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Order-edit metabox.
 *
 * @since 0.1.0
 */
final class Admin {

	private const METABOX_ID    = 'nk-order-gift-card-keys';
	private const NONCE_ACTION  = 'nk_gck_save_';
	private const NONCE_FIELD   = 'nk_gck_nonce';
	private const ROW_NAME      = 'nk_gck';

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( function_exists( 'ng_gck_admin_box' ) ) {
			return; // Legacy mu-plugin still loaded — avoid double-registration.
		}
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save' ) );
	}

	/**
	 * Register the metabox on both classic and HPOS order screens.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_metabox(): void {
		$title = __( 'NovaKeys — Gift Card Keys', 'novakeys-commerce' );

		add_meta_box(
			self::METABOX_ID,
			$title,
			array( __CLASS__, 'render' ),
			'shop_order',
			'normal',
			'default'
		);
		if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' )
			&& function_exists( 'wc_get_page_screen_id' ) ) {
			add_meta_box(
				self::METABOX_ID,
				$title,
				array( __CLASS__, 'render' ),
				wc_get_page_screen_id( 'shop-order' ),
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render the metabox.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $post_or_order Either WP_Post (classic) or WC_Order (HPOS).
	 * @return void
	 */
	public static function render( $post_or_order ): void {
		$order = is_a( $post_or_order, 'WP_Post' ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof \WC_Order ) {
			echo '<p>—</p>';
			return;
		}
		wp_nonce_field( self::NONCE_ACTION . $order->get_id(), self::NONCE_FIELD );
		?>
		<p style="font-size:12px;color:#666;margin:0 0 10px;">
			<?php
			echo wp_kses(
				__( 'Gift-card codes are encrypted at rest. Customers see the (decrypted) code under <em>My Account → Gift Card Keys</em> (<code>/my-account/gift-card-keys/</code>) once the order is <code>processing</code> or <code>completed</code>.', 'novakeys-commerce' ),
				array( 'em' => array(), 'code' => array() )
			);
			?>
		</p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:32%;"><?php echo esc_html__( 'Item', 'novakeys-commerce' ); ?></th>
					<th><?php echo esc_html__( 'Code', 'novakeys-commerce' ); ?></th>
					<th style="width:120px;"><?php echo esc_html__( 'Status', 'novakeys-commerce' ); ?></th>
					<th style="width:140px;"><?php echo esc_html__( 'Expires (YYYY-MM-DD)', 'novakeys-commerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) :
					if ( ! $item instanceof \WC_Order_Item_Product ) {
						continue;
					}
					$cipher  = (string) $item->get_meta( '_ng_gift_card_code', true );
					$code    = '' === $cipher ? '' : Vault::decrypt( $cipher );
					$status  = (string) ( $item->get_meta( '_ng_gift_card_status', true ) ?: 'pending' );
					$expires = (int) $item->get_meta( '_ng_gift_card_expires_at', true );
					$exp_str = $expires ? gmdate( 'Y-m-d', $expires ) : '';
					$row_id  = (int) $item_id;
					?>
					<tr>
						<td><code style="font-size:11px;"><?php echo esc_html( $item->get_name() ); ?></code></td>
						<td><input type="text" name="<?php echo esc_attr( self::ROW_NAME ); ?>[<?php echo $row_id; ?>][code]" value="<?php echo esc_attr( $code ); ?>" style="width:100%;font-family:monospace;" placeholder="XXXX-XXXX-XXXX-XXXX"></td>
						<td>
							<select name="<?php echo esc_attr( self::ROW_NAME ); ?>[<?php echo $row_id; ?>][status]" style="width:100%;">
								<?php foreach ( array( 'pending', 'active', 'consumed', 'revoked' ) as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( $s ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><input type="date" name="<?php echo esc_attr( self::ROW_NAME ); ?>[<?php echo $row_id; ?>][expires]" value="<?php echo esc_attr( $exp_str ); ?>" style="width:100%;"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save handler — verifies nonce + capability, persists each row.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WC order ID.
	 * @return void
	 */
	public static function save( $order_id ): void {
		$order_id = (int) $order_id;
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION . $order_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$rows = isset( $_POST[ self::ROW_NAME ] ) && is_array( $_POST[ self::ROW_NAME ] )
			? wp_unslash( $_POST[ self::ROW_NAME ] )
			: array();

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		foreach ( $rows as $item_id => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$code    = isset( $row['code'] ) ? sanitize_text_field( (string) $row['code'] ) : '';
			$status  = isset( $row['status'] ) ? sanitize_key( (string) $row['status'] ) : 'pending';
			$exp_str = isset( $row['expires'] ) ? sanitize_text_field( (string) $row['expires'] ) : '';
			$exp_ts  = '' !== $exp_str ? strtotime( $exp_str . ' 23:59:59 UTC' ) : 0;

			Store::set_code(
				$order_id,
				(int) $item_id,
				$code,
				array(
					'status'     => $status,
					'expires_at' => $exp_ts ?: 0,
				)
			);
		}
	}
}

Admin::register();
