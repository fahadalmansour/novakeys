<?php
/**
 * Backwards-compatibility shims: `ng_*` aliases for `nk_*` symbols.
 *
 * Phase 2 ports each mu-plugin to the new namespace. Until phase 4
 * cleanup, snippets, scripts, and any third-party hooks may still call
 * `ng_*` names. Each shim is `function_exists`-guarded so the file can
 * be safely required even when the underlying `nk_*` function hasn't
 * been registered yet (the call falls through to the original
 * mu-plugin's `ng_*` definition).
 *
 * Add a `ng_*` shim here every time a function moves to `nk_*`.
 *
 * @package NovaKeys\Commerce
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/* -- icons module ----------------------------------------------------- */

if ( ! function_exists( 'ng_icons_catalog' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_icons_catalog()}.
	 *
	 * @return array<string, string>
	 */
	function ng_icons_catalog(): array {
		return function_exists( 'nk_icons_catalog' ) ? nk_icons_catalog() : array();
	}
}

if ( ! function_exists( 'ng_icon' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_icon()}.
	 *
	 * @param string $name        Icon name.
	 * @param int    $size        Icon size in px.
	 * @param string $extra_class Extra CSS class names.
	 * @return string
	 */
	function ng_icon( string $name, int $size = 20, string $extra_class = '' ): string {
		return function_exists( 'nk_icon' ) ? nk_icon( $name, $size, $extra_class ) : '';
	}
}

if ( ! function_exists( 'ng_icon_sprite' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_icon_sprite()}.
	 *
	 * @return string
	 */
	function ng_icon_sprite(): string {
		return function_exists( 'nk_icon_sprite' ) ? nk_icon_sprite() : '';
	}
}

if ( ! function_exists( 'ng_icon_use' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_icon_use()}.
	 *
	 * @param string $name        Icon name.
	 * @param int    $size        Icon size in px.
	 * @param string $extra_class Extra CSS class names.
	 * @return string
	 */
	function ng_icon_use( string $name, int $size = 20, string $extra_class = '' ): string {
		return function_exists( 'nk_icon_use' ) ? nk_icon_use( $name, $size, $extra_class ) : '';
	}
}

/* -- recommendations module ----------------------------------------- */

if ( ! function_exists( 'ng_rec_read_cookie' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_rec_read_cookie()}.
	 *
	 * @return int[]
	 */
	function ng_rec_read_cookie(): array {
		return function_exists( 'nk_rec_read_cookie' ) ? nk_rec_read_cookie() : array();
	}
}

if ( ! function_exists( 'ng_recent_product_ids' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_recent_product_ids()}.
	 *
	 * @param int $exclude Product ID to exclude.
	 * @return int[]
	 */
	function ng_recent_product_ids( int $exclude = 0 ): array {
		return function_exists( 'nk_recent_product_ids' ) ? nk_recent_product_ids( $exclude ) : array();
	}
}

if ( ! function_exists( 'ng_recommended_products' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_recommended_products()}.
	 *
	 * @param int $exclude Product ID to exclude.
	 * @param int $limit   Max items.
	 * @return array
	 */
	function ng_recommended_products( int $exclude = 0, int $limit = 4 ): array {
		return function_exists( 'nk_recommended_products' ) ? nk_recommended_products( $exclude, $limit ) : array();
	}
}

if ( ! function_exists( 'ng_compatibility_note' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_compatibility_note()}.
	 *
	 * @param \WC_Product|null $source Source product.
	 * @param \WC_Product|null $compat Recommended companion.
	 * @return string
	 */
	function ng_compatibility_note( $source = null, $compat = null ): string {
		return function_exists( 'nk_compatibility_note' ) ? nk_compatibility_note( $source, $compat ) : '';
	}
}

if ( ! function_exists( 'ng_render_recommendations' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_render_recommendations()}.
	 *
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	function ng_render_recommendations( array $args = array() ): string {
		return function_exists( 'nk_render_recommendations' ) ? nk_render_recommendations( $args ) : '';
	}
}

/* -- product-meta module --------------------------------------------- */

if ( ! function_exists( 'ng_product_ar_title_meta_box' ) ) {
	/**
	 * @deprecated 0.1.0 The Arabic-title metabox is now class-based; nothing
	 *             outside the plugin should call this. Shim returns void so
	 *             a stale `add_meta_box()` callback registration in legacy
	 *             code resolves harmlessly.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	function ng_product_ar_title_meta_box( \WP_Post $post ): void {
		if ( class_exists( '\\NovaKeys\\Commerce\\Product_Meta\\Arabic_Title' ) ) {
			\NovaKeys\Commerce\Product_Meta\Arabic_Title::instance()->render( $post );
		}
	}
}
