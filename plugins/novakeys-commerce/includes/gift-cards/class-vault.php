<?php
/**
 * Encrypted gift-card code vault.
 *
 * Three cipher envelopes are supported:
 *   - `enc:v1:` AES-256-CTR (legacy; malleable, no MAC) — read-only.
 *   - `enc:v2:` AES-256-GCM under wp_salt('logged_in') — read-only after
 *     v3 ships; existing v2 records continue to decrypt while the
 *     rewrap CLI migrates them.
 *   - `enc:v3:` AES-256-GCM under Vault_Key (dedicated wp_option) —
 *     used for ALL new writes after 0.3.1. Rotatable independently of
 *     wp_salt; key lives in `nk_vault_key_v3_current`.
 *
 * Decrypt path for v3 tries the current key first, falls back to the
 * previous key during a rewrap window. Old envelopes (v1, v2) keep
 * deriving from wp_salt forever.
 *
 * Encryption-key isolation:
 *   - Pre-0.3.1: `wp_salt('logged_in')` is the seed (db steal without
 *     wp-config.php = unreadable).
 *   - 0.3.1+:    `nk_vault_key_v3_current` option is the seed (db steal
 *     without the row content = still unreadable; key rotation no
 *     longer logs out users).
 *
 * Audit-5: GCM authentication. CTR-only v1 was forgeable; GCM v2 / v3
 * fail-closed on any tamper.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypted code vault.
 *
 * @since 0.1.0
 */
final class Vault {

	/**
	 * GCM auth tag length in bytes.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const GCM_TAG_LEN = 16;

	/**
	 * IV length in bytes (16 for both CTR and GCM as we use them).
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const IV_LEN = 16;

	/**
	 * Key length in bytes (AES-256).
	 *
	 * @since 0.3.1
	 * @var int
	 */
	private const KEY_LEN = 32;

	/**
	 * Derive the 32-byte AES key from WP salts. Stable per site.
	 *
	 * @since 0.1.0
	 * @return string Raw 32-byte key.
	 */
	public static function key(): string {
		return hash( 'sha256', wp_salt( 'logged_in' ) . '|ng-gift-card-key|v1', true );
	}

