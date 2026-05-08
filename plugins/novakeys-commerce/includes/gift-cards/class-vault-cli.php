<?php
/**
 * WP-CLI commands for vault key inspection + rotation.
 *
 * Three operator commands:
 *
 *   wp nk vault status
 *     Print the current envelope fingerprint, per-version record
 *     counts, and the last 5 rotation log entries.
 *
 *   wp nk vault rewrap [--batch=<n>] [--source=<v1|v2|all>] [--dry-run]
 *     Migrate existing records forward to `enc:v3:` under the current
 *     key. Idempotent + resumable. Use `--dry-run` to count without
 *     writing.
 *
 *   wp nk vault rotate [--batch=<n>]
 *     Generate a new v3 key, move current → previous, rewrap every
 *     record under the new key, then drop the previous-key slot and
 *     log the rotation. Atomic from a customer perspective: reads
 *     keep working throughout because Vault::decrypt_v3 falls back
 *     to the previous key during the window.
 *
 * Loaded only when running under WP-CLI (the class itself does
 * nothing in a web request).
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.3.1
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Vault rotation CLI.
 *
 * @since 0.3.1
 */
final class Vault_CLI {

	/**
	 * Transient lock name. Prevents concurrent rewrap runs.
	 *
	 * @since 0.3.1
	 * @var string
	 */
	private const LOCK_KEY = 'nk_vault_rewrap_lock';

	/**
	 * Lock TTL in seconds (1 hour). A real rewrap completes in
	 * minutes; the long TTL is just so a crashed rewrap doesn't
	 * pin the lock forever.
	 *
	 * @since 0.3.1
	 * @var int
	 */
	private const LOCK_TTL = HOUR_IN_SECONDS;

	/**
	 * Default batch size.
	 *
	 * @since 0.3.1
	 * @var int
	 */
	private const DEFAULT_BATCH = 200;

	/**
	 * Register the CLI namespace if running under WP-CLI.
	 *
	 * @since 0.3.1
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( 'nk vault status', array( __CLASS__, 'cmd_status' ) );
		\WP_CLI::add_command( 'nk vault rewrap', array( __CLASS__, 'cmd_rewrap' ) );
		\WP_CLI::add_command( 'nk vault rotate', array( __CLASS__, 'cmd_rotate' ) );
	}

	/**
	 * `wp nk vault status` — print envelope fingerprint + counts +
	 * last rotations.
	 *
	 * @since 0.3.1
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Flag args (unused).
	 * @return void
	 */
	public static function cmd_status( $args = array(), $assoc_args = array() ): void {
		unset( $args, $assoc_args );

		\WP_CLI::log( '— NovaKeys Vault Status —' );
		\WP_CLI::log( 'Current key fingerprint (sha256[:16]): ' . Vault_Key::current_fingerprint() );
		\WP_CLI::log( 'Rotation pending:                       ' . ( Vault_Key::rotation_pending() ? 'YES (previous key still set)' : 'no' ) );

		$counts = self::record_counts();
		\WP_CLI::log( '' );
		\WP_CLI::log( 'Records by envelope:' );
		foreach ( $counts as $prefix => $n ) {
			\WP_CLI::log( sprintf( '  %-12s  %d', $prefix, $n ) );
		}

		$log = Vault_Key::read_log();
		if ( ! empty( $log ) ) {
			\WP_CLI::log( '' );
			\WP_CLI::log( 'Recent rotations (last 5):' );
			foreach ( array_slice( $log, -5 ) as $entry ) {
				\WP_CLI::log( sprintf(
					'  %s  %s → %s   total=%d  rewrapped=%d  failed=%d',
					$entry['id'] ?? '?',
					$entry['from_version'] ?? '?',
					$entry['to_version'] ?? '?',
					(int) ( $entry['records_total'] ?? 0 ),
					(int) ( $entry['records_rewrapped'] ?? 0 ),
					(int) ( $entry['records_failed'] ?? 0 )
				) );
			}
		}
	}

