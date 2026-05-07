<?php
/**
 * NK Points loyalty engine.
 *
 * - Awards points on `woocommerce_order_status_completed` (10 pts/SAR,
 *   2× for `nk_is_premium` users). Idempotent per-order via
 *   `_nk_points_awarded` meta.
 * - Welcome bonus (`NK_WELCOME_POINTS`) on `user_register`.
 * - Premium membership flag flipped when an order ships SKU
 *   `NK-MEMBER-25` — adds 100 bonus points.
 * - Provides `Points::get()` / `Points::add()` static API; the
 *   procedural wrappers `nk_get_points()` / `nk_add_points()` live in
 *   `loyalty-functions.php`.
 *
 * Postmeta `_nk_points_awarded` is now written via WC CRUD
 * (`$order->update_meta_data() → save()`) so it remains HPOS-compatible.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Loyalty;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NK_POINTS_PER_SAR' ) ) {
	define( 'NK_POINTS_PER_SAR', 10 );
}
if ( ! defined( 'NK_WELCOME_POINTS' ) ) {
	define( 'NK_WELCOME_POINTS', 50 );
}
if ( ! defined( 'NK_REFERRAL_POINTS' ) ) {
	define( 'NK_REFERRAL_POINTS', 250 );
}

/**
 * NK Points engine.
 *
 * @since 0.1.0
 */
final class Points {

	/**
	 * Wire hooks. Idempotent — guards against re-registration when the
	 * legacy mu-plugin is still loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( function_exists( 'ng_nk_points_award' ) || defined( 'NK_LOYALTY_LEGACY_LOADED' ) ) {
			return;
		}
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'award_for_order' ) );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'maybe_upgrade_to_premium' ), 20 );
		add_action( 'user_register', array( __CLASS__, 'award_on_registration' ) );
	}

	/**
	 * Get a user's current points balance.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return int
	 */
	public static function get( int $user_id = 0 ): int {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}
		return (int) get_user_meta( $user_id, 'nk_points', true );
	}

	/**
	 * Add points to a user's balance and append a log entry.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Points to add (negative is allowed).
	 * @param string $reason  Human-readable reason (Arabic accepted).
	 * @return void
	 */
	public static function add( int $user_id, int $points, string $reason = '' ): void {
		if ( $user_id <= 0 ) {
			return;
		}
		$current = self::get( $user_id );
		update_user_meta( $user_id, 'nk_points', $current + $points );

		$log = get_user_meta( $user_id, 'nk_points_log', true );
		$log = is_array( $log ) ? $log : array();
		$log[] = array(
			'pts'    => $points,
			'reason' => $reason,
			'time'   => time(),
		);
		update_user_meta( $user_id, 'nk_points_log', array_slice( $log, -50 ) );
	}

	/**
	 * Award points when an order is marked completed.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function award_for_order( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$user_id = (int) $order->get_user_id();
		if ( $user_id <= 0 ) {
			return;
		}
		// Idempotent — don't double-award. WC CRUD read.
		if ( '' !== (string) $order->get_meta( '_nk_points_awarded', true ) ) {
			return;
		}

		$total  = (float) $order->get_total();
		$points = (int) floor( $total * NK_POINTS_PER_SAR );
		if ( get_user_meta( $user_id, 'nk_is_premium', true ) ) {
			$points *= 2;
		}
		if ( $points <= 0 ) {
			return;
		}

		self::add( $user_id, $points, 'طلب #' . (int) $order_id );
		$order->update_meta_data( '_nk_points_awarded', 1 );
		$order->save();
	}

	/**
	 * Welcome bonus + referral credit on user registration.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id Newly-registered user ID.
	 * @return void
	 */
	public static function award_on_registration( $user_id ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		self::add( $user_id, NK_WELCOME_POINTS, 'مكافأة الترحيب' );

		$ref_code = isset( $_COOKIE['nk_ref'] ) ? sanitize_key( wp_unslash( $_COOKIE['nk_ref'] ) ) : '';
		if ( '' === $ref_code || ! preg_match( '/^u\d+$/', $ref_code ) ) {
			return;
		}
		$referrer_id = (int) substr( $ref_code, 1 );
		if ( $referrer_id <= 0 || $referrer_id === $user_id || ! get_userdata( $referrer_id ) ) {
			return;
		}
		self::add( $referrer_id, NK_REFERRAL_POINTS, 'إحالة مستخدم جديد' );
		update_user_meta( $user_id, 'nk_referred_by', $referrer_id );
	}

	/**
	 * Flip the premium flag when an order containing SKU `NK-MEMBER-25`
	 * completes; awards a small bonus.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function maybe_upgrade_to_premium( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$user_id = (int) $order->get_user_id();
		if ( $user_id <= 0 ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			if ( 'NK-MEMBER-25' === $product->get_sku() ) {
				update_user_meta( $user_id, 'nk_is_premium', 1 );
				self::add( $user_id, 100, 'ترقية Premium' );
				return; // One bonus per order.
			}
		}
	}
}

Points::register();
