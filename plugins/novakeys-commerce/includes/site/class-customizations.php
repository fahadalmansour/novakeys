<?php
/**
 * Sitewide customizations: timezone lock, admin badge, WC compat
 * sentinel, public-read REST opener, and the `window.NK` JS bootstrap
 * payload consumed by the theme's app.js.
 *
 * @package NovaKeys\Commerce\Site
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Site;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NK_TESTED_WC' ) ) {
	/** WooCommerce minor version this overlay was last reconciled against. */
	define( 'NK_TESTED_WC', '10.7' );
}

// Legacy alias — anything that grepped the old constant name still resolves.
if ( ! defined( 'NG_TESTED_WC' ) ) {
	define( 'NG_TESTED_WC', NK_TESTED_WC );
}

/**
 * Site customizations.
 *
 * @since 0.1.0
 */
final class Customizations {

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Customizations|null
	 */
	private static ?Customizations $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 * @return Customizations
	 */
	public static function instance(): Customizations {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire all hooks.
	 *
	 * Guarded against double-registration when the legacy mu-plugin is
	 * still loaded on the server during a transitional deploy.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		if ( defined( 'NEOGEN_CUSTOM_VERSION' ) ) {
			return;
		}

		// Lock timezone to Asia/Riyadh — KSA merchant context.
		add_filter( 'pre_option_timezone_string', array( $this, 'lock_timezone_string' ) );
		add_filter( 'pre_option_gmt_offset', array( $this, 'lock_gmt_offset' ) );

		// Admin UI.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_badge' ), 100 );
		add_action( 'admin_notices', array( $this, 'maybe_show_wc_compat_notice' ) );

		// Public WC REST read (unauthenticated SPA fetch).
		add_filter( 'woocommerce_rest_check_permissions', array( $this, 'open_wc_rest_public_read' ), 10, 4 );

		// Frontend bootstrap payload.
		add_action( 'wp_head', array( $this, 'inject_window_nk' ), 1 );
	}

	/**
	 * Force the WP option `timezone_string` to Asia/Riyadh at read time.
	 *
	 * Filtered at runtime so it cannot drift if an admin edits Settings →
	 * General. WP also clears `gmt_offset` when `timezone_string` is set,
	 * so we mirror that via {@see lock_gmt_offset()}.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function lock_timezone_string(): string {
		return 'Asia/Riyadh';
	}

	/**
	 * Force `gmt_offset` empty so WP defers to `timezone_string`.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function lock_gmt_offset(): string {
		return '';
	}

	/**
	 * Admin-bar badge showing the deployed plugin version.
	 *
	 * Visible-only-to-admins proof that a deploy succeeded. Links to the
	 * `neogen-deploy` admin page when that plugin is installed; harmless
	 * 404 otherwise.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_admin_bar_badge( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_node(
			array(
				'id'    => 'novakeys-deployed-version',
				'title' => '🚀 NK ' . NK_COMMERCE_VERSION,
				'href'  => admin_url( 'tools.php?page=neogen-deploy' ),
				'meta'  => array( 'title' => 'NovaKeys Commerce deployed version' ),
			)
		);
	}

	/**
	 * Admin notice when live WC is more than two minors newer than
	 * the version we last reconciled template overrides against.
	 *
	 * Acts as a forcing function to re-walk
	 * `mu-plugins/neogen-theme-assets/templates/woocommerce/` (or its
	 * theme equivalent post phase 3) after a major WC bump.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function maybe_show_wc_compat_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		$tested_parts = array_pad( explode( '.', NK_TESTED_WC ), 2, '0' );
		$live_parts   = array_pad( explode( '.', WC_VERSION ), 2, '0' );
		$tested       = ( (int) $tested_parts[0] ) * 100 + (int) $tested_parts[1];
		$live         = ( (int) $live_parts[0] ) * 100 + (int) $live_parts[1];

		if ( $live - $tested < 2 ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>NovaKeys:</strong> '
			. 'WooCommerce ' . esc_html( WC_VERSION ) . ' is more than two minor '
			. 'versions newer than the version this overlay was last verified '
			. 'against (' . esc_html( NK_TESTED_WC ) . '). Reconcile template '
			. 'overrides under <code>mu-plugins/neogen-theme-assets/templates/'
			. 'woocommerce/</code> against the upstream WC files, then bump '
			. '<code>NK_TESTED_WC</code> in '
			. '<code>plugins/novakeys-commerce/includes/site/class-customizations.php</code>.'
			. '</p></div>';
	}

	/**
	 * Open WC REST `read` access on product / product_variation for
	 * unauthenticated callers (frontend SPA).
	 *
	 * Outbound responses are scrubbed by {@see \NovaKeys\Commerce\Security\MCP_Meta_Guard}
	 * which strips `_ng_gift_card_*` keys so this opener cannot leak
	 * encrypted ciphertext even if a custom endpoint surfaces meta.
	 *
	 * @since 0.1.0
	 *
	 * @param bool   $ok        Existing permission check result.
	 * @param string $context   `read` | `create` | `edit` | `delete` | `batch`.
	 * @param int    $object_id Object ID. Unused.
	 * @param string $post_type CPT slug being queried.
	 * @return bool
	 */
	public function open_wc_rest_public_read( $ok, string $context, $object_id, string $post_type ): bool {
		unset( $object_id );
		if ( 'read' === $context && in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return true;
		}
		return (bool) $ok;
	}

	/**
	 * Inject `window.NK` early in `<head>` for app.js consumers that
	 * run before the theme's enqueued localize_script payload arrives.
	 *
	 * The theme's `wp_localize_script('novakeys-app', 'NK', …)` provides
	 * the canonical payload; this inline injection covers the gap for
	 * inline `<script>` tags that read NK before app.js loads.
	 *
	 * @todo Drop in phase 3 once the new FSE theme owns the bootstrap.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function inject_window_nk(): void {
		$uid    = get_current_user_id();
		$in     = $uid > 0;
		$points = $in ? (int) get_user_meta( $uid, 'nk_points', true ) : 0;

		$payload = array(
			'restUrl'      => esc_url_raw( rest_url( 'wc/v3/' ) ),
			'nonce'        => wp_create_nonce( 'wc_store_api' ),
			'wpNonce'      => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn'   => $in,
			'userId'       => $in ? $uid : null,
			'userPoints'   => $points,
			'siteUrl'      => esc_url_raw( home_url() ),
			'myAccountUrl' => function_exists( 'wc_get_page_permalink' ) ? esc_url_raw( wc_get_page_permalink( 'myaccount' ) ) : '',
		);

		printf(
			"<script>window.NK=%s;</script>\n",
			wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) // safe: JSON encode + WP-validated URLs/nonces.
		);
	}
}

Customizations::instance()->register_hooks();
