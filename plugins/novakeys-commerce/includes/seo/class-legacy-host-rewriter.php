<?php
/**
 * Legacy host rewriter.
 *
 * Rewrites any stale `ngs1.blazr.net` URL emitted by widgets, post
 * content, nav menus, or Rank Math sitemap output to the canonical
 * `novakeys.store` host.
 *
 * @package NovaKeys\Commerce\SEO
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Legacy host rewriter.
 *
 * @since 0.1.0
 */
final class Legacy_Host_Rewriter {

	/**
	 * Register the rewrite filters.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'the_content', array( __CLASS__, 'rewrite' ), 1 );
		add_filter( 'widget_text_content', array( __CLASS__, 'rewrite' ), 1 );
		add_filter( 'widget_text', array( __CLASS__, 'rewrite' ), 1 );
		add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'rewrite' ), 1 );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'fix_menu_items' ), 99 );

		// Rank Math sitemap output — rewrite cached legacy URLs at flight.
		add_filter( 'rank_math/sitemap/build_index', array( __CLASS__, 'rewrite' ), 1 );
		add_filter( 'rank_math/sitemap/output', array( __CLASS__, 'rewrite' ), 1 );
		add_filter(
			'rank_math/sitemap/locations',
			static function ( $locs ) {
				if ( is_array( $locs ) ) {
					return array_map( array( __CLASS__, 'rewrite' ), $locs );
				}
				return self::rewrite( $locs );
			}
		);
	}

	/**
	 * Recursively rewrite `ngs1.blazr.net` to `novakeys.store` in any
	 * string, array, or object with a `url` property.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Value to rewrite.
	 * @return mixed
	 */
	public static function rewrite( $value ) {
		$pattern     = '#https?://(?:www\.)?ngs1\.blazr\.net#i';
		$replacement = 'https://novakeys.store';

		if ( is_string( $value ) ) {
			return preg_replace( $pattern, $replacement, $value );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $i => $item ) {
				if ( is_object( $item ) && isset( $item->url ) ) {
					$value[ $i ]->url = preg_replace( $pattern, $replacement, $item->url );
				} elseif ( is_string( $item ) || is_array( $item ) ) {
					$value[ $i ] = self::rewrite( $item );
				}
			}
		}

		return $value;
	}

	/**
	 * Fix nav-menu items: rewrite host on `url`, fall back to a derived
	 * label when the anchor text is empty (silenced "empty link" SEO
	 * warning).
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, object> $items Nav menu items.
	 * @return array<int, object>
	 */
	public static function fix_menu_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		foreach ( $items as $item ) {
			if ( ! empty( $item->url ) ) {
				$item->url = preg_replace(
					'#https?://(?:www\.)?ngs1\.blazr\.net#i',
					'https://novakeys.store',
					$item->url
				);
			}
			if ( '' === trim( wp_strip_all_tags( (string) $item->title ) ) && ! empty( $item->url ) ) {
				$path  = wp_parse_url( $item->url, PHP_URL_PATH );
				$slug  = trim( (string) $path, '/' );
				$label = '' !== $slug ? ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ) : 'Link';
				$item->classes[]  = 'ng-empty-anchor-fixed';
				$item->attr_title = $label;
				if ( ! isset( $item->aria_label ) || '' === $item->aria_label ) {
					$item->aria_label = $label;
				}
			}
		}
		return $items;
	}
}

Legacy_Host_Rewriter::register();