	/**
	 * `wp nk vault rewrap [--batch=N] [--source=v1|v2|all] [--dry-run]`
	 * — migrate v1/v2 records forward to v3 under the current key.
	 *
	 * @since 0.3.1
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Flag args.
	 * @return void
	 */
	public static function cmd_rewrap( $args = array(), $assoc_args = array() ): void {
		unset( $args );
		$batch    = isset( $assoc_args['batch'] ) ? max( 10, (int) $assoc_args['batch'] ) : self::DEFAULT_BATCH;
		$source   = isset( $assoc_args['source'] ) ? (string) $assoc_args['source'] : 'all';
		$dry_run  = ! empty( $assoc_args['dry-run'] );

		if ( ! self::lock() ) {
			\WP_CLI::error( 'Another rewrap is already running (lock held). Wait for it to finish or `wp transient delete ' . self::LOCK_KEY . '` if certain.' );
			return;
		}

		try {
			$prefixes = self::resolve_source( $source );
			$total    = array( 'scanned' => 0, 'rewrapped' => 0, 'failed' => 0 );
			foreach ( $prefixes as $prefix ) {
				\WP_CLI::log( sprintf( '→ Rewrapping %s records (batch=%d%s)…', $prefix, $batch, $dry_run ? ', DRY RUN' : '' ) );
				$stats = self::rewrap_loop( $prefix, $batch, $dry_run );
				\WP_CLI::log( sprintf( '  scanned=%d  rewrapped=%d  failed=%d', $stats['scanned'], $stats['rewrapped'], $stats['failed'] ) );
				$total['scanned']   += $stats['scanned'];
				$total['rewrapped'] += $stats['rewrapped'];
				$total['failed']    += $stats['failed'];
			}
			\WP_CLI::success( sprintf(
				'Rewrap complete. Total: scanned=%d  rewrapped=%d  failed=%d.',
				$total['scanned'],
				$total['rewrapped'],
				$total['failed']
			) );
			if ( $total['failed'] > 0 ) {
				\WP_CLI::warning( 'Some records failed to decrypt. They are likely corrupt or written under a salt no longer in wp-config.php. Inspect manually.' );
			}
		} finally {
			self::unlock();
		}
	}

	/**
	 * `wp nk vault rotate [--batch=N]` — generate new key, rewrap
	 * everything to it, commit, log.
	 *
	 * @since 0.3.1
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Flag args.
	 * @return void
	 */
	public static function cmd_rotate( $args = array(), $assoc_args = array() ): void {
		unset( $args );
		$batch = isset( $assoc_args['batch'] ) ? max( 10, (int) $assoc_args['batch'] ) : self::DEFAULT_BATCH;

		if ( Vault_Key::rotation_pending() ) {
			\WP_CLI::error( 'A previous rotation did not commit (previous-key option still present). Run `wp nk vault rewrap` to finish it, then re-try rotate.' );
			return;
		}
		if ( ! self::lock() ) {
			\WP_CLI::error( 'Another rewrap/rotate is already running (lock held).' );
			return;
		}

		$old_fp = Vault_Key::current_fingerprint();
		$started_at = time();

		try {
			$rotation_id = Vault_Key::rotate_to_new();
			if ( '' === $rotation_id ) {
				\WP_CLI::error( 'rotate_to_new() failed (random_bytes or option write?)' );
				return;
			}
			$new_fp = Vault_Key::current_fingerprint();
			\WP_CLI::log( sprintf( '→ Rotating: %s → %s (rotation_id=%s)', $old_fp, $new_fp, $rotation_id ) );

			$total = array( 'scanned' => 0, 'rewrapped' => 0, 'failed' => 0 );
			foreach ( array( 'enc:v3:', 'enc:v2:', 'enc:v1:' ) as $prefix ) {
				$stats = self::rewrap_loop( $prefix, $batch, false );
				if ( $stats['scanned'] > 0 ) {
					\WP_CLI::log( sprintf( '  %s  scanned=%d  rewrapped=%d  failed=%d', $prefix, $stats['scanned'], $stats['rewrapped'], $stats['failed'] ) );
				}
				$total['scanned']   += $stats['scanned'];
				$total['rewrapped'] += $stats['rewrapped'];
				$total['failed']    += $stats['failed'];
			}

			if ( $total['failed'] > 0 ) {
				\WP_CLI::warning( sprintf( '%d records failed to rewrap. Previous key kept in place; investigate manually before re-running rotate. Run `wp nk vault status` to see.', $total['failed'] ) );
				$completed_at = time();
				Vault_Key::log_event( array(
					'id'                => $rotation_id,
					'started_at'        => $started_at,
					'completed_at'      => $completed_at,
					'from_version'      => 'mixed',
					'to_version'        => 'v3',
					'records_total'     => $total['scanned'],
					'records_rewrapped' => $total['rewrapped'],
					'records_failed'    => $total['failed'],
					'key_fingerprint_old' => $old_fp,
					'key_fingerprint_new' => $new_fp,
					'trigger'           => 'cli',
					'committed'         => false,
				) );
				\WP_CLI::error( 'Rotation NOT committed. Re-run after resolving the failures.' );
				return;
			}

			Vault_Key::commit_rotation();
			$completed_at = time();
			Vault_Key::log_event( array(
				'id'                => $rotation_id,
				'started_at'        => $started_at,
				'completed_at'      => $completed_at,
				'from_version'      => 'mixed',
				'to_version'        => 'v3',
				'records_total'     => $total['scanned'],
				'records_rewrapped' => $total['rewrapped'],
				'records_failed'    => $total['failed'],
				'key_fingerprint_old' => $old_fp,
				'key_fingerprint_new' => $new_fp,
				'trigger'           => 'cli',
				'committed'         => true,
			) );
			\WP_CLI::success( sprintf(
				'Rotation committed (rotation_id=%s). Records: scanned=%d  rewrapped=%d.',
				$rotation_id,
				$total['scanned'],
				$total['rewrapped']
			) );
		} finally {
			self::unlock();
		}
	}

