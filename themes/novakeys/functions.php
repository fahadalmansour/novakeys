<?php
/**
 * NovaKeys block theme — functions.
 *
 * Slim by design. The novakeys-commerce companion plugin owns commerce
 * + chrome logic (header/footer markup, gift-card pipeline, loyalty,
 * SEO). This file only:
 *   - registers theme support flags FSE doesn't pick up automatically,
 *   - enqueues the optional app.js shell when present, and
 *   - hides Blocksy's default styles via the WC stylesheet array.
 *
 * The earlier priority-999 `template_include` hijack that swapped
 * `app-shell.php` on every is_home / is_front_page request is gone —
 * FSE templates handle hierarchy now.
 *
 * @package NovaKeys
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme setup — runs after WP loads the theme.
 *
 * @since 0.1.0
 * @return void
 */
function novakeys_theme_setup(): void {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	load_theme_textdomain( 'novakeys', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'novakeys_theme_setup' );

/**
 * Enqueue the theme stylesheet and the optional app.js bundle.
 *
 * @since 0.1.0
 * @return void
 */
function novakeys_theme_enqueue(): void {
	$ver = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'novakeys-style',
		get_stylesheet_uri(),
		array(),
		$ver
	);

	$app_path = get_template_directory() . '/assets/app.js';
	$app_url  = get_template_directory_uri() . '/assets/app.js';
	if ( file_exists( $app_path ) ) {
		wp_enqueue_script(
			'novakeys-app',
			$app_url,
			array(),
			(string) filemtime( $app_path ),
			true
		);
		wp_localize_script(
			'novakeys-app',
			'NK',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'wc/v3/' ) ),
				'storeApiUrl'  => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wc_store_api' ),
				'wpNonce'      => wp_create_nonce( 'wp_rest' ),
				'siteUrl'      => esc_url_raw( home_url() ),
				'isLoggedIn'   => is_user_logged_in(),
				'userId'       => get_current_user_id() ?: null,
				'userPoints'   => function_exists( 'nk_get_points' ) && is_user_logged_in() ? nk_get_points( get_current_user_id() ) : 0,
				'isProduct'    => function_exists( 'is_product' ) && is_product(),
				'productId'    => function_exists( 'is_product' ) && is_product() ? get_the_ID() : null,
				'myAccountUrl' => function_exists( 'wc_get_page_permalink' ) ? esc_url_raw( wc_get_page_permalink( 'myaccount' ) ) : '',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'novakeys_theme_enqueue' );

/**
 * Register theme block patterns shipped under `patterns/`.
 * WP auto-discovers files in `/patterns/` from block themes.
 *
 * @since 0.1.0
 * @return void
 */
function novakeys_theme_register_pattern_categories(): void {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}
	register_block_pattern_category(
		'novakeys',
		array( 'label' => __( 'NovaKeys', 'novakeys' ) )
	);
}
add_action( 'init', 'novakeys_theme_register_pattern_categories' );

/**
 * Disable WooCommerce's default stylesheet bundle.
 *
 * The companion plugin emits its own minimal CSS for product cards,
 * legal pages, gift-card key views, etc. WC's defaults clash with our
 * tokenised colour / spacing scale.
 *
 * @since 0.1.0
 *
 * @param array<string, mixed> $styles WC stylesheet handle map.
 * @return array<string, mixed>
 */
function novakeys_theme_disable_wc_styles( $styles ): array {
	unset( $styles );
	return array();
}
add_filter( 'woocommerce_enqueue_styles', 'novakeys_theme_disable_wc_styles' );
