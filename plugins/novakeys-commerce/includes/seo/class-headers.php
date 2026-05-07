<?php
/**
 * Security headers — CSP, HSTS, X-Frame-Options, Referrer-Policy,
 * Permissions-Policy. CSP runs Report-Only by default; operator flips
 * to enforcement by defining `NK_CSP_ENFORCE` (or legacy `NG_CSP_ENFORCE`)
 * in wp-config.php after a clean reporting window.
 *
 * @package NovaKeys\Commerce\SEO
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Security headers.
 *
 * @since 0.1.0
 */
final class Headers {

	/**
	 * Send all security headers on the public-facing front end.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function send(): void {
		if ( is_admin() || headers_sent() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Permissions-Policy: interest-cohort=(), browsing-topics=()' );
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}

		$csp        = self::build_csp();
		$enforce    = ( defined( 'NK_CSP_ENFORCE' ) && NK_CSP_ENFORCE )
			|| ( defined( 'NG_CSP_ENFORCE' ) && NG_CSP_ENFORCE );
		$csp_header = $enforce
			? 'Content-Security-Policy: ' . $csp
			: 'Content-Security-Policy-Report-Only: ' . $csp;
		header( $csp_header );
	}

	/**
	 * Build the CSP header value.
	 *
	 * Tuned for WooCommerce + KSA payment gateways (Mada / Apple Pay /
	 * STC Pay / Tabby / PayPal / Checkout.com). Filterable via
	 * `nk_csp_directives` so future modules can inject directives.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	private static function build_csp(): string {
		$directives = array(
			"default-src 'self'",
			"base-uri 'self'",
			"object-src 'none'",
			"frame-ancestors 'self'",
			"form-action 'self' https://*.mada.com.sa https://*.checkout.com https://*.tabby.ai https://*.stcpay.com.sa https://*.paypal.com",
			"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.googletagmanager.com https://*.google-analytics.com https://*.googleadservices.com https://*.googlesyndication.com https://*.doubleclick.net https://*.gstatic.com https://*.tabby.ai https://*.checkout.com https://*.stcpay.com.sa https://*.applepay.cdn-apple.com",
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://*.gstatic.com",
			"font-src 'self' data: https://fonts.gstatic.com",
			"img-src 'self' data: blob: https:",
			"connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com https://*.tabby.ai https://*.checkout.com https://*.stcpay.com.sa",
			"frame-src 'self' https://*.youtube.com https://*.youtube-nocookie.com https://*.tabby.ai https://*.checkout.com https://*.stcpay.com.sa https://*.applepay.cdn-apple.com",
			"media-src 'self' blob: https:",
			'upgrade-insecure-requests',
		);

		/**
		 * Filter the CSP directives.
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $directives Directive lines, joined with `; `.
		 */
		$directives = (array) apply_filters( 'nk_csp_directives', $directives );
		return implode( '; ', $directives );
	}
}

add_action( 'send_headers', array( Headers::class, 'send' ) );
