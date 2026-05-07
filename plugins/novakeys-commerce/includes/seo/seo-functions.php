<?php
/**
 * Procedural wrappers for the SEO module.
 *
 * Templates and external code call these by name; the underlying logic
 * lives in the class implementations under the same directory.
 *
 * @package NovaKeys\Commerce\SEO
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nk_home_description_ar' ) ) {
	/**
	 * Canonical homepage AR description (~145 visual chars).
	 *
	 * @since 0.1.0
	 * @return string
	 */
	function nk_home_description_ar(): string {
		return \NovaKeys\Commerce\SEO\Rank_Math_Bridge::home_description_ar();
	}
}

if ( ! function_exists( 'nk_home_title_ar' ) ) {
	/**
	 * Canonical homepage title.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	function nk_home_title_ar(): string {
		return \NovaKeys\Commerce\SEO\Rank_Math_Bridge::home_title_ar();
	}
}

if ( ! function_exists( 'nk_seo_rewrite_legacy_host' ) ) {
	/**
	 * Recursively rewrite the legacy `ngs1.blazr.net` host.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Value to rewrite.
	 * @return mixed
	 */
	function nk_seo_rewrite_legacy_host( $value ) {
		return \NovaKeys\Commerce\SEO\Legacy_Host_Rewriter::rewrite( $value );
	}
}

if ( ! function_exists( 'nk_og_image_url' ) ) {
	/**
	 * Resolve the OG image URL.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	function nk_og_image_url(): string {
		return \NovaKeys\Commerce\SEO\Rank_Math_Bridge::og_image_url();
	}
}

if ( ! function_exists( 'nk_twitter_image_url' ) ) {
	/**
	 * Resolve the Twitter card image URL.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	function nk_twitter_image_url(): string {
		return \NovaKeys\Commerce\SEO\Rank_Math_Bridge::twitter_image_url();
	}
}
