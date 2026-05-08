<?php
/**
 * NovaKeys block theme — functions.
 *
 * Slim by design. The novakeys-commerce companion plugin owns commerce
 * + chrome logic. This file only:
 *   - registers theme support flags FSE doesn't pick up automatically,
 *   - enqueues the optional app.js shell when present,
 *   - corrects `<html lang>` to match the rendered locale (audit B1),
 *   - conditionally dequeues unused block libraries on guest non-block
 *     pages (audit M7),
 *   - registers block style variations (ghost button, sky duotone),
 *   - hides Blocksy's default WC styles via the WC stylesheet array.
 *
 * Self-hosted fonts (Space Grotesk, Inter, JetBrains Mono, IBM Plex Sans
 * Arabic) are wired via theme.json `fontFace` declarations — WP 6.5+
 * registers and enqueues them automatically from `assets/fonts/`.
 *
 * @package NovaKeys
 * @since   0.2.0
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
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );

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
 * Register theme block pattern category.
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
 * Register block style variations introduced by the new design system.
 *
 * - Button: `is-style-ghost` (transparent fill, Sky border).
 * - Image:  `is-style-duotone-sky` (applies sky-on-ink duotone).
 * - Separator: `is-style-sky-fade` (gradient hairline).
 *
 * @since 0.2.0
 * @return void
 */
function novakeys_theme_register_block_styles(): void {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	register_block_style(
		'core/button',
		array(
			'name'  => 'ghost',
			'label' => __( 'Ghost', 'novakeys' ),
		)
	);

	register_block_style(
		'core/image',
		array(
			'name'  => 'duotone-sky',
			'label' => __( 'Duotone · Sky', 'novakeys' ),
		)
	);

	register_block_style(
		'core/separator',
		array(
			'name'  => 'sky-fade',
			'label' => __( 'Sky Fade', 'novakeys' ),
		)
	);
}
add_action( 'init', 'novakeys_theme_register_block_styles' );

/**
 * Correct the document `<html>` lang attribute when the rendered content
 * is Arabic but WordPress reports `en_US` (TranslatePress and similar
 * multilingual plugins can leave the root attribute mismatched).
 *
 * Closes readiness-report finding B1 (WCAG 3.1.1).
 *
 * @since 0.2.0
 * @param string $output The current `lang="…"` etc. string.
 * @return string
 */
function novakeys_theme_fix_lang_attribute( string $output ): string {
	if ( ! function_exists( 'determine_locale' ) ) {
		return $output;
	}

	$locale = determine_locale();
	if ( '' === $locale ) {
		return $output;
	}

	// Only intervene when the doctype-language string already exists.
	if ( strpos( $output, 'lang=' ) === false ) {
		return $output;
	}

	$short = preg_match( '/^([a-z]{2})(?:[_-]|$)/i', $locale, $matches )
		? strtolower( $matches[1] )
		: 'en';

	// is_rtl() is the source of truth for direction in the active locale.
	$dir = is_rtl() ? 'rtl' : 'ltr';

	$output = preg_replace( '/lang="[^"]*"/', sprintf( 'lang="%s"', esc_attr( $short ) ), $output );
	if ( strpos( $output, 'dir=' ) === false ) {
		$output .= sprintf( ' dir="%s"', esc_attr( $dir ) );
	} else {
		$output = preg_replace( '/dir="[^"]*"/', sprintf( 'dir="%s"', esc_attr( $dir ) ), $output );
	}

	return $output;
}
add_filter( 'language_attributes', 'novakeys_theme_fix_lang_attribute', 99 );

/**
 * Conditionally dequeue the WP block library + WC blocks frontend payload
 * on guest, non-block, non-Woo pages where they aren't needed.
 *
 * Closes readiness-report finding M7. Cuts ~150 KB of JS+CSS for the
 * homepage / blog / contact / static pages that don't render block
 * editor primitives or Woo blocks.
 *
 * Strict guard: never runs in admin, never for logged-in users (so the
 * editor stays intact), and skips on WC pages, on /cart/, /checkout/,
 * /my-account/, single product, archive product, and any post that
 * carries `wp:` block markup.
 *
 * @since 0.2.0
 * @return void
 */
function novakeys_theme_conditional_block_dequeue(): void {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		return;
	}

	$post = get_post();
	if ( $post && false !== strpos( (string) $post->post_content, '<!-- wp:' ) ) {
		// The post uses block markup — keep the library.
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_script( 'wp-block-library' );
}
add_action( 'wp_enqueue_scripts', 'novakeys_theme_conditional_block_dequeue', 100 );

/**
 * Disable WooCommerce's default stylesheet bundle.
 *
 * The companion plugin emits its own minimal CSS for product cards,
 * legal pages, gift-card key views, etc. WC's defaults clash with the
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

/**
 * Add a body-class for AR rendering so patterns can apply Arabic typography
 * scoped via `:lang(ar)` or the body class. Keeps the Arabic font binding
 * out of theme.json (where it would force the family on every body) and
 * into a context-aware rule.
 *
 * @since 0.2.0
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function novakeys_theme_body_class( $classes ): array {
	if ( is_rtl() ) {
		$classes[] = 'nk-locale-ar';
	}
	return $classes;
}
add_filter( 'body_class', 'novakeys_theme_body_class' );