	/**
	 * Encrypt plaintext. Returns the cipher envelope or the original
	 * plaintext on fail-soft (when openssl is unavailable).
	 *
	 * Writes `enc:v3:` (GCM under Vault_Key) when the dedicated key
	 * is reachable. Falls back to `enc:v2:` (GCM under wp_salt) when
	 * Vault_Key is unavailable, then to `enc:v1:` (CTR) when GCM
	 * isn't compiled into openssl.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plain Plaintext.
	 * @return string Cipher envelope, plaintext on failure, or empty.
	 */
	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return $plain; // Fail-soft. Caller is admin-trusted; storing plaintext is preferable to losing the value.
		}

		$gcm_available = in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true );

		// Prefer v3 (dedicated key) when the helper class is loaded
		// AND a usable 32-byte key is available. Falls through to v2
		// when key generation fails or the helper isn't loaded.
		if ( $gcm_available && class_exists( __NAMESPACE__ . '\\Vault_Key' ) ) {
			$key = Vault_Key::current_key();
			if ( '' !== $key && self::KEY_LEN === strlen( $key ) ) {
				$iv  = random_bytes( self::IV_LEN );
				$tag = '';
				$ct  = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LEN );
				if ( false !== $ct ) {
					return 'enc:v3:' . base64_encode( $iv . $tag . $ct );
				}
			}
		}

		// v2 / v1 paths use the wp_salt-derived key.
		$key = self::key();

		if ( $gcm_available ) {
			$iv  = random_bytes( self::IV_LEN );
			$tag = '';
			$ct  = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LEN );
			if ( false === $ct ) {
				return $plain;
			}
			return 'enc:v2:' . base64_encode( $iv . $tag . $ct );
		}

		$iv = random_bytes( self::IV_LEN );
		$ct = openssl_encrypt( $plain, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $ct ) {
			return $plain;
		}
		return 'enc:v1:' . base64_encode( $iv . $ct );
	}

	/**
	 * Decrypt a cipher envelope. Recognises both `enc:v1:` (CTR) and
	 * `enc:v2:` (GCM). Returns the decrypted plaintext, or the original
	 * value when no envelope prefix is present (legacy plaintext).
	 * Returns empty on tamper or on missing openssl.
	 *
	 * @since 0.1.0
	 *
	 * @param string $cipher Cipher envelope or legacy plaintext.
	 * @return string
	 */
	public static function decrypt( string $cipher ): string {
		if ( 0 === strpos( $cipher, 'enc:v3:' ) ) {
			return self::decrypt_v3( substr( $cipher, 7 ) );
		}
		if ( 0 === strpos( $cipher, 'enc:v2:' ) ) {
			return self::decrypt_v2( substr( $cipher, 7 ) );
		}
		if ( 0 === strpos( $cipher, 'enc:v1:' ) ) {
			return self::decrypt_v1( substr( $cipher, 7 ) );
		}
		return $cipher; // Legacy plaintext or empty.
	}

	/**
	 * Decrypt a v3 (GCM under Vault_Key) payload. Tries the current
	 * key first; falls back to the previous key during a rotation
	 * window so reads succeed mid-rewrap. Returns empty on auth
	 * failure under both keys.
	 *
	 * @since 0.3.1
	 *
	 * @param string $payload Base64 of IV(16) + tag(16) + ciphertext.
	 * @return string
	 */
	private static function decrypt_v3( string $payload ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		if ( ! class_exists( __NAMESPACE__ . '\\Vault_Key' ) ) {
			return '';
		}
		$blob   = base64_decode( $payload, true );
		$header = self::IV_LEN + self::GCM_TAG_LEN;
		if ( false === $blob || strlen( $blob ) <= $header ) {
			return '';
		}
		$iv  = substr( $blob, 0, self::IV_LEN );
		$tag = substr( $blob, self::IV_LEN, self::GCM_TAG_LEN );
		$ct  = substr( $blob, $header );

		$cur_key = Vault_Key::current_key();
		if ( '' !== $cur_key ) {
			$pt = openssl_decrypt( $ct, 'aes-256-gcm', $cur_key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false !== $pt ) {
				return $pt;
			}
		}

		// Auth failed under current — try previous (rewrap window).
		$prev_key = Vault_Key::previous_key();
		if ( null !== $prev_key ) {
			$pt = openssl_decrypt( $ct, 'aes-256-gcm', $prev_key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false !== $pt ) {
				return $pt;
			}
		}

		return '';
	}

	/**
	 * Decrypt a v1 (CTR) payload.
	 *
	 * @since 0.1.0
	 *
	 * @param string $payload Base64 of IV(16) + ciphertext.
	 * @return string
	 */
	private static function decrypt_v1( string $payload ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$blob = base64_decode( $payload, true );
		if ( false === $blob || strlen( $blob ) <= self::IV_LEN ) {
			return '';
		}
		$iv = substr( $blob, 0, self::IV_LEN );
		$ct = substr( $blob, self::IV_LEN );
		$pt = openssl_decrypt( $ct, 'aes-256-ctr', self::key(), OPENSSL_RAW_DATA, $iv );
		return false === $pt ? '' : $pt;
	}

	/**
	 * Decrypt a v2 (GCM) payload. Returns empty on auth failure.
	 *
	 * @since 0.1.0
	 *
	 * @param string $payload Base64 of IV(16) + tag(16) + ciphertext.
	 * @return string
	 */
	private static function decrypt_v2( string $payload ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$blob   = base64_decode( $payload, true );
		$header = self::IV_LEN + self::GCM_TAG_LEN;
		if ( false === $blob || strlen( $blob ) <= $header ) {
			return '';
		}
		$iv  = substr( $blob, 0, self::IV_LEN );
		$tag = substr( $blob, self::IV_LEN, self::GCM_TAG_LEN );
		$ct  = substr( $blob, $header );
		$pt  = openssl_decrypt( $ct, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag );
		return false === $pt ? '' : $pt;
	}
}
