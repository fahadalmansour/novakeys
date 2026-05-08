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
		// Fire before redirect_canonical (priority 10 on template_redirect)
		// so WP's "rewrite ?author=N to /author/<slug>/" never runs.
		add_action( 'template_redirect',  array( __CLASS__, 'block_author_query' ), 1 );
		// Belt-and-braces: also short-circuit on `parse_request` for any
		// route that bypasses template_redirect (REST, feed, etc.).
		add_action( 'parse_request',      array( __CLASS__, 'block_author_in_query' ) );
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
		if ( ! isset( $_GET['author'] ) && ! isset( $_GET['author_name'] ) ) {
			return;
		}
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	/**
	 * Strip `author` from the parsed query at the earliest possible
	 * point, so even REST/feed routes that bypass template_redirect
	 * don't run an author lookup.
	 *
	 * @since 0.2.5
	 * @param \WP $wp WP environment object.
	 * @return void
	 */
	public static function block_author_in_query( $wp ): void {
		if ( is_admin() ) {
			return;
		}
		if ( isset( $wp->query_vars['author'] ) ) {
			unset( $wp->query_vars['author'] );
		}
		if ( isset( $wp->query_vars['author_name'] ) ) {
			unset( $wp->query_vars['author_name'] );
		}
	}
}
