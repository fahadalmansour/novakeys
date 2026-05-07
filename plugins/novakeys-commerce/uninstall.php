<?php
/**
 * NovaKeys Commerce uninstall handler.
 *
 * Conservative on purpose — removes only the migrator flag option so that
 * a re-install will re-run the option migrator. All `_ng_*` postmeta on
 * products and orders is preserved (it backs live customer data). Operator
 * must manually purge legacy data if they want a full clean uninstall.
 *
 * @package NovaKeys\Commerce
 * @since 0.1.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'nk_options_migrated_v1' );
