<?php
/**
 * Plugin Name: NovaKeys Site Custom
 * Description: Central site customizations deployed via git. Auto-loaded (mu-plugin).
 * Version: 1.38.1
 * Author: Fahad Almansour
 */

defined('ABSPATH') || exit;

if (!defined('NEOGEN_CUSTOM_VERSION')) {
    define('NEOGEN_CUSTOM_VERSION', '1.38.1');
}

/**
 * The WooCommerce version we last reconciled our 15 template overrides
 * against (under mu-plugins/neogen-theme-assets/templates/woocommerce/).
 * Bump after each upstream WC template review pass.
 */
if (!defined('NG_TESTED_WC')) {
    define('NG_TESTED_WC', '10.7');
}

/**
 * Lock the WordPress timezone to Asia/Riyadh — the merchant operates
 * from KSA and all order timestamps, scheduled posts, cron events and
 * email "received at" lines should read in local time. Filtered at
 * runtime (no DB write) so it cannot drift if an admin clicks around in
 * Settings → General. WP also clears `gmt_offset` when timezone_string
 * is set, so we mirror that.
 */
add_filter('pre_option_timezone_string', function () { return 'Asia/Riyadh'; });
add_filter('pre_option_gmt_offset',      function () { return ''; });

// Admin bar badge — shows current deployed version (admin-only, visible proof of successful deploy)
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;
    $wp_admin_bar->add_node([
        'id'    => 'neogen-deployed-version',
        'title' => '🚀 NG ' . NEOGEN_CUSTOM_VERSION,
        'href'  => admin_url('tools.php?page=neogen-deploy'),
        'meta'  => ['title' => 'NovaKeys Custom deployed version'],
    ]);
}, 100);

/**
 * WC compat sentinel — fire a one-line admin notice when the live
 * WooCommerce version is more than two minors newer than NG_TESTED_WC.
 * Two minors is WC's typical "templates may have moved" threshold.
 * Acts as a forcing function to walk the templates/woocommerce/ tree
 * and reconcile against upstream after a major WC bump.
 */
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    if (!defined('WC_VERSION')) return;

    $tested_parts = array_pad(explode('.', NG_TESTED_WC), 2, '0');
    $live_parts   = array_pad(explode('.', WC_VERSION),    2, '0');
    $tested = ((int) $tested_parts[0]) * 100 + ((int) $tested_parts[1]);
    $live   = ((int) $live_parts[0])   * 100 + ((int) $live_parts[1]);

    if ($live - $tested >= 2) {
        echo '<div class="notice notice-warning"><p><strong>NovaKeys:</strong> '
           . 'WooCommerce ' . esc_html(WC_VERSION) . ' is more than two minor '
           . 'versions newer than the version this overlay was last verified '
           . 'against (' . esc_html(NG_TESTED_WC) . '). Reconcile template '
           . 'overrides under <code>mu-plugins/neogen-theme-assets/templates/'
           . 'woocommerce/</code> against the upstream WC files, then bump '
           . '<code>NG_TESTED_WC</code> in <code>neogen-site-custom.php</code>.'
           . '</p></div>';
    }
});

// Add more site-wide customizations below this line

// Allow public read access to WC REST API product catalog (unauthenticated SPA requests)
add_filter('woocommerce_rest_check_permissions', function ($ok, $context, $object_id, $post_type) {
    if ('read' === $context && in_array($post_type, ['product', 'product_variation'], true)) {
        return true;
    }
    return $ok;
}, 10, 4);

// Inject window.NK — nonce, user state, and URLs consumed by app.js
add_action('wp_head', function () {
    $uid    = get_current_user_id();
    $in     = $uid > 0;
    $points = $in ? (int) get_user_meta($uid, 'nk_points', true) : 0;
    printf(
        "<script>window.NK=%s;</script>\n",
        wp_json_encode([
            'restUrl'      => esc_url_raw(rest_url('wc/v3/')),
            'nonce'        => wp_create_nonce('wc_store_api'),
            'wpNonce'      => wp_create_nonce('wp_rest'),
            'isLoggedIn'   => $in,
            'userId'       => $in ? $uid : null,
            'userPoints'   => $points,
            'siteUrl'      => esc_url_raw(home_url()),
            'myAccountUrl' => esc_url_raw(wc_get_page_permalink('myaccount')),
        ], JSON_UNESCAPED_SLASHES)
    );
}, 1);

// Compatibility shim: templates calling ng_cr() get NovaKeys org data via nk_cr()
if (!function_exists('ng_cr')) {
    function ng_cr() { return function_exists('nk_cr') ? nk_cr() : []; }
}
