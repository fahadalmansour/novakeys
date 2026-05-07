<?php
/**
 * Refund / cancellation revoker for gift-card codes.
 *
 * Audit-3 fix: previously only `woocommerce_order_status_changed` was
 * wired. Partial refunds via the WC refund API don't change order
 * status, so issued codes stayed `active` after a refund — refund-then-
 * redeem was possible. This class adds `woocommerce_order_refunded` so
 * partial refunds also flip statuses to `revoked`.
 *
 * Revocation policy: the encrypted ciphertext is preserved (audit
 * trail); only `_ng_gift_card_status` flips to `revoked`. Customers see
 * the line as struck-through in their account.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Refund revoker.
 *
 * @since 0.1.0
 */
final class Refund_Revoker {

	/**
	 * Order statuses that trigger revocation.
	 *
	 * @since 0.1.0
	 * @var string[]
	 */
	private const TERMINAL_STATUSES = array( 'refunded', 'cancelled', 'failed' );

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( has_action( 'woocommerce_order_status_changed', 'ng_gck_refund_revoker' ) ) {
			return; // Legacy mu-plugin's named callback still bound; bail.
		}
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status_changed' ), 10, 3 );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'on_order_refunded' ), 10, 2 );
	}

	/**
	 * Status-change handler. Revokes when the new status is in the
	 * terminal set.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $order_id    Order ID.
	 * @param string $old_status  Previous status.
	 * @param string $new_status  New status.
	 * @return void
	 */
	public static function on_status_changed( $order_id, $old_status, $new_status ): void {
		if ( ! in_array( (string) $new_status, self::TERMINAL_STATUSES, true ) ) {
			return;
		}
		self::revoke(
			(int) $order_id,
			sprintf(
				/* translators: 1: old order status. 2: new order status. */
				esc_html__( 'Gift-card codes revoked because order status changed from %1$s to %2$s.', 'novakeys-commerce' ),
				(string) $old_status,
				(string) $new_status
			)
		);
	}

	/**
	 * Refund-API handler. Fires on partial or full refunds via the WC
	 * refund flow without an accompanying status change. Audit-3 fix.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund post ID.
	 * @return void
	 */
	public static function on_order_refunded( $order_id, $refund_id ): void {
		self::revoke(
			(int) $order_id,
			sprintf(
				/* translators: %d: refund post ID. */
				esc_html__( 'Gift-card codes revoked because a refund (#%d) was issued against this order.', 'novakeys-commerce' ),
				(int) $refund_id
			)
		);
	}

	/**
	 * Iterate the order line items and flip any still-active gift-card
	 * code to `revoked`. Idempotent: items already revoked are skipped.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $order_id Order ID.
	 * @param string $note     Order note text (already escaped by caller).
	 * @return void
	 */
	private static function revoke( int $order_id, string $note ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$flipped_any = false;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			if ( '' === (string) $item->get_meta( '_ng_gift_card_code', true ) ) {
				continue;
			}
			if ( 'revoked' === (string) $item->get_meta( '_ng_gift_card_status', true ) ) {
				continue;
			}
			$item->update_meta_data( '_ng_gift_card_status', 'revoked' );
			$item->save_meta_data();
			$flipped_any = true;
		}

		if ( $flipped_any ) {
			$order->add_order_note( $note );
		}
	}
}

Refund_Revoker::register();
