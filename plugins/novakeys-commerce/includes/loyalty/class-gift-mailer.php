<?php
/**
 * Gift email + WhatsApp dispatcher.
 *
 * Fires on `woocommerce_order_status_completed`. Reads `_gift_phone` /
 * `_gift_email` line-item meta and posts a gift notice to the recipient.
 *
 * Audit-4 fix: the order note previously interpolated `$gift_phone`
 * directly into HTML, opening a stored-XSS vector if any path that
 * writes the meta failed to sanitise. Both `$gift_phone` and the
 * computed `$wa_link` are now `esc_html()` / `esc_url()` before
 * concatenation.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Loyalty;

defined( 'ABSPATH' ) || exit;

/**
 * Gift email/WhatsApp mailer.
 *
 * @since 0.1.0
 */
final class Gift_Mailer {

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ), 25 );
	}

	/**
	 * Iterate line items and dispatch any gifts.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function on_order_completed( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$gift_phone = (string) $item->get_meta( '_gift_phone' );
			$gift_email = (string) $item->get_meta( '_gift_email' );
			if ( '' === $gift_phone && '' === $gift_email ) {
				continue;
			}

			$product = $item->get_product();
			$pname   = $product ? $product->get_name() : 'منتج NovaKeys';
			$sender  = $order->get_billing_first_name() ?: 'صديقك';
			$msg     = "هدية من {$sender}!\n\n{$pname}\n\nاشترِ الآن من NovaKeys.store";

			if ( '' !== $gift_email && is_email( $gift_email ) ) {
				wp_mail( $gift_email, "هدية من {$sender} — NovaKeys", $msg );
			}

			if ( '' !== $gift_phone ) {
				$digits  = preg_replace( '/\D/', '', '966' . ltrim( $gift_phone, '0' ) );
				$wa_link = 'https://wa.me/' . $digits . '?text=' . rawurlencode( $msg );

				$order->add_order_note(
					sprintf(
						/* translators: 1: gift recipient phone (escaped). 2: WhatsApp deep link (escaped URL). */
						'هدية للجوال %1$s — <a href="%2$s" target="_blank" rel="noopener">إرسال WhatsApp</a>',
						esc_html( $gift_phone ),
						esc_url( $wa_link )
					)
				);
			}
		}
	}
}

Gift_Mailer::register();
