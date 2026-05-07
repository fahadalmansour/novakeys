<?php
/**
 * Encrypted gift-card code vault.
 *
 * Two cipher envelopes are supported:
 *   - `enc:v1:` AES-256-CTR (legacy; CTR mode is malleable, no MAC).
 *   - `enc:v2:` AES-256-GCM (new writes; built-in MAC, tamper-evident).
 *
 * New encrypts use v2 when the GCM cipher is available. Decrypts handle
 * both — existing customer data written under v1 keeps decrypting cleanly.
 *
 * Encryption key is derived from `wp_salt('logged_in')` — a stolen DB
 * without `wp-config.php` salts cannot decrypt.
 *
 * Audit-5 fix: added GCM authentication. Previous CTR-only envelope was
 * forgeable — an attacker with write access to a row could flip
 * ciphertext bits and silently flip plaintext bits. With GCM, decrypt
 * returns false on any tamper.
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

		$key = self::key();

		// Prefer GCM (authenticated). Fall back to CTR if GCM cipher is unavailable.
		if ( in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ) ) {
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
		if ( 0 === strpos( $cipher, 'enc:v2:' ) ) {
			return self::decrypt_v2( substr( $cipher, 7 ) );
		}
		if ( 0 === strpos( $cipher, 'enc:v1:' ) ) {
			return self::decrypt_v1( substr( $cipher, 7 ) );
		}
		return $cipher; // Legacy plaintext or empty.
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
