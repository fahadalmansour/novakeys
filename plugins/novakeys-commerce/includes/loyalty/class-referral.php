<?php
/**
 * Referral cookie + REST endpoint.
 *
 * Handles `?ref=u<ID>` URL parameter on `init` (cookie-set on first
 * visit) and the `GET /wp-json/nk/v1/referral/<code>` REST endpoint.
 *
 * Audit-2 fix: the REST endpoint previously accepted any
 * `[a-z0-9]+` string and set it as a 7-day cookie. Anyone could
 * brute-force or pollute referral chains. Now:
 *   - code must match `^u\d+$` exactly,
 *   - per-IP rate limit (10 calls / minute via transient),
 *   - cookie writes use SameSite=Lax + Secure(when SSL) + HttpOnly.
 *
 * Cookie value `nk_ref` is preserved verbatim — already nk_*, plus
 * the URL contract is set by the live customer chains.
 *
 * @package NovaKeys\Commerce\Loyalty
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Loyalty;

defined( 'ABSPATH' ) || exit;

/**
 * Referral cookie + REST endpoint.
 *
 * @since 0.1.0
 */
final class Referral {

	/**
	 * Cookie name.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const COOKIE = 'nk_ref';

	/**
	 * Cookie TTL in seconds (7 days).
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const TTL = 604800;

	/**
	 * Per-IP rate limit window cap.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const RATE_LIMIT_PER_MINUTE = 10;

	/**
	 * Wire hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( __CLASS__, 'maybe_set_from_url' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );
	}

	/**
	 * `?ref=u<ID>` URL handler. Sets the cookie if not already set.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function maybe_set_from_url(): void {
		if ( empty( $_GET['ref'] ) ) {
			return;
		}
		if ( isset( $_COOKIE[ self::COOKIE ] ) ) {
			return;
		}
		// Audit B5: validate raw input first; sanitize_key() before the
		// regex would strip dashes/letters and turn `u1-2a` into `u12`,
		// silently mutating the referral ID. Regex pass implies the
		// string is already a subset of sanitize_key()'s allowed charset.
		$raw = wp_unslash( (string) $_GET['ref'] );
		if ( ! self::valid_code( $raw ) ) {
			return;
		}
		self::set_cookie( $raw );
	}

	/**
	 * Register the REST route.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_rest(): void {
		register_rest_route(
			'nk/v1',
			'/referral/(?P<code>[a-z0-9]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_handler' ),
				'permission_callback' => '__return_true', // Public by design — sets a cookie.
				'args'                => array(
					'code' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * REST handler: validate format + rate limit + set cookie.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $req REST request.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function rest_handler( \WP_REST_Request $req ) {
		// Audit B5: validate raw against the strict regex BEFORE sanitize_key().
		// Sanitising first would let `u1-2a` mutate to `u12` and pass.
		$code = (string) $req['code'];
		if ( ! self::valid_code( $code ) ) {
			return new \WP_Error(
				'nk_referral_invalid',
				__( 'Invalid referral code format.', 'novakeys-commerce' ),
				array( 'status' => 400 )
			);
		}

		// Per-IP rate limit. Transient survives across requests; window
		// is one minute (`MINUTE_IN_SECONDS`).
		$ip    = self::client_ip();
		$key   = 'nk_referral_throttle_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT_PER_MINUTE ) {
			return new \WP_Error(
				'nk_referral_rate_limited',
				__( 'Too many referral requests from this address. Try again in a minute.', 'novakeys-commerce' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		self::set_cookie( $code );
		return array( 'ok' => true );
	}

	/**
	 * Strict format check — must look like `u<digits>`.
	 *
	 * @since 0.1.0
	 *
	 * @param string $code Code to test.
	 * @return bool
	 */
	private static function valid_code( string $code ): bool {
		return 1 === preg_match( '/^u\d+$/', $code );
	}

	/**
	 * Set the `nk_ref` cookie with HttpOnly + SameSite=Lax + Secure(SSL).
	 *
	 * @since 0.1.0
	 *
	 * @param string $code Validated code.
	 * @return void
	 */
	private static function set_cookie( string $code ): void {
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			self::COOKIE,
			$code,
			array(
				'expires'  => time() + self::TTL,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE[ self::COOKIE ] = $code;
	}

	/**
	 * Best-effort client IP. Handles common reverse-proxy headers but
	 * does not trust them blindly — used only as a rate-limit bucket
	 * key, never for authorisation decisions.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	private static function client_ip(): string {
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return (string) wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		}
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$first     = trim( strtok( $forwarded, ',' ) );
			if ( '' !== $first ) {
				return $first;
			}
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	}
}

Referral::register();
