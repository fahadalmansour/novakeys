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
	 * Boot the plugin — pull in active modules in dependency order.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function boot(): void {
		// Modules — each one is self-registering on load.
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
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-vault-key.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-vault.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-vault-cli.php';
		\NovaKeys\Commerce\Gift_Cards\Vault_CLI::register();
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-store.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-admin.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-refund-revoker.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-customer-endpoint.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/class-bootstrap-tool.php';
		require_once NK_COMMERCE_DIR . 'includes/gift-cards/gift-card-keys-functions.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/class-points.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/class-points-rest.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/class-referral.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/class-coupon-rest.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/class-gift-mailer.php';
		require_once NK_COMMERCE_DIR . 'includes/loyalty/loyalty-functions.php';
		require_once NK_COMMERCE_DIR . 'includes/theme/theme-bridge.php';
		require_once NK_COMMERCE_DIR . 'includes/security/class-mcp-meta-guard.php';
		\NovaKeys\Commerce\Security\MCP_Meta_Guard::register();
		require_once NK_COMMERCE_DIR . 'includes/security/class-user-enum-shield.php';
		\NovaKeys\Commerce\Security\User_Enum_Shield::register();
		require_once NK_COMMERCE_DIR . 'includes/consent/class-cookie-consent.php';
		\NovaKeys\Commerce\Consent\Cookie_Consent::register();

		// Load i18n early so module strings translate.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Fires after NovaKeys Commerce has booted.
		 *
		 * Modules and integrations should hook here rather than on
		 * `plugins_loaded` directly, so they always run *after* every
		 * module file is loaded.
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
