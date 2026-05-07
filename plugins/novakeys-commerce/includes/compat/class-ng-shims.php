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

/* -- site / theme-data ----------------------------------------------- */

if ( ! function_exists( 'ng_cr' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_cr()}.
	 *
	 * @return array<string, mixed>
	 */
	function ng_cr(): array {
		return function_exists( 'nk_cr' ) ? nk_cr() : array();
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

/* -- seo module ------------------------------------------------------ */

if ( ! function_exists( 'ng_home_description_ar' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_home_description_ar()}.
	 *
	 * @return string
	 */
	function ng_home_description_ar(): string {
		return function_exists( 'nk_home_description_ar' ) ? nk_home_description_ar() : '';
	}
}

if ( ! function_exists( 'ng_home_title_ar' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_home_title_ar()}.
	 *
	 * @return string
	 */
	function ng_home_title_ar(): string {
		return function_exists( 'nk_home_title_ar' ) ? nk_home_title_ar() : '';
	}
}

if ( ! function_exists( 'ng_seo_rewrite_legacy_host' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_seo_rewrite_legacy_host()}.
	 *
	 * @param mixed $value Value to rewrite.
	 * @return mixed
	 */
	function ng_seo_rewrite_legacy_host( $value ) {
		return function_exists( 'nk_seo_rewrite_legacy_host' ) ? nk_seo_rewrite_legacy_host( $value ) : $value;
	}
}

if ( ! function_exists( 'ng_og_image_url' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_og_image_url()}.
	 *
	 * @return string
	 */
	function ng_og_image_url(): string {
		return function_exists( 'nk_og_image_url' ) ? nk_og_image_url() : '';
	}
}

if ( ! function_exists( 'ng_twitter_image_url' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_twitter_image_url()}.
	 *
	 * @return string
	 */
	function ng_twitter_image_url(): string {
		return function_exists( 'nk_twitter_image_url' ) ? nk_twitter_image_url() : '';
	}
}

/* -- gift-card key vault -------------------------------------------- */

if ( ! function_exists( 'ng_gck_key' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gck_key()}.
	 *
	 * @return string
	 */
	function ng_gck_key(): string {
		return function_exists( 'nk_gck_key' ) ? nk_gck_key() : '';
	}
}

if ( ! function_exists( 'ng_gck_encrypt' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gck_encrypt()}.
	 *
	 * @param string $plain Plaintext.
	 * @return string
	 */
	function ng_gck_encrypt( $plain ): string {
		return function_exists( 'nk_gck_encrypt' ) ? nk_gck_encrypt( $plain ) : (string) $plain;
	}
}

if ( ! function_exists( 'ng_gck_decrypt' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gck_decrypt()}.
	 *
	 * @param string $cipher Cipher envelope.
	 * @return string
	 */
	function ng_gck_decrypt( $cipher ): string {
		return function_exists( 'nk_gck_decrypt' ) ? nk_gck_decrypt( $cipher ) : (string) $cipher;
	}
}

if ( ! function_exists( 'ng_gift_card_set_code' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_set_code()}.
	 *
	 * @param int                  $order_id Order ID.
	 * @param int                  $item_id  Item ID.
	 * @param string               $code     Plaintext code.
	 * @param array<string, mixed> $extras   Extras.
	 * @return bool
	 */
	function ng_gift_card_set_code( $order_id, $item_id, $code, $extras = array() ): bool {
		return function_exists( 'nk_gift_card_set_code' ) ? nk_gift_card_set_code( $order_id, $item_id, $code, $extras ) : false;
	}
}

if ( ! function_exists( 'ng_get_gift_card_keys' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_get_gift_card_keys()}.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	function ng_get_gift_card_keys( $user_id = 0 ): array {
		return function_exists( 'nk_get_gift_card_keys' ) ? nk_get_gift_card_keys( $user_id ) : array();
	}
}

/* -- gift-cards matcher --------------------------------------------- */

if ( ! function_exists( 'ng_gift_card_asset_map' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_asset_map()}.
	 *
	 * @return array<string, array{files: string[], keywords: string[]}>
	 */
	function ng_gift_card_asset_map(): array {
		return function_exists( 'nk_gift_card_asset_map' ) ? nk_gift_card_asset_map() : array();
	}
}

if ( ! function_exists( 'ng_gift_card_asset_dir' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_asset_dir()}.
	 *
	 * @return string
	 */
	function ng_gift_card_asset_dir(): string {
		return function_exists( 'nk_gift_card_asset_dir' ) ? nk_gift_card_asset_dir() : '';
	}
}

if ( ! function_exists( 'ng_gift_card_asset_url_base' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_asset_url_base()}.
	 *
	 * @return string
	 */
	function ng_gift_card_asset_url_base(): string {
		return function_exists( 'nk_gift_card_asset_url_base' ) ? nk_gift_card_asset_url_base() : '';
	}
}

if ( ! function_exists( 'ng_gift_card_existing_file' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_existing_file()}.
	 *
	 * @param array<string, mixed> $asset Asset entry.
	 * @return string
	 */
	function ng_gift_card_existing_file( array $asset ): string {
		return function_exists( 'nk_gift_card_existing_file' ) ? nk_gift_card_existing_file( $asset ) : '';
	}
}

if ( ! function_exists( 'ng_gift_card_parent_product' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_parent_product()}.
	 *
	 * @param mixed $product Product.
	 * @return mixed
	 */
	function ng_gift_card_parent_product( $product ) {
		return function_exists( 'nk_gift_card_parent_product' ) ? nk_gift_card_parent_product( $product ) : null;
	}
}

if ( ! function_exists( 'ng_gift_card_normalize_match_text' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_normalize_match_text()}.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function ng_gift_card_normalize_match_text( $text ): string {
		return function_exists( 'nk_gift_card_normalize_match_text' ) ? nk_gift_card_normalize_match_text( $text ) : (string) $text;
	}
}

if ( ! function_exists( 'ng_gift_card_match_text' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_match_text()}.
	 *
	 * @param mixed $product Product.
	 * @param mixed $parent  Parent product.
	 * @return string
	 */
	function ng_gift_card_match_text( $product, $parent = null ): string {
		return function_exists( 'nk_gift_card_match_text' ) ? nk_gift_card_match_text( $product, $parent ) : '';
	}
}

if ( ! function_exists( 'ng_gift_card_is_candidate_product' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_is_candidate_product()}.
	 *
	 * @param mixed $product Product.
	 * @param mixed $parent  Parent product.
	 * @return bool
	 */
	function ng_gift_card_is_candidate_product( $product, $parent = null ): bool {
		return function_exists( 'nk_gift_card_is_candidate_product' ) ? nk_gift_card_is_candidate_product( $product, $parent ) : false;
	}
}

if ( ! function_exists( 'ng_gift_card_asset_for_product' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_asset_for_product()}.
	 *
	 * @param mixed $product Product.
	 * @param mixed $parent  Parent product.
	 * @return array<string, mixed>|null
	 */
	function ng_gift_card_asset_for_product( $product, $parent = null ): ?array {
		return function_exists( 'nk_gift_card_asset_for_product' ) ? nk_gift_card_asset_for_product( $product, $parent ) : null;
	}
}

if ( ! function_exists( 'ng_gift_card_image_url' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_image_url()}.
	 *
	 * @param mixed $product Product.
	 * @param mixed $parent  Parent product.
	 * @return string
	 */
	function ng_gift_card_image_url( $product, $parent = null ): string {
		return function_exists( 'nk_gift_card_image_url' ) ? nk_gift_card_image_url( $product, $parent ) : '';
	}
}

if ( ! function_exists( 'ng_gift_card_image_html' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_image_html()}.
	 *
	 * @param mixed                $product Product.
	 * @param string               $size    Image size keyword.
	 * @param string               $alt     Alt text.
	 * @param mixed                $parent  Parent product.
	 * @param array<string, mixed> $attr    HTML attrs.
	 * @return string
	 */
	function ng_gift_card_image_html( $product, $size = 'woocommerce_thumbnail', $alt = '', $parent = null, $attr = array() ): string {
		return function_exists( 'nk_gift_card_image_html' ) ? nk_gift_card_image_html( $product, $size, $alt, $parent, $attr ) : '';
	}
}

if ( ! function_exists( 'ng_gift_card_clean_product_name' ) ) {
	/**
	 * @deprecated 0.1.0 Use {@see nk_gift_card_clean_product_name()}.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function ng_gift_card_clean_product_name( $text ): string {
		return function_exists( 'nk_gift_card_clean_product_name' ) ? nk_gift_card_clean_product_name( $text ) : (string) $text;
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
