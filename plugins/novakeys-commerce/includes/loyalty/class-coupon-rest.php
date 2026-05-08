<?php
/**
 * Share-to-unlock coupon REST endpoint.
 *
 * `POST /wp-json/nk/v1/coupon` — generates a one-shot 10% discount
 * coupon, valid 24 hours, single-use.
 *
 * Audit-1 fix: the legacy endpoint was unauthenticated with no
 * rate-limit, so an attacker could spawn unbounded coupons. Now:
 *   - login required (`is_user_logged_in()` permission callback),
 *   - per-user 1-coupon-per-24-hour throttle via transient.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Loyalty;

defined( 'ABSPATH' ) || exit;

/**
 * Coupon REST endpoint.
 *
 * @since 0.1.0
 */
final class Coupon_REST {

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );
	}

	/**
	 * Register the REST route.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_rest(): void {
		register_rest_route(
			'nk/v1',
			'/coupon',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_handler' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);
	}

	/**
	 * Permission callback — login required.
	 *
	 * @since 0.1.0
	 * @return bool|\WP_Error
	 */
	public static function permission_check() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'nk_coupon_not_auth',
				__( 'You must be signed in to generate a coupon.', 'novakeys-commerce' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Handler: rate-limit + create a one-shot 10% coupon.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $req REST request.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function rest_handler( \WP_REST_Request $req ) {
		unset( $req );
		$uid = get_current_user_id();
		if ( $uid <= 0 ) {
			return new \WP_Error( 'nk_coupon_not_auth', 'unauth', array( 'status' => 401 ) );
		}

		$throttle_key = 'nk_coupon_throttle_' . $uid;
		if ( get_transient( $throttle_key ) ) {
			return new \WP_Error(
				'nk_coupon_rate_limited',
				__( 'You have already generated a coupon in the last 24 hours. Try again tomorrow.', 'novakeys-commerce' ),
				array( 'status' => 429 )
			);
		}

		$code     = 'NK' . strtoupper( substr( md5( uniqid( '', true ) ), 0, 6 ) );
		$tomorrow = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );

		$coupon_id = wp_insert_post(
			array(
				'post_title'  => $code,
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'post_author' => $uid,
			)
		);
		if ( ! $coupon_id || is_wp_error( $coupon_id ) ) {
			return new \WP_Error(
				'nk_coupon_create_failed',
				__( 'Could not create coupon.', 'novakeys-commerce' ),
				array( 'status' => 500 )
			);
		}

		// WC_Coupon CRUD is the only supported write path. The plugin
		// header declares `Requires Plugins: woocommerce` so WC is
		// always loaded by the time this REST route can fire — the
		// previous post-meta fallback was unreachable defensive code
		// that violated the engineering-standards "WC CRUD over
		// postmeta" rule (readiness-2026-05-08 MEDIUM).
		$coupon = new \WC_Coupon( (int) $coupon_id );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->set_usage_limit( 1 );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->set_date_expires( strtotime( $tomorrow ) );
		$coupon->save();

		set_transient( $throttle_key, 1, DAY_IN_SECONDS );

		return array(
			'code'     => $code,
			'discount' => '10%',
			'expires'  => $tomorrow,
		);
	}
}

Coupon_REST::register();
