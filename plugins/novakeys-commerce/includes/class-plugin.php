<?php
/**
 * NovaKeys Commerce plugin singleton.
 *
 * Phase 1: loads no modules. Modules are added incrementally in phase 2,
 * with the corresponding `mu-plugins/novakeys-<module>.php` file renamed to
 * `*.php.disabled` in the same commit so we never double-register hooks.
 *
 * @package NovaKeys\Commerce
 * @since   0.1.0
 */

namespace NovaKeys\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin singleton.
 *
 * @since 0.1.0
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Boot the plugin — load compat shims and pull in active modules.
	 *
	 * Module activation is incremental. Each phase-2 commit adds one or
	 * two `require_once` lines below alongside renaming the corresponding
	 * mu-plugin to `*.php.disabled`.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function boot(): void {
		// Modules — each one is self-registering on load. Loaded BEFORE
		// the compat shim file so `nk_*` functions exist by the time the
		// shims fall back to them.
		require_once NK_COMMERCE_DIR . 'includes/icons/class-icon-registry.php';
		require_once NK_COMMERCE_DIR . 'includes/product-meta/class-arabic-title.php';
		require_once NK_COMMERCE_DIR . 'includes/recommendations/class-recommender.php';
		require_once NK_COMMERCE_DIR . 'includes/vouchers/class-shortcode.php';
		require_once NK_COMMERCE_DIR . 'includes/site/class-customizations.php';
		require_once NK_COMMERCE_DIR . 'includes/seo/class-headers.php';
		require_once NK_COMMERCE_DIR . 'includes/seo/class-text-routes.php';
		require_once NK_COMMERCE_DIR . 'includes/seo/class-legacy-host-rewriter.php';
		require_once NK_COMMERCE_DIR . 'includes/seo/class-rank-math-bridge.php';
		require_once NK_COMMERCE_DIR . 'includes/seo/seo-functions.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/gift-cards-matcher.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-vault.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-store.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-admin.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-refund-revoker.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-customer-endpoint.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-bootstrap-tool.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/gift-card-keys-functions.php';
		require_once NK_COMMERCE_DIR . 'includes/security/class-mcp-meta-guard.php';
		\NovaKeys\Commerce\Security\MCP_Meta_Guard::register();

		// Backwards-compat shims: define `ng_*` aliases for any `nk_*` we
		// ship. Always loaded so external callers (snippets/, scripts/,
		// third-party hooks) keep working until phase 4.
		require_once NK_COMMERCE_DIR . 'includes/compat/class-ng-shims.php';

		// Load i18n early so module strings translate.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Fires after NovaKeys Commerce has booted.
		 *
		 * Modules and integrations should hook here rather than on
		 * `plugins_loaded` directly, so they always run *after* the
		 * compat shims are in place.
		 *
		 * @since 0.1.0
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'nk_commerce_booted', $this );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'novakeys-commerce',
			false,
			dirname( plugin_basename( NK_COMMERCE_FILE ) ) . '/languages/'
		);
	}
}
