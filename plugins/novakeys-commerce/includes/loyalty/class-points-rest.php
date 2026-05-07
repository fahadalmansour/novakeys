<?php
/**
 * NK Points REST endpoint — `GET /wp-json/nk/v1/points`.
 *
 * Returns the current user's points balance, SAR equivalent, premium
 * flag, and referral code. Login required.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Loyalty;

defined( 'ABSPATH' ) || exit;

/**
 * Points REST endpoint.
 *
 * @since 0.1.0
 */
final class Points_REST {

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
			'/points',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_handler' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}

	/**
	 * Handler.
	 *
	 * @since 0.1.0
	 * @return array<string, mixed>
	 */
	public static function rest_handler(): array {
		$uid = get_current_user_id();
		$pts = Points::get( $uid );
		return array(
			'points'     => $pts,
			'sar'        => round( $pts / 100, 2 ),
			'is_premium' => (bool) get_user_meta( $uid, 'nk_is_premium', true ),
			'ref_code'   => 'u' . $uid,
		);
	}
}

Points_REST::register();
