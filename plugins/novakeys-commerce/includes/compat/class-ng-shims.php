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