	/**
	 * Translate a `--source` flag into a list of envelope prefixes.
	 *
	 * @since 0.3.1
	 * @param string $source User input — `all`, `v1`, `v2`, or comma-separated.
	 * @return array<int, string>
	 */
	private static function resolve_source( string $source ): array {
		$map = array(
			'v1' => 'enc:v1:',
			'v2' => 'enc:v2:',
		);
		if ( '' === $source || 'all' === $source ) {
			return array_values( $map );
		}
		$out = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', $source ) ) ) as $tok ) {
			if ( isset( $map[ $tok ] ) ) {
				$out[] = $map[ $tok ];
			}
		}
		return empty( $out ) ? array_values( $map ) : $out;
	}

	/**
	 * Iterate all order-item-meta rows whose meta_value starts with
	 * the given prefix and rewrap them via Vault. Cursor-driven so
	 * decrypt failures don't loop the same row.
	 *
	 * @since 0.3.1
	 *
	 * @param string $prefix  Envelope prefix to scan for, e.g. `enc:v2:`.
	 * @param int    $batch   Rows per query.
	 * @param bool   $dry_run When true, count but don't write.
	 * @return array{scanned: int, rewrapped: int, failed: int}
	 */
	private static function rewrap_loop( string $prefix, int $batch, bool $dry_run ): array {
		global $wpdb;
		$stats = array( 'scanned' => 0, 'rewrapped' => 0, 'failed' => 0 );
		$last  = 0;
		$table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		while ( true ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$table} WHERE meta_key = %s AND meta_value LIKE %s AND meta_id > %d ORDER BY meta_id ASC LIMIT %d",
				'_ng_gift_card_code',
				$wpdb->esc_like( $prefix ) . '%',
				$last,
				$batch
			) );
			if ( empty( $rows ) ) {
				break;
			}
			foreach ( $rows as $row ) {
				$stats['scanned']++;
				$last = max( $last, (int) $row->meta_id );
				$plain = Vault::decrypt( (string) $row->meta_value );
				if ( '' === $plain ) {
					$stats['failed']++;
					continue;
				}
				$new = Vault::encrypt( $plain );
				if ( 0 !== strpos( $new, 'enc:v3:' ) ) {
					$stats['failed']++;
					continue;
				}
				if ( $new === $row->meta_value ) {
					// Already current — counts as a no-op rewrap. (Should not
					// normally happen, but defensive: if a record is already
					// v3 under the current key, skip the write.)
					continue;
				}
				if ( $dry_run ) {
					$stats['rewrapped']++;
					continue;
				}
				$updated = $wpdb->update(
					$table,
					array( 'meta_value' => $new ),
					array( 'meta_id' => (int) $row->meta_id ),
					array( '%s' ),
					array( '%d' )
				);
				if ( false === $updated ) {
					$stats['failed']++;
				} else {
					$stats['rewrapped']++;
				}
			}
		}
		// phpcs:enable
		return $stats;
	}

	/**
	 * Per-prefix record counts on `_ng_gift_card_code`.
	 *
	 * @since 0.3.1
	 * @return array<string, int>
	 */
	private static function record_counts(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$out   = array(
			'enc:v3:'   => 0,
			'enc:v2:'   => 0,
			'enc:v1:'   => 0,
			'plaintext' => 0,
		);
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach ( array( 'enc:v3:', 'enc:v2:', 'enc:v1:' ) as $prefix ) {
			$out[ $prefix ] = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE meta_key = %s AND meta_value LIKE %s",
				'_ng_gift_card_code',
				$wpdb->esc_like( $prefix ) . '%'
			) );
		}
		$out['plaintext'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE meta_key = %s AND meta_value NOT LIKE %s AND meta_value NOT LIKE %s AND meta_value NOT LIKE %s AND meta_value <> ''",
			'_ng_gift_card_code',
			'enc:v3:%',
			'enc:v2:%',
			'enc:v1:%'
		) );
		// phpcs:enable
		return $out;
	}

	/**
	 * Acquire the rewrap lock.
	 *
	 * @since 0.3.1
	 * @return bool True on success, false when another run holds it.
	 */
	private static function lock(): bool {
		if ( false !== get_transient( self::LOCK_KEY ) ) {
			return false;
		}
		return (bool) set_transient( self::LOCK_KEY, time(), self::LOCK_TTL );
	}

	/**
	 * Release the rewrap lock.
	 *
	 * @since 0.3.1
	 * @return void
	 */
	private static function unlock(): void {
		delete_transient( self::LOCK_KEY );
	}
}
