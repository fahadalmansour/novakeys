<?php
/**
 * One-shot `ng_*` → `nk_*` option key migrator.
 *
 * Runs on plugin activation only, gated by the `nk_options_migrated_v1`
 * option. Idempotent: re-activating the plugin will not re-migrate.
 *
 * Postmeta keys are NOT migrated — they back live customer data and stay
 * `_ng_*` per the project rename contract.
 *
 * @package NovaKeys\Commerce
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Option migrator.
 *
 * @since 0.1.0
 */
final class Option_Migrator {

	/**
	 * Migrator version. Bump to re-run with a different `MIGRATIONS` map.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const VERSION = 'v1';

	/**
	 * Old → new option key map.
	 *
	 * @since 0.1.0
	 * @var array<string, string>
	 */
	private const MIGRATIONS = array(
		'ng_adsense_client_id'                => 'nk_adsense_client_id',
		'ng_gtm_container_id'                 => 'nk_gtm_container_id',
		'ng_seo_engine_enabled'               => 'nk_seo_engine_enabled',
		'ng_csp_enforce'                      => 'nk_csp_enforce',
		'ng_redesign_active_phases'           => 'nk_redesign_active_phases',
		'ng_blocksy_chrome_handoff'           => 'nk_blocksy_chrome_handoff',
		'ng_blocksy_dark_mode_allowed'        => 'nk_blocksy_dark_mode_allowed',
		'ng_simulate_recent'                  => 'nk_simulate_recent',
		'ng_bust_cats'                        => 'nk_bust_cats',
		'ng_gift_cards_assets_cache_busted_v1' => 'nk_gift_cards_assets_cache_busted_v1',
		'ng_gck_endpoint_v1'                  => 'nk_gck_endpoint_v1',
	);

	/**
	 * Run the migrator. Idempotent.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function run(): void {
		$flag = 'nk_options_migrated_' . self::VERSION;
		if ( '1' === get_option( $flag ) ) {
			return;
		}

		foreach ( self::MIGRATIONS as $old_key => $new_key ) {
			$value = get_option( $old_key, null );
			if ( null === $value ) {
				continue;
			}
			if ( false === get_option( $new_key, false ) ) {
				update_option( $new_key, $value, false );
			}
			delete_option( $old_key );
		}

		update_option( $flag, '1', true );
	}
}
