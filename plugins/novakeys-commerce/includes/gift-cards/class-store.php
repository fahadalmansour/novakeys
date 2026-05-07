<?php
/**
 * Gift-card key store — read/write per-order-item codes.
 *
 * Per-line-item meta written here (preserved verbatim from the legacy
 * mu-plugin; backs live customer data):
 *   _ng_gift_card_code        string (encrypted envelope)
 *   _ng_gift_card_status      pending | active | consumed | revoked
 *   _ng_gift_card_expires_at  int (UNIX timestamp, optional)
 *   _ng_gift_card_brand       string (Apple, Spotify, Steam, …)
 *   _ng_gift_card_region      string (KSA, UAE, US, UK, …)
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Gift-card key read/write.
 *
 * @since 0.1.0
 */
final class Store {

	/**
	 * Set or clear the gift-card code on a specific order item.
	 *
	 * Encrypts at rest. Empty `$code` deletes the code + status meta.
	 * Returns false if the order or item could not be loaded.
	 *
	 * @since 0.1.0
	 *
	 * @param int                  $order_id WC order ID.
	 * @param int                  $item_id  Order line-item ID.
	 * @param string               $code     Plaintext code, or empty to clear.
	 * @param array<string, mixed> $extras   Optional `status`, `expires_at`, `brand`, `region`.
	 * @return bool
	 */
	public static function set_code( int $order_id, int $item_id, string $code, array $extras = array() ): bool {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}
		$item = $order->get_item( $item_id );
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return false;
		}

		$code = trim( $code );
		if ( '' === $code ) {
			$item->delete_meta_data( '_ng_gift_card_code' );
			$item->delete_meta_data( '_ng_gift_card_status' );
		} else {
			$item->update_meta_data( '_ng_gift_card_code', Vault::encrypt( $code ) );
			$item->update_meta_data( '_ng_gift_card_status', isset( $extras['status'] ) ? sanitize_key( $extras['status'] ) : 'active' );
		}
		if ( isset( $extras['expires_at'] ) ) {
			$item->update_meta_data( '_ng_gift_card_expires_at', (int) $extras['expires_at'] );
		}
		if ( isset( $extras['brand'] ) ) {
			$item->update_meta_data( '_ng_gift_card_brand', sanitize_text_field( (string) $extras['brand'] ) );
		}
		if ( isset( $extras['region'] ) ) {
			$item->update_meta_data( '_ng_gift_card_region', sanitize_text_field( (string) $extras['region'] ) );
		}
		$item->save_meta_data();
		return true;
	}

	/**
	 * Return all gift-card keys for a user across their orders. Decrypts
	 * codes on read. Suitable for the My Account "بطاقاتي" tab.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_keys_for_user( int $user_id = 0 ): array {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'completed', 'processing' ),
				'limit'       => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
		if ( empty( $orders ) ) {
			return array();
		}

		$now  = current_time( 'timestamp', true );
		$keys = array();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}
			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}
				$cipher  = (string) $item->get_meta( '_ng_gift_card_code', true );
				$product = $item->get_product();
				$is_gc   = false;

				if ( $product instanceof \WC_Product ) {
					if ( function_exists( 'nk_gift_card_is_candidate_product' ) ) {
						$is_gc = (bool) nk_gift_card_is_candidate_product( $product );
					} elseif ( function_exists( 'ng_gift_card_is_candidate_product' ) ) {
						$is_gc = (bool) ng_gift_card_is_candidate_product( $product );
					} else {
						$cats  = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
						$is_gc = ! is_wp_error( $cats ) && in_array( 'gift-cards', $cats, true );
					}
				}

				if ( ! $is_gc && '' === $cipher ) {
					continue;
				}

				$expires_at = (int) $item->get_meta( '_ng_gift_card_expires_at', true );
				$keys[]     = array(
					'order_id'      => $order->get_id(),
					'order_number'  => $order->get_order_number(),
					'item_id'       => $item_id,
					'product_id'    => $item->get_product_id(),
					'product_name'  => $item->get_name(),
					'product_sku'   => $product ? $product->get_sku() : '',
					'brand'         => (string) $item->get_meta( '_ng_gift_card_brand', true ),
					'region'        => (string) $item->get_meta( '_ng_gift_card_region', true ),
					'code'          => '' === $cipher ? '' : Vault::decrypt( $cipher ),
					'has_code'      => '' !== $cipher,
					'status'        => (string) ( $item->get_meta( '_ng_gift_card_status', true ) ?: ( '' === $cipher ? 'pending' : 'active' ) ),
					'expires_at'    => $expires_at,
					'is_expired'    => $expires_at > 0 && $now > $expires_at,
					'purchase_date' => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
				);
			}
		}
		return $keys;
	}
}
