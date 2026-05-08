<?php
/**
 * PDPL cookie-consent persistence + admin-post handler.
 *
 * Saudi Personal Data Protection Law (PDPL, Royal Decree M/19) Art. 6
 * requires explicit, informed consent before non-essential personal
 * data is processed. This module is the consent gate: a versioned
 * cookie holds the visitor's per-category decision; getters elsewhere
 * in the plugin (Recommender, Referral) read it before setting their
 * own non-essential cookies.
 *
 * Cookie format (JSON, 1y TTL):
 *
 *   { "v": 1, "necessary": 1, "functional": 0, "marketing": 0, "ts": 1234567890 }
 *
 * - `v` is the schema version. Bumping it invalidates older cookies
 *   so the banner re-prompts on a material policy change.
 * - `necessary` is always 1 (informational; cart/session/security
 *   cookies don't require opt-in).
 * - `functional` gates `ng_recent` (recently-viewed product IDs).
 * - `marketing` gates `nk_ref` (referral attribution).
 * - `ts` is the consent-set timestamp; useful for audit later.
 *
 * Categories are stored as a frozen list. Adding a new one means a
 * version bump so the banner re-prompts (otherwise visitors with old
 * cookies would silently default the new category to off).
 *
 * @package NovaKeys\Commerce\Consent
 * @since   0.3.0
 */

namespace NovaKeys\Commerce\Consent;

defined( 'ABSPATH' ) || exit;

/**
 * PDPL consent state.
 *
 * @since 0.3.0
 */
final class Cookie_Consent {

	/**
	 * Cookie name. Frozen — visitors carry this from prior visits.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const COOKIE = 'nk_cookie_consent';

	/**
	 * TTL in seconds. 1 year matches the longest-lived consented
	 * cookie (`ng_recent` is 30d, `nk_ref` is 7d, but the *consent
	 * decision itself* is durable).
	 *
	 * @since 0.3.0
	 * @var int
	 */
	public const TTL = YEAR_IN_SECONDS;

	/**
	 * Schema version. Bump on a material policy change to force
	 * re-consent.
	 *
	 * @since 0.3.0
	 * @var int
	 */
	public const VERSION = 1;

	/**
	 * Allowed (toggleable) consent categories. `necessary` is always
	 * on and isn't listed here.
	 *
	 * @since 0.3.0
	 * @var array<int, string>
	 */
	public const CATEGORIES = array( 'functional', 'marketing' );

	/**
	 * Wire hooks.
	 *
	 * @since 0.3.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_post_nk_consent_save',        array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_nopriv_nk_consent_save', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Read the visitor's current consent state.
	 *
	 * @since 0.3.0
	 * @return array<string, int>|null Decoded state, or null when no
	 *                                 valid consent cookie is present.
	 */
	public static function read(): ?array {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) ) {
			return null;
		}
		$raw = wp_unslash( (string) $_COOKIE[ self::COOKIE ] );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return null;
		}
		if ( (int) ( $data['v'] ?? 0 ) !== self::VERSION ) {
			return null;
		}
		return array(
			'v'          => self::VERSION,
			'necessary'  => 1,
			'functional' => empty( $data['functional'] ) ? 0 : 1,
			'marketing'  => empty( $data['marketing'] ) ? 0 : 1,
			'ts'         => (int) ( $data['ts'] ?? 0 ),
		);
	}

	/**
	 * Whether the visitor has already made a consent decision.
	 *
	 * @since 0.3.0
	 * @return bool
	 */
	public static function is_set(): bool {
		return null !== self::read();
	}

	/**
	 * Whether a category is consented.
	 *
	 * Returns false if no consent cookie is present yet — fail-closed.
	 *
	 * @since 0.3.0
	 * @param string $category One of self::CATEGORIES, or 'necessary'.
	 * @return bool
	 */
	public static function has( string $category ): bool {
		if ( 'necessary' === $category ) {
			return true;
		}
		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			return false;
		}
		$state = self::read();
		if ( null === $state ) {
			return false;
		}
		return ! empty( $state[ $category ] );
	}

	/**
	 * Persist a consent decision to the cookie.
	 *
	 * @since 0.3.0
	 * @param array<string, mixed> $consents Map of category => truthy.
	 *                                       Unknown keys ignored.
	 * @return void
	 */
	public static function set( array $consents ): void {
		$payload = array(
			'v'          => self::VERSION,
			'necessary'  => 1,
			'functional' => empty( $consents['functional'] ) ? 0 : 1,
			'marketing'  => empty( $consents['marketing'] ) ? 0 : 1,
			'ts'         => time(),
		);
		$value = (string) wp_json_encode( $payload );

		// Set in $_COOKIE too so subsequent reads in the same request
		// see the new state (e.g. the gate in Recommender::set_cookie
		// fired on the same request that processed the consent POST).
		$_COOKIE[ self::COOKIE ] = $value;

		$args = array(
			'expires'  => time() + self::TTL,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => false, // JS reads it for client-side gating.
			'samesite' => 'Lax',
		);
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, $value, $args );
		}
	}

	/**
	 * `admin-post.php?action=nk_consent_save` handler. Wired by both
	 * the form submission inside the manage modal and the
	 * Accept-all / Reject convenience buttons.
	 *
	 * @since 0.3.0
	 * @return void
	 */
	public static function handle_save(): void {
		$back = wp_get_referer();
		if ( ! $back ) {
			$back = home_url( '/' );
		}

		if ( ! isset( $_POST['nk_consent_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nk_consent_nonce'] ) ), 'nk_consent' )
		) {
			wp_safe_redirect( $back );
			exit;
		}

		// Buttons send a `decision` field of 'accept_all' | 'reject' |
		// 'save'. The 'save' path also reads the per-category checkboxes.
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';

		if ( 'accept_all' === $decision ) {
			$consents = array_fill_keys( self::CATEGORIES, 1 );
		} elseif ( 'reject' === $decision ) {
			$consents = array_fill_keys( self::CATEGORIES, 0 );
		} else {
			$consents = array();
			foreach ( self::CATEGORIES as $cat ) {
				$consents[ $cat ] = isset( $_POST[ $cat ] ) ? 1 : 0;
			}
		}

		self::set( $consents );

		/**
		 * Fires after a visitor's consent decision is persisted. ESP
		 * integrations or analytics gates can hook here.
		 *
		 * @since 0.3.0
		 * @param array<string, int> $consents Final decision map.
		 */
		do_action( 'nk_consent_saved', self::read() ?? array() );

		wp_safe_redirect( $back );
		exit;
	}
}

/**
 * Procedural helper so other modules can read consent without a
 * `use` import. Returns false until the visitor decides — fail-closed.
 *
 * @since 0.3.0
 * @param string $category One of 'functional' | 'marketing' | 'necessary'.
 * @return bool
 */
function nk_consent_has( string $category ): bool {
	return Cookie_Consent::has( $category );
}
