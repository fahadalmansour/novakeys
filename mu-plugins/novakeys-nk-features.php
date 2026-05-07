<?php
if (!defined('ABSPATH')) exit;

/* ─── NK Points system ──────────────────────────────────────────────── */
define('NK_POINTS_PER_SAR', 10);
define('NK_WELCOME_POINTS', 50);
define('NK_REFERRAL_POINTS', 250);

function nk_get_points($user_id = 0) {
    if (!$user_id) $user_id = get_current_user_id();
    return (int) get_user_meta($user_id, 'nk_points', true);
}
function nk_add_points($user_id, $points, $reason = '') {
    $current = nk_get_points($user_id);
    update_user_meta($user_id, 'nk_points', $current + (int)$points);
    $log = get_user_meta($user_id, 'nk_points_log', true) ?: [];
    $log[] = ['pts' => $points, 'reason' => $reason, 'time' => time()];
    update_user_meta($user_id, 'nk_points_log', array_slice($log, -50));
}

// Award points on order completion
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    $user_id = $order->get_user_id();
    if (!$user_id) return;
    if (get_post_meta($order_id, '_nk_points_awarded', true)) return;
    $total  = (float) $order->get_total();
    $points = (int) floor($total * NK_POINTS_PER_SAR);
    if (get_user_meta($user_id, 'nk_is_premium', true)) $points *= 2;
    if ($points > 0) {
        nk_add_points($user_id, $points, 'طلب #' . $order_id);
        update_post_meta($order_id, '_nk_points_awarded', 1);
    }
});

// Welcome points on registration
add_action('user_register', function($user_id) {
    nk_add_points($user_id, NK_WELCOME_POINTS, 'مكافأة الترحيب');
    $ref_code = isset($_COOKIE['nk_ref']) ? sanitize_key($_COOKIE['nk_ref']) : '';
    if ($ref_code && strpos($ref_code, 'u') === 0) {
        $referrer_id = (int) substr($ref_code, 1);
        if ($referrer_id > 0 && get_userdata($referrer_id)) {
            nk_add_points($referrer_id, NK_REFERRAL_POINTS, 'إحالة مستخدم جديد');
            update_user_meta($user_id, 'nk_referred_by', $referrer_id);
        }
    }
});

// Mark NK Premium on membership purchase
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && $product->get_sku() === 'NK-MEMBER-25') {
            $user_id = $order->get_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'nk_is_premium', 1);
                nk_add_points($user_id, 100, 'ترقية Premium');
            }
        }
    }
}, 20);

/* ─── Gift sending hook ─────────────────────────────────────────────── */
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    foreach ($order->get_items() as $item) {
        $gift_phone = $item->get_meta('_gift_phone');
        $gift_email = $item->get_meta('_gift_email');
        if (!$gift_phone && !$gift_email) continue;
        $product = $item->get_product();
        $pname   = $product ? $product->get_name() : 'منتج NovaKeys';
        $sender  = $order->get_billing_first_name() ?: 'صديقك';
        $msg     = "هدية من {$sender}!\n\n{$pname}\n\nاشترِ الآن من NovaKeys.store";
        if ($gift_email && is_email($gift_email)) {
            wp_mail($gift_email, "هدية من {$sender} — NovaKeys", $msg);
        }
        if ($gift_phone) {
            $phone   = preg_replace('/\D/', '', '966' . ltrim($gift_phone, '0'));
            $wa_link = 'https://wa.me/' . $phone . '?text=' . rawurlencode($msg);
            $order->add_order_note("هدية للجوال {$gift_phone} — <a href='{$wa_link}' target='_blank'>إرسال WhatsApp</a>");
        }
    }
}, 25);

/* ─── REST API endpoints ────────────────────────────────────────────── */
add_action('rest_api_init', function() {
    register_rest_route('nk/v1', '/points', [
        'methods'             => 'GET',
        'callback'            => function() {
            if (!is_user_logged_in()) return new WP_Error('not_auth', 'Unauthorized', ['status' => 401]);
            $uid = get_current_user_id();
            $pts = nk_get_points($uid);
            return [
                'points'     => $pts,
                'sar'        => round($pts / 100, 2),
                'is_premium' => (bool) get_user_meta($uid, 'nk_is_premium', true),
                'ref_code'   => 'u' . $uid,
            ];
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('nk/v1', '/referral/(?P<code>[a-z0-9]+)', [
        'methods'             => 'GET',
        'callback'            => function($req) {
            $code = sanitize_key($req['code']);
            setcookie('nk_ref', $code, time() + 604800, '/', '', true, true);
            return ['ok' => true];
        },
        'permission_callback' => '__return_true',
    ]);

    // POST /wp-json/nk/v1/coupon — generate share-to-unlock coupon
    register_rest_route('nk/v1', '/coupon', [
        'methods'             => 'POST',
        'callback'            => function($req) {
            $code     = 'NK' . strtoupper(substr(md5(uniqid()), 0, 6));
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $coupon   = wp_insert_post([
                'post_title'  => $code,
                'post_type'   => 'shop_coupon',
                'post_status' => 'publish',
            ]);
            if (!$coupon || is_wp_error($coupon)) return new WP_Error('failed', 'Could not create coupon', ['status' => 500]);
            update_post_meta($coupon, 'discount_type', 'percent');
            update_post_meta($coupon, 'coupon_amount', '10');
            update_post_meta($coupon, 'usage_limit', '1');
            update_post_meta($coupon, 'usage_limit_per_user', '1');
            update_post_meta($coupon, 'date_expires', strtotime($tomorrow));
            return ['code' => $code, 'discount' => '10%', 'expires' => $tomorrow];
        },
        'permission_callback' => '__return_true',
    ]);
});

/* ─── Handle ?ref= URL parameter ───────────────────────────────────── */
add_action('init', function() {
    if (!empty($_GET['ref']) && !isset($_COOKIE['nk_ref'])) {
        $code = sanitize_key($_GET['ref']);
        setcookie('nk_ref', $code, time() + 604800, '/', '', true, true);
    }
});
