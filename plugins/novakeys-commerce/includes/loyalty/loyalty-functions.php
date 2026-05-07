<?php
/**
 * Procedural wrappers for the loyalty module.
 *
 * Templates and external code call these by name; the underlying logic
 * lives in `class-points.php` under the same dir.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nk_get_points' ) ) {
	/**
	 * Get a user's NK Points balance.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return int
	 */
	function nk_get_points( int $user_id = 0 ): int {
		return \NovaKeys\Commerce\Loyalty\Points::get( $user_id );
	}
}

if ( ! function_exists( 'nk_add_points' ) ) {
	/**
	 * Add NK Points to a user's balance.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Points to add.
	 * @param string $reason  Reason (Arabic accepted).
	 * @return void
	 */
	function nk_add_points( int $user_id, int $points, string $reason = '' ): void {
		\NovaKeys\Commerce\Loyalty\Points::add( $user_id, $points, $reason );
	}
}
