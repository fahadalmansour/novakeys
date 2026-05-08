<?php
/**
 * User-enumeration shield.
 *
 * Two surfaces leak the admin username on a default WP install:
 *   - `GET /wp-json/wp/v2/users` returns the admin record + gravatar
 *     to anonymous callers, exposing the login slug.
 *   - `GET /?author=1` 301-redirects to `/author/<slug>/`, which
 *     reveals the same slug.
 *
 * This module closes both off for unauthenticated visitors. Logged-in
 * users with `list_users` capability still see `/wp/v2/users` (the
 * block editor's user picker depends on it). The author-redirect is
 * blocked sitewide because the public site doesn't expose author
 * archives — there's no editorial blog with bylines to attribute.
 *
 * @package NovaKeys\Commerce\Security
 * @since   0.2.5
 */

namespace NovaKeys\Commerce\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Block public user enumeration via REST + author query.
 *
 * @since 0.2.5
 */
final class User_Enum_Shield {

	/**
	 * Wire hooks.
	 *
	 * @since 0.2.5
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'rest_endpoints',     array( __CLASS__, 'remove_users_route' ) );
		add_action( 'template_redirect',  array( __CLASS__, 'block_author_query' ) );
	}

	/**
	 * Remove `/wp/v2/users` and `/wp/v2/users/<id>` for guests.
	 *
	 * @since 0.2.5
	 * @param array<string, mixed> $endpoints Registered REST endpoints.
	 * @return array<string, mixed>
	 */
	public static function remove_users_route( $endpoints ) {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}
		if ( ! is_array( $endpoints ) ) {
			return $endpoints;
		}
		$paths = array(
			'/wp/v2/users',
			'/wp/v2/users/(?P<id>[\d]+)',
		);
		foreach ( $paths as $path ) {
			if ( isset( $endpoints[ $path ] ) ) {
				unset( $endpoints[ $path ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Redirect any front-end `?author=` query to the homepage.
	 *
	 * @since 0.2.5
	 * @return void
	 */
	public static function block_author_query(): void {
		if ( is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['author'] ) ) {
			return;
		}
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
