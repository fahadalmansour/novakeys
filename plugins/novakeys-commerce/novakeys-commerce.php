<?php
/**
 * Plugin Name:       NovaKeys Commerce
 * Plugin URI:        https://github.com/fahadalmansour/novakeys
 * Description:       Companion plugin for the NovaKeys Store. Owns gift-card commerce, NK Points loyalty, vouchers, recommendations, SEO/security headers, icons, and legal-route rewrites. Pairs with the `novakeys` block theme for the visual layer.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Fahad Almansour
 * License:           Proprietary
 * Text Domain:       novakeys-commerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package NovaKeys\Commerce
 */

defined( 'ABSPATH' ) || exit;

define( 'NK_COMMERCE_VERSION', '0.1.0' );
define( 'NK_COMMERCE_FILE', __FILE__ );
define( 'NK_COMMERCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NK_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

require_once NK_COMMERCE_DIR . 'includes/class-plugin.php';

/**
 * Boot the plugin singleton.
 *
 * @since 0.1.0
 * @return void
 */
function nk_commerce_boot() {
	NovaKeys\Commerce\Plugin::instance();
}
add_action( 'plugins_loaded', 'nk_commerce_boot', 5 );

/**
 * Declare HPOS (custom_order_tables) compatibility.
 *
 * Plugin already uses HPOS-safe APIs (wc_get_order, $item->get_meta,
 * $order->save) end-to-end; this is the WC contract that surfaces
 * "Compatible" in WooCommerce → Status → Plugins instead of the
 * default "Uncertified" warning, and unblocks HPOS-default sites
 * from gating plugin features.
 *
 * @since 0.2.5
 * @return void
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			NK_COMMERCE_FILE,
			true
		);
	}
} );

/**
 * Activation hook — runs the one-shot option migrator and flushes rewrites.
 *
 * @since 0.1.0
 * @return void
 */
function nk_commerce_activate() {
	require_once NK_COMMERCE_DIR . 'includes/migrations/class-option-migrator.php';
	NovaKeys\Commerce\Migrations\Option_Migrator::run();
	flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'nk_commerce_activate' );

/**
 * Deactivation hook — flush rewrites so legacy URLs don't 404.
 *
 * @since 0.1.0
 * @return void
 */
function nk_commerce_deactivate() {
	flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'nk_commerce_deactivate' );
