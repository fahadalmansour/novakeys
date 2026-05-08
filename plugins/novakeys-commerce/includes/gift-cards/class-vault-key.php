<?php
/**
 * Vault key resolver, generator, and rotation log.
 *
 * Bridges Vault::encrypt()/decrypt() to a versioned `enc:v3:` envelope
 * whose key lives in a dedicated wp_option (NOT in wp_salt). Rotating
 * the vault key now no longer logs every user out, and the key can be
 * back-up-and-restored independently of wp-config.php.
 *
 * Backward compatibility:
 *   - `enc:v1:` (CTR) and `enc:v2:` (GCM) records continue to decrypt
 *     under the wp_salt('logged_in')-derived key path in Vault.
 *   - `enc:v3:` records decrypt under Vault_Key::current_key(), with a
 *     fallback to Vault_Key::previous_key() during a rewrap window so
 *     reads still work while the rewrap CLI is in flight.
 *   - Lazy init: on the first encrypt() call after this code ships,
 *     `nk_vault_key_v3_current` is created with random_bytes(32). No
 *     manual `wp nk vault init` step required.
 *
 * Storage:
 *   - `nk_vault_key_v3_current`  — base64 of 32 random bytes (autoload=false)
 *   - `nk_vault_key_v3_previous` — base64 of the prior 32 bytes during
 *     an in-progress rotation; deleted after the rewrap commits.
 *   - `nk_vault_rotation_log`    — JSON-encoded array of rotation events.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.3.1
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Versioned vault key resolver.
 *
 * @since 0.3.1
 */
final class Vault_Key {

	/**
	 * Option holding the active 32-byte key (base64).
	 *
	 * @since 0.3.1
	 * @var string
	 */
	public const OPT_CURRENT = 'nk_vault_key_v3_current';

	/**
	 * Option holding the previous 32-byte key during a rewrap window
	 * (base64). Absent outside rotation.
	 *
	 * @since 0.3.1
	 * @var string
	 */
	public const OPT_PREVIOUS = 'nk_vault_key_v3_previous';

	/**
	 * Option holding the rotation audit log (array of records).
	 *
	 * @since 0.3.1
	 * @var string
	 */
	public const OPT_LOG = 'nk_vault_rotation_log';

	/**
	 * Key length in bytes (AES-256).
	 *
	 * @since 0.3.1
	 * @var int
	 */
	public const KEY_LEN = 32;

	/**
	 * Get the active 32-byte vault key. Lazy-initialises on first call.
	 *
	 * @since 0.3.1
	 * @return string Raw 32-byte key, or '' on irrecoverable error.
	 */
	public static function current_key(): string {
		$raw = self::read( self::OPT_CURRENT );
		if ( '' !== $raw ) {
			return $raw;
		}
		// First-time init.
		try {
			$bytes = random_bytes( self::KEY_LEN );
		} catch ( \Throwable $e ) {
			return '';
		}
		update_option( self::OPT_CURRENT, base64_encode( $bytes ), false );
		return $bytes;
	}

	/**
	 * Get the previous 32-byte key (set only during an in-flight
	 * rewrap window). Returns null when no rotation is pending.
	 *
	 * @since 0.3.1
	 * @return string|null
	 */
	public static function previous_key(): ?string {
		$raw = self::read( self::OPT_PREVIOUS );
		return '' === $raw ? null : $raw;
	}

	/**
	 * Generate a new key, move current → previous, store new as
	 * current. Returns the rotation_id (a sortable string ID for
	 * the audit log).
	 *
	 * Caller (Vault_CLI::rotate) must run the rewrap immediately
	 * after this; until rewrap commits, decrypt() will need the
	 * previous key to read existing v3 records.
	 *
	 * @since 0.3.1
	 * @return string Rotation ID, or '' on failure.
	 */
	public static function rotate_to_new(): string {
		$current = self::current_key();
		if ( '' === $current ) {
			return '';
		}
		try {
			$bytes = random_bytes( self::KEY_LEN );
		} catch ( \Throwable $e ) {
			return '';
		}
		update_option( self::OPT_PREVIOUS, base64_encode( $current ), false );
		update_option( self::OPT_CURRENT,  base64_encode( $bytes   ), false );
		return self::next_rotation_id();
	}

	/**
	 * Drop the previous-key slot. Call only after a clean rewrap
	 * confirms zero records remain encrypted under the previous key.
	 *
	 * @since 0.3.1
	 * @return void
	 */
	public static function commit_rotation(): void {
		delete_option( self::OPT_PREVIOUS );
	}

	/**
	 * Whether a rotation is in progress (previous key still set).
	 *
	 * @since 0.3.1
	 * @return bool
	 */
	public static function rotation_pending(): bool {
		return null !== self::previous_key();
	}

	/**
	 * Sha256 fingerprint (first 16 hex chars) of the active key.
	 * For audit/status display only — never the raw key.
	 *
	 * @since 0.3.1
	 * @return string
	 */
	public static function current_fingerprint(): string {
		$raw = self::current_key();
		if ( '' === $raw ) {
			return '';
		}
		return substr( hash( 'sha256', $raw ), 0, 16 );
	}

	/**
	 * Append an entry to the rotation log.
	 *
	 * @since 0.3.1
	 * @param array<string, mixed> $entry Event record.
	 * @return void
	 */
	public static function log_event( array $entry ): void {
		$log = (array) get_option( self::OPT_LOG, array() );
		// Cap log at 50 most-recent rotations. Rotations are rare
		// (months apart in practice); 50 is a few years of history.
		$log = array_slice( $log, -49 );
		$log[] = $entry;
		update_option( self::OPT_LOG, $log, false );
	}

	/**
	 * Read all log entries.
	 *
	 * @since 0.3.1
	 * @return array<int, array<string, mixed>>
	 */
	public static function read_log(): array {
		$log = get_option( self::OPT_LOG, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Decode a base64-stored option to raw bytes. Returns ''
	 * (empty string) when the option is missing or invalid.
	 *
	 * @since 0.3.1
	 * @param string $option_name wp_options name.
	 * @return string
	 */
	private static function read( string $option_name ): string {
		$encoded = (string) get_option( $option_name, '' );
		if ( '' === $encoded ) {
			return '';
		}
		$raw = base64_decode( $encoded, true );
		if ( false === $raw || self::KEY_LEN !== strlen( $raw ) ) {
			return '';
		}
		return $raw;
	}

	/**
	 * Generate a sortable rotation ID — `rot_<UTC ISO 8601>`.
	 *
	 * @since 0.3.1
	 * @return string
	 */
	private static function next_rotation_id(): string {
		return 'rot_' . gmdate( 'Y-m-d\TH-i-s\Z' );
	}
}
