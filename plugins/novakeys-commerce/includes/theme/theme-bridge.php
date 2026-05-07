<?php
/**
 * Theme bridge — sitewide visual chrome for novakeys.store.
 *
 * The largest single file in the plugin. Owns: brand identity (NK_CR
 * + nk_cr()), bilingual label filter (nk_ar_label), taxonomy ordering,
 * shop category tiles, gift-cards archive helpers, the legal/info-page
 * registry (nk_info_pages), the /legal + /terms + /privacy + /returns +
 * /warranty + /usage virtual routes (registered with the routing fix
 * from session 2026-05-07), Schema.org Store JSON-LD, header / footer
 * markup, WC template overrides, and the front-page template swap.
 *
 * Phase 3 of the refactor will replace much of this with FSE templates,
 * parts, patterns, and theme.json tokens. For phase 2 we keep the
 * functional chrome intact and rename `ng_*` symbols to `nk_*` with
 * compat shims.
 *
 * @package NovaKeys\Commerce\Theme
 * @since   0.1.0
 */

defined('ABSPATH') || exit;

if (!defined('NOVAKEYS_THEME_VERSION')) {
    define('NOVAKEYS_THEME_VERSION', '1.5.9');
}

/**
 * Commercial Registration data — single source of truth.
 * Values reproduced verbatim from the active MOC register for
 * CR 7053130576. Do NOT edit to add speculative data — VAT /
 * physical address / extra contacts should arrive via filters
 * (novakeys_org_data, novakeys_org_jsonld, novakeys_legal_extra).
 */
if (!defined('NK_CR')) {
    define('NK_CR', [
        'legal_name_ar'  => 'مكتب فهد سعد فهد المنصور للخدمات الإلكترونية',
        'legal_name_en'  => 'FAHAD SAAD FAHAD ALMANSOUR Office For electronic services',
        'brand_ar'       => 'نيوجين ستور',
        'brand_en'       => 'NovaKeys Store',
        'owner'          => 'FAHAD SAAD FAHAD ALMANSOUR',
        'entity_type'    => 'Sole Proprietorship',
        'entity_type_ar' => 'مؤسسة فردية',
        'cr'             => '7053130576',
        'status'         => 'Active',
        'status_ar'      => 'نشط',
        'register_type'  => 'Main register',
        'registered_ad'  => '2025-12-27',
        'registered_ah'  => '1447-07-07',
        'next_conf_ad'   => '2026-12-26',
        'next_conf_ah'   => '1448-07-17',
        'capital_sar'    => '1.00',
        'phone_landline' => '+966 11 421 3842',
        'phone_mobile'   => '+966 57 013 1122',
        'email'          => 'support@novakeys.store',
        'website'        => 'https://novakeys.store/',
        'activities'     => [
            ['code' => '620101', 'ar' => 'تكامل الأنظمة',                            'en' => 'System integration'],
            ['code' => '821100', 'ar' => 'أنشطة الخدمات الإدارية المتكاملة للمكاتب', 'en' => 'Combined office administrative service activities'],
            ['code' => '829903', 'ar' => 'أنشطة تعقيب المعاملات',                     'en' => 'Transaction follow-up activities'],
        ],
        'authority'      => 'Ministry of Commerce, Kingdom of Saudi Arabia',
        'authority_ar'   => 'وزارة التجارة - المملكة العربية السعودية',
        'authority_url'  => 'https://mc.gov.sa/',
        'verify_url'     => 'https://eservices.mc.gov.sa/',
        // Parent entity — NTS LLC (Wyoming, USA). Brand owner & data processor.
        'parent'         => [
            'name'             => 'NeoTechnology Solutions LLC',
            'name_ar'          => 'شركة نيوتكنولوجي سوليوشنز ذ.م.م.',
            'jurisdiction'     => 'Wyoming, USA',
            'jurisdiction_ar'  => 'ولاية وايومنغ، الولايات المتحدة',
            'reg_number'       => '2025-001744917',
            'reg_label_en'     => 'WY Articles of Organization',
            'reg_label_ar'     => 'مستند تأسيس وايومنغ',
            'ein'              => '36-5148912',
            'ein_label_en'     => 'EIN (US Federal Tax ID)',
            'ein_label_ar'     => 'الرقم الضريبي الفيدرالي الأمريكي',
            'registered_agent' => 'FBRA LLC',
            'registered_addr'  => '1023 E Lincolnway, Cheyenne, WY 82001',
            'principal_addr'   => '1021 E Lincolnway Suite 8983, Cheyenne, WY 82001',
            'phone'            => '+966 57 013 1122',
            'email'            => 'support@novakeys.store',
            'role_en'          => 'Brand owner & data processor (PDPL Art. 29)',
            'role_ar'          => 'مالك العلامة وجهة معالجة البيانات (المادة 29 من نظام حماية البيانات)',
        ],
        // Additional regulatory body registrations (factual, supplied by user).
        'regulatory'     => [
            [
                'key'    => 'zatca',
                'label'  => 'ZATCA REGISTRATION',
                'authority_ar' => 'هيئة الزكاة والضريبة والجمارك',
                'authority_en' => 'Zakat, Tax and Customs Authority',
                'number' => '3145127947',
                'url'    => 'https://zatca.gov.sa/',
            ],
            [
                'key'    => 'csc',
                'label'  => 'CHAMBER OF COMMERCE',
                'authority_ar' => 'هيئة الغرف التجارية',
                'authority_en' => 'Council of Saudi Chambers',
                'number' => '1238532',
                'url'    => 'https://csc.org.sa/',
            ],
        ],
    ]);
}

/**
 * Accessor so future filters can override any field without
 * touching the constant. Always use nk_cr() — never NK_CR directly.
 */
function nk_cr() {
    /** @var array $cr */
    static $cached = null;
    if ($cached === null) {
        $cached = apply_filters('novakeys_org_data', NK_CR);
    }
    return $cached;
}

/**
 * Strip the English side of a bilingual "English | Arabic" string.
 * Returns the Arabic part if a pipe separator is present and the
 * trailing portion contains Arabic characters. Otherwise returns
 * the input unchanged. Used to prefer Arabic labels site-wide
 * without requiring editors to clean up every term/title.
 */
function nk_ar_label( $s ) {
    $s = (string) $s;
    if ( strpos( $s, '|' ) === false ) return $s;
    $parts = array_map( 'trim', explode( '|', $s, 2 ) );
    if ( count( $parts ) === 2 && preg_match( '/[\x{0600}-\x{06FF}]/u', $parts[1] ) ) {
        return $parts[1];
    }
    return $s;
}

// Apply Arabic-side preference at the WP filter layer so every render path
// (Woo grid, breadcrumbs, archive titles, product titles, term names) gets
// the cleaned label without per-site code changes.
add_filter( 'term_name',         'nk_ar_label', 5 );
add_filter( 'single_term_title', 'nk_ar_label', 5 );
add_filter( 'single_cat_title',  'nk_ar_label', 5 );
add_filter( 'list_cats',         'nk_ar_label', 5 );
add_filter( 'woocommerce_product_title', 'nk_ar_label', 5 );

// Scope the_title to products only — protects WC order item titles,
// blog post titles, and any non-product titles that legitimately
// contain a "|" character.
add_filter( 'the_title', function ( $title, $post_id = null ) {
    if ( $post_id && 'product' === get_post_type( $post_id ) ) {
        return nk_ar_label( $title );
    }
    return $title;
}, 5, 2 );
add_filter( 'woocommerce_breadcrumb_main_term', function ( $term ) {
    if ( is_object( $term ) && isset( $term->name ) ) {
        $term->name = nk_ar_label( $term->name );
    }
    return $term;
}, 5 );

/**
 * Top-level product_cat terms by SKU count, transient-cached for 1
 * hour. Used by the sysbar nav, footer, and homepage front-page
 * template. On a host without a persistent object cache (Blocksy on
 * blazr.net VPS), this saves one terms-table SELECT per page load.
 *
 * Cache busts via the edited/created/delete _product_cat hooks
 * registered below.
 */
function nk_top_product_cats($limit = 6) {
    if (!taxonomy_exists('product_cat')) { return []; }
    $limit = max(1, (int) $limit);
    $key   = 'novakeys_top_cats_' . $limit;
    $cached = get_transient($key);
    if (is_array($cached)) { return $cached; }

    // Slugs to hide from public top-cats lists (rack, topnav, footer).
    // Filterable so individual snippets can override per-surface later.
    $exclude_slugs = apply_filters( 'novakeys_top_cats_exclude_slugs', [ 'homelab', 'uncategorized' ] );

    // Pull a few extra so we still get $limit results after filtering.
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $limit + count($exclude_slugs),
    ]);
    if (is_wp_error($terms)) { return []; }

    if ( ! empty( $exclude_slugs ) ) {
        $terms = array_values( array_filter( $terms, function ( $t ) use ( $exclude_slugs ) {
            return ! in_array( $t->slug, $exclude_slugs, true );
        } ) );
    }
    $terms = array_slice( $terms, 0, $limit );

    set_transient($key, $terms, HOUR_IN_SECONDS);
    return $terms;
}

/**
 * Bust the nk_top_product_cats() transient when categories change.
 * Covers the three limit values currently called from this codebase
 * (5, 6, plus a safety margin in case future call sites add more).
 */
$ng_bust_cats = function () {
    foreach ([4, 5, 6, 7, 8] as $n) {
        delete_transient('novakeys_top_cats_' . $n);
    }
};
add_action('edited_product_cat',  $ng_bust_cats);
add_action('created_product_cat', $ng_bust_cats);
add_action('delete_product_cat',  $ng_bust_cats);

/**
 * Resolve a product's primary-category slug, honoring Rank Math then
 * Yoast SEO before falling back to the alphabetical-first term. Used
 * by the homepage picker (templates/front-page.php) so the
 * "diversify across categories" rule respects the editor's intent
 * instead of WP's default term ordering.
 */
function nk_primary_product_cat_slug($product) {
    if ( ! is_object($product) || ! method_exists($product, 'get_id') ) return '';
    $id = (int) $product->get_id();
    if ($id <= 0) return '';

    // Rank Math primary cat
    $rm_id = (int) get_post_meta($id, 'rank_math_primary_product_cat', true);
    if ($rm_id > 0) {
        $term = get_term($rm_id, 'product_cat');
        if ($term && ! is_wp_error($term)) return (string) $term->slug;
    }

    // Yoast SEO primary cat
    $yo_id = (int) get_post_meta($id, '_yoast_wpseo_primary_product_cat', true);
    if ($yo_id > 0) {
        $term = get_term($yo_id, 'product_cat');
        if ($term && ! is_wp_error($term)) return (string) $term->slug;
    }

    // Fallback — first attached product_cat (alphabetical)
    $terms = get_the_terms($id, 'product_cat');
    if ( ! is_wp_error($terms) && ! empty($terms) ) {
        return (string) $terms[0]->slug;
    }
    return '';
}

/**
 * Code-level fallback image for a product_cat slug. Resolves in this
 * order:
 *   1. novakeys_theme_category_image_fallbacks filter map (slug → URL)
 *   2. brands/{slug}/_default.webp if file exists on disk
 *   3. null — caller falls back to SVG icon
 *
 * Admin uploads via Products → Categories → Thumbnail always win
 * over this; the helper is only consulted when no thumbnail_id is
 * set in term-meta. Filter the map from any mu-plugin to extend.
 *
 * Bundled defaults shipped with the repo:
 *   - gift-cards → playstation.webp (most-recognized of the 11 brand
 *     cards we ship; admin can override per-category later)
 *
 * @param string $slug product_cat term slug
 * @return string|null image URL or null if none available
 */
function nk_category_image_fallback( $slug ) {
    static $resolved = [];
    if ( array_key_exists($slug, $resolved) ) return $resolved[$slug];

    $base = defined('NG_THEME_ASSET_URL') ? NG_THEME_ASSET_URL : '';
    $defaults = [
        'gift-cards' => $base . '/img/gift-cards/playstation.webp',
    ];

    /**
     * Filter the slug → image-URL fallback map. Only consulted when
     * the category has no admin-uploaded thumbnail.
     *
     * @param array  $defaults Default map (slug => URL).
     * @param string $slug     The slug being resolved.
     */
    $map = apply_filters( 'novakeys_theme_category_image_fallbacks', $defaults, $slug );

    $url = isset($map[$slug]) ? (string) $map[$slug] : '';

    // v1.36.0: prefer a designed cover at img/cat/<slug>.svg if shipped.
    if ( $url === '' && defined('NG_THEME_ASSET_DIR') ) {
        $disk = NG_THEME_ASSET_DIR . '/img/cat/' . $slug . '.svg';
        if ( is_readable($disk) ) {
            $url = $base . '/img/cat/' . $slug . '.svg';
        }
    }

    // Auto-discover a brands/{slug}/_default.* file as a secondary path.
    if ( $url === '' && defined('NG_THEME_ASSET_DIR') ) {
        foreach ( ['webp','jpg','png'] as $ext ) {
            $disk = NG_THEME_ASSET_DIR . '/img/brands/' . $slug . '/_default.' . $ext;
            if ( is_readable($disk) ) {
                $url = $base . '/img/brands/' . $slug . '/_default.' . $ext;
                break;
            }
        }
    }

    // v1.36.0: final tier — designed _default cover.
    if ( $url === '' && defined('NG_THEME_ASSET_DIR') ) {
        $disk = NG_THEME_ASSET_DIR . '/img/cat/_default.svg';
        if ( is_readable($disk) ) {
            $url = $base . '/img/cat/_default.svg';
        }
    }

    return $resolved[$slug] = ( $url !== '' ? $url : null );
}

/**
 * Inject a category tiles strip at the top of the WooCommerce shop
 * and product-category archives. Reuses the homepage `.ng-rack-*`
 * markup so the existing CSS picks it up site-wide. Only fires on
 * is_shop() and is_product_category() — search/tag archives skip.
 */
// v1.34.2: split rendering by context.
//   - Bare /shop/ archive: rack stays at TOP (priority 5 before the loop)
//     because it's the primary entry point — "اختر فئة".
//   - Inside any /product-category/.../ archive: rack moves to BOTTOM
//     (priority 15 after the loop) — it's cross-nav, not a primary
//     action, so the products themselves should lead the page.
add_action('woocommerce_before_shop_loop', 'nk_shop_category_tiles_top', 5);
add_action('woocommerce_after_shop_loop',  'nk_shop_category_tiles_bottom', 15);

function nk_shop_category_tiles_top() {
    if ( ! function_exists('is_shop') ) return;
    if ( ! is_shop() ) return; // category archives render via _bottom
    nk_shop_category_tiles();
}
function nk_shop_category_tiles_bottom() {
    if ( ! function_exists('is_product_category') ) return;
    if ( ! is_product_category() ) return; // bare shop renders via _top
    nk_shop_category_tiles();
}

function nk_shop_category_tiles() {
    if ( ! function_exists('is_shop') ) return;
    if ( ! ( is_shop() || is_product_category() ) ) return;
    if ( ! function_exists('nk_top_product_cats') ) return;

    // v1.34.1: skip on gift-cards parent — the brand grid below
    // (ng_gift_cards_brand_grid, prio 9) is the primary cross-nav
    // there, so this rack would just stack redundantly. Descendant
    // pages (game-cards/playstation/) still get the rack so users
    // can jump back to other top-level cats from a brand drilldown.
    if ( is_product_category( 'gift-cards' ) ) return;

    $current_id = is_product_category() ? get_queried_object_id() : 0;
    $cats = nk_top_product_cats(6);
    if ( empty( $cats ) ) return;

    $icons    = apply_filters('novakeys_theme_category_icons', []);
    $fallback = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 10h8M8 14h6"/></svg>';
    $led_patterns = [
        '<span class="l on"></span><span class="l"></span><span class="l cyan"></span>',
        '<span class="l cyan"></span><span class="l on"></span><span class="l"></span>',
        '<span class="l"></span><span class="l cyan"></span><span class="l on"></span>',
    ];

    // v1.35.2: when the rack appears at the bottom of a category page
    // (cross-nav, not primary entry), render a compact "footer rack"
    // variant — chip-card lane instead of the heavy 6-column rack-id
    // strip used on bare Shop. Same data, lighter visual weight.
    $is_inside_cat = is_product_category();
    $section_cls   = 'ng-section ng-shop-cats' . ( $is_inside_cat ? ' ng-shop-cats--footer' : '' );

    echo '<section class="' . esc_attr( $section_cls ) . '">';
    echo '<div class="ng-container">';
    echo '<div class="ng-section-head">';
    echo   '<div class="ng-section-kicker"><span></span>المتجر · <b>حسب الفئة</b></div>';
    echo   '<div class="ng-section-titles">';
    if ( $is_inside_cat ) {
        echo '<h2 class="ng-section-en">تصفّح فئات أخرى.</h2>';
        echo '<div class="ng-section-ar">كل الفئات في المتجر — للقفز السريع.</div>';
    } else {
        echo '<h2 class="ng-section-en">اختر فئة.</h2>';
        echo '<div class="ng-section-ar">اختر فئة لبدء التصفّح.</div>';
    }
    echo   '</div>';
    echo '</div>';
    echo '<div class="ng-rack' . ( $is_inside_cat ? ' ng-rack--footer' : '' ) . '">';
    foreach ( $cats as $i => $term ) {
        $slug      = $term->slug;
        $thumb_id  = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
        $link      = get_term_link($term);
        $link      = is_wp_error($link) ? '#' : $link;
        $is_curr   = ( (int) $term->term_id === $current_id );
        $ar_name   = trim((string) $term->description);
        if ( $ar_name === '' ) { $ar_name = nk_ar_label( $term->name ); }
        $led       = $led_patterns[$i % count($led_patterns)];
        $rack_id   = sprintf('%02d · رف %s', $i + 1, chr(65 + $i));
        $cls       = 'ng-rack-unit reveal' . ( $is_curr ? ' is-current' : '' );

        echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($link) . '">';
        echo   '<span class="ng-rack-id">' . esc_html($rack_id) . '</span>';
        echo   '<span class="ng-rack-led" aria-hidden="true">' . $led . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $fallback_url = nk_category_image_fallback( $slug );

        if ( $thumb_id ) {
            // Admin-uploaded term thumbnail wins over everything.
            echo '<span class="ng-rack-photo" aria-hidden="true">';
            echo wp_get_attachment_image( $thumb_id, 'medium', false, [ 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ] );
            echo '</span>';
        } elseif ( $fallback_url ) {
            // Code-level fallback for categories without a manual upload.
            echo '<span class="ng-rack-photo" aria-hidden="true">';
            echo '<img src="' . esc_url($fallback_url) . '" alt="" loading="lazy" decoding="async" width="240" height="240">';
            echo '</span>';
        } else {
            $icon = isset($icons[$slug]) ? $icons[$slug] : $fallback;
            echo '<span class="ng-rack-icon" aria-hidden="true">' . $icon . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo   '<span class="ng-rack-title">';
        echo     '<span class="ar">' . esc_html($ar_name) . '</span>';
        echo   '</span>';
        echo   '<span class="ng-rack-count"><b>' . (int) $term->count . '</b> منتج</span>';
        echo   '<span class="ng-rack-link">تصفّح <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>';
        echo '</a>';
    }
    echo '</div></div></section>';
}

/**
 * Gift-cards category-archive extras (v1.32.0 → v1.33.0).
 *
 * On /product-category/gift-cards/ only, inject a region-tab row + a
 * trust strip ABOVE the product grid — LikeCard-style intent. Region
 * tabs filter the loop via `_ng_gift_card_region` post meta when set.
 *
 * Hooks `woocommerce_before_shop_loop` at priority 8 — after
 * nk_shop_category_tiles (prio 5) which renders the cross-nav, before
 * the WC sort row (prio 10).
 */

/**
 * Filter the main archive query on /product-category/gift-cards/ (and
 * its descendants) by ?region=<slug> and/or ?brand=<slug>. Both meta
 * lookups stack so combinations like ?region=us&brand=playstation work.
 */
add_action('pre_get_posts', function ($q) {
    if ( is_admin() || ! $q->is_main_query() ) return;
    // Apply on /gift-cards/ and any descendant brand sub-cat too.
    $on_gift_cards = is_product_category( 'gift-cards' );
    if ( ! $on_gift_cards && function_exists('is_product_category') && is_product_category() ) {
        $term = get_queried_object();
        if ( $term && $term->taxonomy === 'product_cat' ) {
            $ancestors = get_ancestors( $term->term_id, 'product_cat' );
            $gc        = get_term_by( 'slug', 'gift-cards', 'product_cat' );
            if ( $gc && in_array( $gc->term_id, $ancestors, true ) ) {
                $on_gift_cards = true;
            }
        }
    }
    if ( ! $on_gift_cards ) return;

    $region = isset( $_GET['region'] ) ? sanitize_key( $_GET['region'] ) : '';
    $brand  = isset( $_GET['brand'] )  ? sanitize_key( $_GET['brand'] )  : '';
    if ( $region === '' && $brand === '' ) return;

    $existing = (array) $q->get( 'meta_query' );
    if ( $region !== '' ) {
        $existing[] = array(
            'key'     => '_ng_gift_card_region',
            'value'   => $region,
            'compare' => '=',
        );
    }
    if ( $brand !== '' ) {
        $existing[] = array(
            'key'     => '_ng_gift_card_brand',
            'value'   => $brand,
            'compare' => '=',
        );
    }
    $q->set( 'meta_query', $existing );
});

add_action('woocommerce_before_shop_loop', 'nk_gift_cards_archive_extras', 8);
function nk_gift_cards_archive_extras() {
    if ( ! is_product_category( 'gift-cards' ) ) { return; }

    // Region tabs — wired to ?region= via the pre_get_posts hook above.
    // Each region: [label, svg-icon]. Flag glyphs replaced with line-art
    // SVGs so the chrome doesn't depend on emoji-font availability and
    // sidesteps the trademark/cultural questions of country-flag art.
    $svg_globe = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
    $svg_pin   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 21s-7-7.4-7-12a7 7 0 1 1 14 0c0 4.6-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>';
    $regions = array(
        ''       => array( 'الكل',                $svg_globe ),
        'ksa'    => array( 'السعودية',            $svg_pin   ),
        'gcc'    => array( 'دول الخليج',          ''         ),
        'us'     => array( 'الولايات المتحدة',     $svg_pin   ),
        'uk'     => array( 'المملكة المتحدة',      $svg_pin   ),
        'global' => array( 'عالمي',               $svg_globe ),
    );
    $current = isset( $_GET['region'] ) ? sanitize_key( $_GET['region'] ) : '';

    echo '<section class="ng-gc-extras">';
    echo   '<div class="ng-gc-trust" aria-label="ضمانات بطاقات الهدايا">';
    echo     '<div class="ng-gc-trust-item">';
    echo       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>';
    echo       '<div><div class="ng-gc-trust-label">تسليم فوري</div><div class="ng-gc-trust-sub">رمز التفعيل خلال دقيقة</div></div>';
    echo     '</div>';
    echo     '<div class="ng-gc-trust-item">';
    echo       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="m9 12 2 2 4-4"/></svg>';
    echo       '<div><div class="ng-gc-trust-label">تفعيل آمن</div><div class="ng-gc-trust-sub">مفاتيح أصلية مضمونة</div></div>';
    echo     '</div>';
    echo     '<div class="ng-gc-trust-item">';
    echo       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
    echo       '<div><div class="ng-gc-trust-label">دعم سريع</div><div class="ng-gc-trust-sub">واتساب · يوم عمل</div></div>';
    echo     '</div>';
    echo     '<div class="ng-gc-trust-item">';
    echo       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/></svg>';
    echo       '<div><div class="ng-gc-trust-label">دفع موثوق</div><div class="ng-gc-trust-sub">Mada · Apple Pay · STC Pay</div></div>';
    echo     '</div>';
    echo   '</div>';

    echo   '<div class="ng-gc-regions" role="tablist" aria-label="فلتر المنطقة">';
    echo     '<div class="ng-gc-regions-label">المنطقة:</div>';
    echo     '<div class="ng-gc-regions-tabs">';
    foreach ( $regions as $key => $info ) {
        list( $label, $flag ) = $info;
        $is_current = ( $current === $key );
        $url = $key === ''
            ? esc_url( get_term_link( get_queried_object() ) )
            : esc_url( add_query_arg( 'region', $key, get_term_link( get_queried_object() ) ) );
        echo '<a class="ng-gc-region-tab' . ( $is_current ? ' is-current' : '' ) . '" href="' . $url . '" role="tab" aria-selected="' . ( $is_current ? 'true' : 'false' ) . '">';
        if ( $flag !== '' ) {
            echo '<span class="ng-gc-region-flag" aria-hidden="true">' . $flag . '</span>';
        }
        echo   '<span class="ng-gc-region-label">' . esc_html( $label ) . '</span>';
        echo '</a>';
    }
    echo     '</div>';
    echo   '</div>';
    echo '</section>';
}

/**
 * Brand grid above the gift-cards product loop.
 *
 * v1.34.0 — flat 14-up grid.
 * v1.35.1 — REDESIGN: three themed lanes (Game Cards / App Stores /
 * Subscriptions) instead of one flat row. Each lane has its own
 * kicker, brands ranked by variant count inside the lane, and a
 * "الأكثر تنوّعاً" pill on the lane leader. Tiles use a clean square
 * logo well + AR name + count chip top-corner — easier to scan than
 * the v1.34.0 image-stack layout.
 *
 * Click → brand sub-category URL when it exists, otherwise ?brand=
 * filter fallback. Only fires on /product-category/gift-cards/ when
 * no brand filter is active.
 */
add_action('woocommerce_before_shop_loop', 'nk_gift_cards_brand_grid', 9);
function nk_gift_cards_brand_grid() {
    if ( ! is_product_category( 'gift-cards' ) ) { return; }
    if ( ! empty( $_GET['brand'] ) ) { return; }

    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT pm.meta_value AS brand_slug, MIN(p.ID) AS sample_pid, COUNT(*) AS variant_count
           FROM {$wpdb->postmeta} pm
           JOIN {$wpdb->posts}    p  ON p.ID = pm.post_id
          WHERE pm.meta_key = '_ng_gift_card_brand'
            AND p.post_type = 'product'
            AND p.post_status = 'publish'
          GROUP BY pm.meta_value
          ORDER BY variant_count DESC"
    );
    if ( empty( $rows ) ) { return; }

    // Brand → AR label + parent lane. Keep in sync with
    // scripts/neogen-gift-cards-bulk.php and -brand-cats.php.
    $brand_meta = array(
        'playstation'      => array( 'ar' => 'بلايستيشن',     'lane' => 'game-cards'    ),
        'xbox'             => array( 'ar' => 'إكس بوكس',     'lane' => 'game-cards'    ),
        'steam'            => array( 'ar' => 'ستيم',         'lane' => 'game-cards'    ),
        'razer-gold'       => array( 'ar' => 'رازر قولد',    'lane' => 'game-cards'    ),
        'roblox'           => array( 'ar' => 'روبلكس',       'lane' => 'game-cards'    ),
        'pubg-mobile'      => array( 'ar' => 'ببجي موبايل',  'lane' => 'game-cards'    ),
        'free-fire'        => array( 'ar' => 'فري فاير',     'lane' => 'game-cards'    ),
        'mobile-legends'   => array( 'ar' => 'موبايل ليجندز','lane' => 'game-cards'    ),
        'apple-itunes'     => array( 'ar' => 'آبل أيتونز',   'lane' => 'app-stores'    ),
        'google-play'      => array( 'ar' => 'قوقل بلاي',    'lane' => 'app-stores'    ),
        'netflix'          => array( 'ar' => 'نتفلكس',       'lane' => 'subscriptions' ),
        'spotify'          => array( 'ar' => 'سبوتيفاي',      'lane' => 'subscriptions' ),
        'disney-plus'      => array( 'ar' => 'ديزني بلس',    'lane' => 'subscriptions' ),
        'playstation-plus' => array( 'ar' => 'بلايستيشن بلس','lane' => 'subscriptions' ),
    );

    $lanes = array(
        'game-cards'    => array( 'ar' => 'بطاقات الألعاب',     'kicker' => 'العاب · GAMING',        'brands' => array() ),
        'app-stores'    => array( 'ar' => 'متاجر التطبيقات',    'kicker' => 'تطبيقات · APP STORES',  'brands' => array() ),
        'subscriptions' => array( 'ar' => 'الاشتراكات الترفيهية', 'kicker' => 'ترفيه · STREAMING',   'brands' => array() ),
    );

    foreach ( $rows as $row ) {
        $slug = $row->brand_slug;
        $meta = $brand_meta[ $slug ] ?? null;
        if ( ! $meta ) { continue; }
        $lanes[ $meta['lane'] ]['brands'][] = array(
            'slug'    => $slug,
            'ar'      => $meta['ar'],
            'pid'     => (int) $row->sample_pid,
            'variants'=> (int) $row->variant_count,
        );
    }

    $total_brands = count( $rows );
    $total_cards  = (int) array_sum( array_map( fn( $r ) => (int) $r->variant_count, $rows ) );

    echo '<section class="ng-gc-brands-grid-wrap ng-gc-brands--lanes">';

    // v1.36.0 — designed hero banner above the brands head.
    if ( defined( 'NG_THEME_ASSET_DIR' ) && is_readable( NG_THEME_ASSET_DIR . '/img/hero/gift-cards.svg' ) ) {
        $hero_url = ( defined( 'NG_THEME_ASSET_URL' ) ? NG_THEME_ASSET_URL : '' ) . '/img/hero/gift-cards.svg';
        echo '<figure class="ng-gc-hero">';
        echo   '<img src="' . esc_url( $hero_url ) . '" alt="بطاقات رقمية · NovaKeys" loading="lazy" decoding="async">';
        echo '</figure>';
    }

    echo   '<div class="ng-gc-brands-head">';
    echo     '<div class="ng-gc-brands-kicker"><span></span>المتجر · <b>بطاقات رقمية</b></div>';
    echo     '<h2 class="ng-gc-brands-h">العلامات التجارية</h2>';
    echo     '<div class="ng-gc-brands-sub">' . (int) $total_brands . ' علامة · ' . (int) $total_cards . ' بطاقة · ' . count( $lanes ) . ' فئات</div>';
    echo   '</div>';

    foreach ( $lanes as $lane_key => $lane ) {
        if ( empty( $lane['brands'] ) ) { continue; }

        $lane_term = get_term_by( 'slug', $lane_key, 'product_cat' );
        $lane_url  = ( $lane_term && ! is_wp_error( $lane_term ) ) ? get_term_link( $lane_term ) : '';
        $lane_url  = is_wp_error( $lane_url ) ? '' : $lane_url;
        $lane_total = array_sum( array_map( fn( $b ) => $b['variants'], $lane['brands'] ) );

        echo '<div class="ng-gc-lane" data-lane="' . esc_attr( $lane_key ) . '">';
        echo   '<header class="ng-gc-lane-head">';
        echo     '<div class="ng-gc-lane-kicker">' . esc_html( $lane['kicker'] ) . '</div>';
        echo     '<div class="ng-gc-lane-titles">';
        echo       '<h3 class="ng-gc-lane-h">' . esc_html( $lane['ar'] ) . '</h3>';
        echo       '<span class="ng-gc-lane-count">' . count( $lane['brands'] ) . ' علامة · ' . (int) $lane_total . ' بطاقة</span>';
        echo     '</div>';
        if ( $lane_url ) {
            echo   '<a class="ng-gc-lane-all" href="' . esc_url( $lane_url ) . '">عرض الكل <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>';
        }
        echo   '</header>';
        echo   '<div class="ng-gc-lane-strip">';

        $is_first = true;
        foreach ( $lane['brands'] as $b ) {
            // v1.36.0: prefer designed brand SVG (img/brand/<slug>.svg)
            // over the sample product's tiny featured thumbnail.
            $img       = '';
            $is_designed = false;
            if ( defined( 'NG_THEME_ASSET_DIR' )
                 && is_readable( NG_THEME_ASSET_DIR . '/img/brand/' . $b['slug'] . '.svg' ) ) {
                $img = ( defined( 'NG_THEME_ASSET_URL' ) ? NG_THEME_ASSET_URL : '' ) . '/img/brand/' . $b['slug'] . '.svg';
                $is_designed = true;
            } else {
                $sample = wc_get_product( $b['pid'] );
                $img_id = $sample instanceof WC_Product ? (int) $sample->get_image_id() : 0;
                $img    = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
            }

            $term = get_term_by( 'slug', $b['slug'], 'product_cat' );
            $url  = ( $term && ! is_wp_error( $term ) )
                ? get_term_link( $term )
                : add_query_arg( 'brand', $b['slug'], get_term_link( get_queried_object() ) );
            $url  = is_wp_error( $url ) ? '#' : $url;

            $cls = 'ng-gc-brand-tile';
            if ( $is_first ) { $cls .= ' is-lead'; }
            if ( $is_designed ) { $cls .= ' is-designed'; }

            echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '" data-brand="' . esc_attr( $b['slug'] ) . '"' . ( $is_designed ? ' data-asset="svg"' : '' ) . '>';
            echo   '<span class="ng-gc-brand-chip" dir="ltr">' . (int) $b['variants'] . '</span>';
            if ( $is_first ) {
                echo '<span class="ng-gc-brand-pill">الأكثر تنوّعاً</span>';
            }
            if ( $img ) {
                echo '<span class="ng-gc-brand-img"><img src="' . esc_url( $img ) . '" alt="" loading="lazy" decoding="async"></span>';
            } else {
                echo '<span class="ng-gc-brand-img ng-gc-brand-img--placeholder" aria-hidden="true"></span>';
            }
            echo   '<span class="ng-gc-brand-name">' . esc_html( $b['ar'] ) . '</span>';
            echo   '<span class="ng-gc-brand-cta">تصفّح <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>';
            echo '</a>';

            $is_first = false;
        }

        echo   '</div>';
        echo '</div>';
    }

    echo '</section>';
}

/**
 * Info-page registry — drives every /about/, /terms/, /privacy/,
 * /returns/, /warranty/, /shipping/, /contact/ route through one
 * shared template. Pages marked draft=true render a "PENDING LEGAL
 * REVIEW" banner and use placeholder section bodies.
 *
 * Real legal copy arrives via add_action('novakeys_info_extra_<slug>',
 * fn($cr) => echo '<div>...</div>'). The registry stays factual.
 */
function nk_info_pages() {
    static $cached = null;
    if ($cached !== null) { return $cached; }

    $cr = nk_cr();

    $cached = [

        'about' => [
            'kicker'   => '02 · ABOUT',
            'h1_en'    => 'WHO WE ARE',
            'h1_ar'    => 'من نحن',
            'lede_en'  => 'A Saudi technology retailer built for the operators behind the screens — networks, homelabs, smart homes, competitive gaming, and the people who keep them running.',
            'lede_ar'  => 'متجر تقني سعودي للمختصين خلف الشاشات — شبكات، هوم لاب، بيوت ذكية، عتاد ألعاب تنافسية، والمشغّلين الذين يديرون كل ذلك.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · MISSION',
                    'h_en' => 'Specialized hardware, no commodity noise.',
                    'h_ar' => 'عتاد متخصص، بدون ضوضاء العامة.',
                    'body' => [
                        'NovaKeys Store carries gear used by people who actually deploy infrastructure inside the Kingdom. We curate by reliability, repairability, and parts availability — not by the brand with the loudest ad spend.',
                        'إذا لم يكن المنتج مفيداً على شبكة جدية، تركيبة هوم لاب، بيت ذكي حقيقي، محطة ألعاب تنافسية، أو مشروع تركيب فعلي — لا نحمله.',
                    ],
                ],
                [
                    'kicker_en' => '02 · WHO WE SERVE',
                    'h_en' => 'Built for operators.',
                    'h_ar' => 'مبني للمشغّلين.',
                    'body' => [
                        'Network engineers buying enterprise APs and switches for SMB sites. Hobbyists running k3s clusters in their garages on Synology + Mikrotik. Smart-home installers commissioning Aqara and Shelly across villas. Competitive gamers chasing 0.1 ms latency cuts. We carry exactly what each of these jobs requires.',
                    ],
                ],
                [
                    'kicker_en' => '03 · WHAT WE STOCK',
                    'h_en' => 'Five racks. Each one curated.',
                    'h_ar' => 'خمسة "رفوف". كل رف مختار بعناية.',
                    'body' => [
                        'NETWORKING / WI-FI 7 — Ubiquiti, MikroTik, TP-Link Omada. Routers, PoE switches, access points, SFP+ modules, fiber patch.',
                        'HOMELAB / STORAGE — NAS, rack-mount mini-PCs, enterprise HDDs and SSDs, UPS units, rack accessories, cable management.',
                        'SMART HOME / MATTER — Aqara, Shelly, Sonoff. Hubs, sensors, relays, door locks, lighting, Matter-over-Thread bridges.',
                        'GAMING / COMPETITIVE — Hall-effect switches, low-latency monitors, studio audio, DAC/AMP, mechanical keyboards, custom cables.',
                        'SERVICES / BUILDS — Network design, rack builds, smart-home commissioning, on-site setup inside KSA metros.',
                    ],
                ],
                [
                    'kicker_en' => '04 · HOW WE OPERATE',
                    'h_en' => 'Plain specs. Honest stock. Real tracking.',
                    'h_ar' => 'مواصفات واضحة. مخزون حقيقي. تتبع فعلي.',
                    'body' => [
                        'Every product page shows the SKU, real specifications without marketing inflation, live stock count, and a single primary action. No decoy buttons.',
                        'الشحن من داخل المملكة، 2-5 أيام عمل لمدن الرياض وجدة والدمام. ضمان 12 شهراً، ودعم بالعربي.',
                    ],
                ],
                [
                    'kicker_en' => '05 · LEGAL · OFFICIAL',
                    'h_en' => 'Saudi MOC-registered establishment.',
                    'h_ar' => 'منشأة سعودية مرخصة من وزارة التجارة.',
                    'body' => [
                        sprintf('Trade name: %s. Owner: %s. CR: %s. Status: Active.', $cr['legal_name_en'], $cr['owner'], $cr['cr']),
                        sprintf('الاسم التجاري: %s · المالك: %s · السجل التجاري: %s · الحالة: نشط.', $cr['legal_name_ar'], $cr['owner'], $cr['cr']),
                        'Full identity readout, regulatory registrations, and verification links: <a href="' . esc_url(home_url('/legal/')) . '">/legal/ →</a>',
                    ],
                ],
            ],
        ],

        'shipping' => [
            'kicker'   => '03 · SHIPPING',
            'h1_en'    => 'SHIPPING POLICY',
            'h1_ar'    => 'سياسة الشحن',
            'lede_en'  => 'Shipped from inside the Kingdom. 2–5 business days to Riyadh, Jeddah, and Dammam metros. Tracking via WhatsApp and email.',
            'lede_ar'  => 'الشحن من داخل المملكة. 2-5 أيام عمل لمدن الرياض وجدة والدمام. التتبع عبر واتساب والبريد الإلكتروني.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · ZONES',
                    'h_en' => 'Where we ship.',
                    'h_ar' => 'مناطق التغطية.',
                    'body' => [
                        'PRIMARY METROS — Riyadh · Jeddah · Dammam · Khobar · Dhahran. Same-day or next-day handling, 2–3 business days transit.',
                        'OTHER KSA CITIES — Madinah · Makkah · Tabuk · Abha · Hail · Buraidah · Najran · Yanbu · Jubail and others. 3–5 business days transit.',
                        'REMOTE GOVERNORATES — Smaller villages and remote areas may add 1–2 days. Tracking is provided regardless.',
                        'INTERNATIONAL — Not currently offered.',
                    ],
                ],
                [
                    'kicker_en' => '02 · TIMELINES',
                    'h_en' => 'When it ships.',
                    'h_ar' => 'متى يُشحن.',
                    'body' => [
                        'Orders placed before 14:00 KSA time on a business day are typically dispatched the same day. Orders placed later, or on weekends/holidays, are dispatched the next business day.',
                        'Backorders are flagged at checkout and on the product page (LOW STOCK / OUT badges). For backorders, we contact you with the expected restock date before processing payment.',
                    ],
                ],
                [
                    'kicker_en' => '03 · PAYMENT & ON-DELIVERY',
                    'h_en' => 'Payment methods accepted.',
                    'h_ar' => 'وسائل الدفع المقبولة.',
                    'body' => [
                        'Mada · Apple Pay · STC Pay · Tabby (split into 4) · all major credit cards via the secure payment gateway.',
                        'الدفع عند الاستلام: غير متوفر حالياً.',
                    ],
                ],
                [
                    'kicker_en' => '04 · TRACKING & ISSUES',
                    'h_en' => 'After it ships.',
                    'h_ar' => 'بعد الشحن.',
                    'body' => [
                        'You receive a tracking number by email and on your order page. For shipping issues, contact us via the channels listed below — we respond within one business day.',
                        sprintf('Mobile: %s · Email: %s', $cr['phone_mobile'], $cr['email']),
                    ],
                ],
            ],
        ],

        'returns' => [
            'kicker'   => '04 · RETURNS',
            'h1_en'    => 'RETURNS & REFUNDS',
            'h1_ar'    => 'سياسة الاسترجاع والاسترداد',
            'lede_en'  => 'This policy sets out the conditions under which orders placed on novakeys.store may be returned and refunded. It tracks the statutory baseline under the KSA E-Commerce Law and the Consumer Protection regime, with extensions specific to NovaKeys\'s digital-goods catalogue.',
            'lede_ar'  => 'توضح هذه السياسة شروط استرجاع الطلبات على novakeys.store واسترداد قيمتها. وتنطلق من الحد الأدنى الذي يقرّره نظام التجارة الإلكترونية ونظام حماية المستهلك في المملكة العربية السعودية، مع إضافات مخصّصة لكتالوج المنتجات الرقمية في نيوجين ستور.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · STATUTORY BASELINE',
                    'h_en' => 'Your default rights under KSA E-Commerce Law.',
                    'h_ar' => 'الحقوق الافتراضية بموجب نظام التجارة الإلكترونية السعودي.',
                    'body' => [
                        'Saudi E-Commerce Law (نظام التجارة الإلكترونية, Royal Decree M/126) gives you a default right to return distance-purchased goods within seven (7) days of delivery, provided the item is unused, unopened where applicable, and in its original condition and packaging.',
                        'Certain categories may be excluded by law: digital downloads or activation keys after redemption, software with broken seals, perishable goods, and items personalized to your specifications.',
                    ],
                ],
                [
                    'kicker_en' => '02 · STORE-SPECIFIC TERMS',
                    'h_en' => 'NovaKeys Store extensions for digital goods.',
                    'h_ar' => 'تمديدات نيوجين ستور للمنتجات الرقمية.',
                    'body' => [
                        '<strong>14-day extended window for unredeemed digital codes.</strong> Where a digital code (gift-card, software-key, voucher) has not yet been revealed or activated by you, you may request a refund within fourteen (14) days of delivery. Send the request to ' . esc_html( $cr['email'] ) . ' with your order number; we will verify on the issuing platform that the code remains unused before authorising the refund.',
                        '<strong>No refund once the code is revealed, copied, or redeemed.</strong> Per the digital-goods exception in KSA E-Commerce Law and the platform terms of the underlying issuer (Apple, PlayStation, Steam, Microsoft, etc.), a code\'s value is consumed at the moment of revelation. Once the code has been displayed in your <em>My Account → Gift Card Keys</em> page, copied, or used, it is non-refundable.',
                        '<strong>Wrong-region purchases.</strong> Region-locked codes (for example a US Apple Gift Card vs a Saudi Apple Gift Card) are non-refundable once revealed. Please confirm the region selector on the product page before placing the order. If you reveal a wrong-region code, contact us — we may, at our discretion and where the issuing platform allows, swap to the correct region against a small administrative fee, but we are not obliged to.',
                        '<strong>Refund channel and timing.</strong> Approved refunds are returned via the original payment method (Mada, Apple Pay, STC Pay, Tabby, or card). The refund is processed within three (3) business days of approval; your bank or card issuer may take up to ten (10) further business days to credit the amount.',
                        '<strong>Order cancellation before delivery.</strong> Orders may be cancelled at no charge any time before the code is delivered to your account. Once the code lands in your account, the rules above apply.',
                        '<div dir="rtl" lang="ar"><strong>مدة 14 يومًا للأكواد الرقمية غير المُستخدَمة.</strong> إذا لم تكشف عن الكود الرقمي (بطاقة هدية، مفتاح برمجي، قسيمة) أو تُفعّله، يحق لك طلب استرداد قيمته خلال أربعة عشر (14) يومًا من تاريخ تسليمه. أرسل الطلب إلى ' . esc_html( $cr['email'] ) . ' مع رقم الطلب، وسنتحقق من المنصة المُصدِرة من أن الكود لا يزال غير مُستخدَم قبل اعتماد الاسترداد.</div>',
                        '<div dir="rtl" lang="ar"><strong>لا استرداد بعد كشف الكود أو نسخه أو استخدامه.</strong> وفقًا لاستثناء المنتجات الرقمية في نظام التجارة الإلكترونية، ولشروط منصات الإصدار (آبل، بلاي ستيشن، ستيم، مايكروسوفت، وغيرها)، تُستهلك قيمة الكود عند لحظة كشفه. بمجرد ظهور الكود في صفحة <em>حسابي ← بطاقاتي</em>، أو نسخه، أو استخدامه، يصبح غير قابل للاسترداد.</div>',
                        '<div dir="rtl" lang="ar"><strong>شراء بمنطقة خاطئة.</strong> الأكواد المرتبطة بمنطقة محددة (كبطاقة آبل أمريكية مقابل بطاقة آبل سعودية) غير قابلة للاسترداد بعد كشفها. يُرجى التأكد من اختيار المنطقة الصحيحة على صفحة المنتج قبل الطلب. في حال كشف كود بمنطقة خاطئة تواصل معنا، وقد نتمكّن من استبداله بمنطقة صحيحة بحسب سياسة المنصة المُصدِرة وبرسوم إدارية، دون أن يكون ذلك إلزامًا علينا.</div>',
                        '<div dir="rtl" lang="ar"><strong>قناة وزمن الاسترداد.</strong> يُعاد المبلغ عبر وسيلة الدفع الأصلية (مدى، Apple Pay، STC Pay، تابي، أو البطاقة الائتمانية). تتم معالجة الاسترداد خلال (3) أيام عمل من اعتماده، وقد يحتاج البنك أو مُصدِر البطاقة إلى (10) أيام عمل إضافية لإيداع المبلغ.</div>',
                        '<div dir="rtl" lang="ar"><strong>إلغاء الطلب قبل التسليم.</strong> يمكن إلغاء الطلب دون رسوم في أي وقت قبل تسليم الكود إلى حسابك. بعد وصول الكود إلى حسابك تسري الأحكام أعلاه.</div>',
                    ],
                ],
            ],
        ],

        'warranty' => [
            'kicker'   => '05 · WARRANTY',
            'h1_en'    => 'WARRANTY POLICY',
            'h1_ar'    => 'سياسة الضمان',
            'lede_en'  => 'This policy describes the warranty NovaKeys Store provides on the digital codes it sells, the statutory protection that applies in addition, and the procedure for raising a warranty claim.',
            'lede_ar'  => 'توضّح هذه السياسة الضمان الذي يقدّمه نيوجين ستور على الأكواد الرقمية التي يبيعها، والحماية النظامية المطبَّقة إضافةً إليه، وإجراءات تقديم طلب ضمان.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · STATUTORY MINIMUM',
                    'h_en' => 'Defect protection by law.',
                    'h_ar' => 'الحماية من العيوب بحكم النظام.',
                    'body' => [
                        'Under the Saudi Consumer Protection regime, the seller is responsible for hidden defects (عيب خفي) that materially affect the product\'s value or fitness for purpose, regardless of any manufacturer warranty.',
                        'This statutory protection runs in parallel with — not instead of — the manufacturer\'s commercial warranty.',
                    ],
                ],
                [
                    'kicker_en' => '02 · MANUFACTURER WARRANTY',
                    'h_en' => 'Pass-through coverage.',
                    'h_ar' => 'الضمان المُقدَّم من المصنّع.',
                    'body' => [
                        'Each product page lists the manufacturer warranty period the unit ships with (e.g., 12 months on most networking equipment). Claims under manufacturer warranty are processed through our service desk and routed to the relevant brand.',
                    ],
                ],
                [
                    'kicker_en' => '03 · CLAIM PROCEDURE',
                    'h_en' => 'How to open a warranty claim.',
                    'h_ar' => 'كيفية فتح طلب ضمان.',
                    'body' => [
                        '<strong>Where the code does not work as advertised</strong> — i.e. the issuing platform reports it as invalid, already redeemed, or expired before the printed expiry — open a claim within seven (7) days of the failure by emailing ' . esc_html( $cr['email'] ) . ' with your order number, the code as it appears in <em>My Account → Gift Card Keys</em>, and a screenshot of the platform error.',
                        '<strong>Verification.</strong> We verify the code\'s status against the issuing platform within two (2) business days. If we confirm the failure was on our side or the supplier\'s, we proceed to remedy.',
                        '<strong>Remedy.</strong> Our default remedy is replacement with an equivalent unused code. Where replacement is not feasible (for example the platform has discontinued the denomination), we issue a full refund to the original payment method.',
                        '<strong>Out-of-scope claims.</strong> A code lost by you, redeemed against the wrong account, or shared with a third party is outside warranty cover. The encryption-at-rest of codes in our system is described in the privacy policy.',
                        sprintf( '<strong>Contact.</strong> Email %s · Phone %s · Operating hours Sun–Thu 09:00–17:00 Asia/Riyadh.', esc_html( $cr['email'] ), esc_html( $cr['phone_mobile'] ) ),
                        '<div dir="rtl" lang="ar"><strong>إذا لم يعمل الكود كما هو موصوف</strong> — أي رفضت المنصة المُصدِرة الكودَ بصفته غير صالح، أو مُستخدَمًا، أو منتهي الصلاحية قبل التاريخ المعلن — افتح طلب ضمان خلال سبعة (7) أيام من تاريخ الفشل بإرسال بريد إلى ' . esc_html( $cr['email'] ) . ' مع رقم الطلب، والكود كما يظهر في <em>حسابي ← بطاقاتي</em>، ولقطة شاشة لرسالة الخطأ من المنصة.</div>',
                        '<div dir="rtl" lang="ar"><strong>التحقق.</strong> نتحقق من حالة الكود لدى المنصة المُصدِرة خلال يومَي عمل (2)، فإن ثبت أن المشكلة من جانبنا أو من المورد بدأنا بالمعالجة.</div>',
                        '<div dir="rtl" lang="ar"><strong>المعالجة.</strong> المعالجة الافتراضية هي استبدال الكود بآخر مكافئ غير مُستخدَم. إن تعذّر الاستبدال (مثلًا أوقفت المنصة هذه الفئة) يُعاد المبلغ كاملًا إلى وسيلة الدفع الأصلية.</div>',
                        '<div dir="rtl" lang="ar"><strong>خارج نطاق الضمان.</strong> الكود الذي تفقده أو تستخدمه على حساب خاطئ أو تشاركه مع طرف ثالث خارج نطاق الضمان. يُشار إلى تشفير الأكواد لدينا في سياسة الخصوصية.</div>',
                        sprintf( '<div dir="rtl" lang="ar"><strong>التواصل.</strong> البريد %s · الجوال %s · ساعات العمل الأحد–الخميس 09:00–17:00 بتوقيت آسيا/الرياض.</div>', esc_html( $cr['email'] ), esc_html( $cr['phone_mobile'] ) ),
                    ],
                ],
            ],
        ],

        'terms' => [
            'kicker'   => '06 · TERMS',
            'h1_en'    => 'TERMS & CONDITIONS',
            'h1_ar'    => 'الشروط والأحكام',
            'lede_en'  => 'These Terms & Conditions govern the commercial relationship between you (the customer) and NovaKeys Store, the KSA-registered sole proprietorship (CR 7053130576) operating on behalf of NeoTechnology Solutions LLC (Wyoming, USA), when you use novakeys.store. They are written to comply with the KSA E-Commerce Law and the Consumer Protection regime.',
            'lede_ar'  => 'تحكم هذه الشروط والأحكام العلاقة التجارية بينك (العميل) وبين نيوجين ستور — المؤسسة الفردية المُسجَّلة في المملكة العربية السعودية (سجل تجاري 7053130576)، التي تعمل بالنيابة عن شركة NeoTechnology Solutions LLC المُسجَّلة في ولاية وايومنغ بالولايات المتحدة، وذلك عند استخدامك novakeys.store. وقد صيغت بما يتوافق مع نظام التجارة الإلكترونية ونظام حماية المستهلك في المملكة العربية السعودية.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · ACCEPTANCE',
                    'h_en'      => 'Acceptance of terms.',
                    'h_ar'      => 'قبول الشروط.',
                    'body'      => [
                        'By creating an account, placing an order, or browsing novakeys.store after the publication date of these Terms, you accept them in full and acknowledge that they form a binding agreement. If you do not accept any clause, do not use the site.',
                        '<div dir="rtl" lang="ar">بإنشائك حسابًا أو تقديمك طلبًا أو تصفّحك novakeys.store بعد تاريخ نشر هذه الشروط، فإنك توافق عليها بالكامل وتُقرّ بأنها تشكّل اتفاقية ملزمة. وإن لم تقبل أيًّا من بنودها فلا تستخدم الموقع.</div>',
                    ],
                ],
                [
                    'kicker_en' => '02 · ACCOUNTS',
                    'h_en'      => 'Customer accounts.',
                    'h_ar'      => 'حسابات العملاء.',
                    'body'      => [
                        '<strong>Eligibility.</strong> You must be at least eighteen (18) years old, or a younger person acting with the consent of a guardian, to create an account.',
                        '<strong>Account integrity.</strong> You are responsible for keeping your login credentials confidential and for every order placed under your account. Notify us immediately at ' . esc_html( $cr['email'] ) . ' if you suspect unauthorised access.',
                        '<strong>Accuracy.</strong> Information you provide (name, contact, address, payment) must be accurate and current. Inaccurate information may result in order cancellation without refund of any expended payment-processor fees.',
                        '<strong>Termination.</strong> You may close your account at any time by contacting support. We may suspend or close accounts that violate these Terms or the Acceptable Use Policy. Outstanding gift-card codes are honoured for the statutory minimum period after closure.',
                        '<div dir="rtl" lang="ar"><strong>الأهلية.</strong> يجب أن تكون قد بلغت ثمانية عشر (18) سنة، أو أن تتصرّف بإذن وليّ أمر، لإنشاء الحساب.</div>',
                        '<div dir="rtl" lang="ar"><strong>سلامة الحساب.</strong> أنت المسؤول عن سرية بيانات الدخول وعن كل طلب يُقدَّم من حسابك. أبلغنا فورًا على ' . esc_html( $cr['email'] ) . ' إذا اشتبهت بوصول غير مُصرَّح به.</div>',
                        '<div dir="rtl" lang="ar"><strong>دقة البيانات.</strong> يجب أن تكون البيانات التي تُدخلها (الاسم، التواصل، العنوان، الدفع) دقيقة ومحدَّثة. قد يترتّب على عدم الدقة إلغاء الطلب دون استرداد رسوم بوابة الدفع المُستحقَّة.</div>',
                        '<div dir="rtl" lang="ar"><strong>إنهاء الحساب.</strong> يمكنك إغلاق حسابك بالتواصل مع الدعم. ولنا أن نُعلّق أو نُغلق الحسابات التي تخالف هذه الشروط أو سياسة الاستخدام. تظل الأكواد المُسلَّمة سارية للحدّ الأدنى النظامي بعد الإغلاق.</div>',
                    ],
                ],
                [
                    'kicker_en' => '03 · ORDERS · PRICING',
                    'h_en'      => 'Orders, pricing, and payment.',
                    'h_ar'      => 'الطلبات والأسعار والدفع.',
                    'body'      => [
                        '<strong>Currency.</strong> All prices are listed in Saudi Riyals (SAR) and <strong>include 15% VAT</strong> as required by KSA tax law. Foreign-currency conversions are made by your card issuer.',
                        '<strong>Order acceptance.</strong> Placing an order is an offer. The contract is concluded when we send the order-confirmation email <strong>and</strong> the gift-card code is delivered to your <em>My Account → Gift Card Keys</em> page. We reserve the right to decline an order — for example where stock from the upstream supplier is unavailable, where the order trips fraud-check rules, or where the price was clearly mis-stated.',
                        '<strong>Pricing.</strong> We make reasonable efforts to ensure listed prices are accurate. If a clear pricing error is detected before delivery, we will contact you with the corrected price; you may proceed at the corrected price or cancel for a full refund.',
                        '<strong>Payment methods.</strong> Mada, Apple Pay, STC Pay, Tabby, and major credit/debit cards. All payment processing is performed by the relevant gateway; we do not store full card numbers.',
                        '<strong>Tax invoices.</strong> A tax invoice (فاتورة ضريبية) compliant with ZATCA Phase-2 e-invoicing requirements is issued for every paid order.',
                        '<div dir="rtl" lang="ar"><strong>العملة.</strong> جميع الأسعار بالريال السعودي (SAR) <strong>وتشمل ضريبة القيمة المضافة 15%</strong> بموجب نظام الضرائب السعودي. تُجري بنوك العملاء أي تحويل عُملة لازم.</div>',
                        '<div dir="rtl" lang="ar"><strong>قبول الطلب.</strong> تقديم الطلب يُعدّ إيجابًا. ويُبرَم العقد عند إرسالنا بريد تأكيد الطلب <strong>و</strong> تسليم كود البطاقة إلى صفحتك في <em>حسابي ← بطاقاتي</em>. ولنا الحق في رفض الطلب — مثلًا عند نفاد المخزون لدى المورد، أو إثارته لقواعد التحقق من الاحتيال، أو وجود خطأ سعري واضح.</div>',
                        '<div dir="rtl" lang="ar"><strong>الأسعار.</strong> نبذل جهودًا معقولة لضمان دقة الأسعار. وإذا اكتُشف خطأ سعري واضح قبل التسليم سنتواصل معك بالسعر المُصحَّح، ولك الخيار بين المتابعة بالسعر الجديد أو إلغاء الطلب واسترداد قيمته كاملًا.</div>',
                        '<div dir="rtl" lang="ar"><strong>وسائل الدفع.</strong> مدى، Apple Pay، STC Pay، تابي، وبطاقات الائتمان والخصم الرئيسية. تُعالَج المدفوعات عبر بوابة الدفع المعنية ولا نُخزّن أرقام البطاقات الكاملة.</div>',
                        '<div dir="rtl" lang="ar"><strong>الفاتورة الضريبية.</strong> تصدر فاتورة ضريبية متوافقة مع متطلبات الفوترة الإلكترونية للمرحلة الثانية لدى هيئة الزكاة والضريبة والجمارك (ZATCA) عن كل طلب مدفوع.</div>',
                    ],
                ],
                [
                    'kicker_en' => '04 · IP',
                    'h_en'      => 'Intellectual property.',
                    'h_ar'      => 'الملكية الفكرية.',
                    'body'      => [
                        'The "NovaKeys" / "نيوجين ستور" name, the NovaKeys logo, the brand-token / design system used on novakeys.store, and the underlying software platform are the property of <strong>NeoTechnology Solutions LLC</strong> (Wyoming, USA — Articles of Organization filing 2025-001744917, EIN 36-5148912), and are licensed to NovaKeys Store (CR 7053130576) for use in connection with the KSA storefront. Brand marks of third parties displayed on our gift-card products (Apple, PlayStation, Steam, Microsoft, etc.) are the property of their respective owners and appear on this site solely to identify the redemption platform — no endorsement is implied. You may not copy, modify, or redistribute site content for commercial use without prior written permission from NeoTechnology Solutions LLC.',
                        '<div dir="rtl" lang="ar">اسم «NovaKeys» / «نيوجين ستور»، وشعار نيوجين، ومنظومة الهوية والتصميم على novakeys.store، والمنصّة البرمجية الأساسية، جميعها ملك لشركة <strong>NeoTechnology Solutions LLC</strong> (وايومنغ، الولايات المتحدة — رقم قيد التأسيس 2025-001744917، الرقم الضريبي الفيدرالي EIN 36-5148912)، ومُرخَّصة لنيوجين ستور (سجل تجاري 7053130576) لاستخدامها في تشغيل المتجر السعودي. وعلامات الأطراف الأخرى الظاهرة على منتجات بطاقات الهدايا (آبل، بلاي ستيشن، ستيم، مايكروسوفت وغيرها) ملك لأصحابها، وتُعرَض على الموقع لتعريف منصّة الاستخدام فقط ولا تعني أي رعاية أو تأييد. لا يجوز نسخ محتوى الموقع أو تعديله أو إعادة توزيعه لأغراض تجارية دون إذن كتابي مسبق من NeoTechnology Solutions LLC.</div>',
                    ],
                ],
                [
                    'kicker_en' => '05 · LIABILITY',
                    'h_en'      => 'Limitation of liability.',
                    'h_ar'      => 'تحديد المسؤولية.',
                    'body'      => [
                        'Subject to mandatory consumer-protection rules that cannot be excluded under KSA law, our maximum aggregate liability for any single order is limited to the price you actually paid for that order, including VAT. We are not liable for indirect or consequential losses (loss of profit, lost data, lost opportunity) arising from use of the site or any product purchased through it. Nothing in this clause excludes liability for fraud, gross negligence, or anything else that cannot be excluded under KSA law.',
                        '<div dir="rtl" lang="ar">مع مراعاة قواعد حماية المستهلك الإلزامية التي لا يجوز استبعادها بموجب الأنظمة السعودية، تكون مسؤوليتنا التراكمية القصوى عن أي طلب محصورةً في السعر الفعلي الذي دفعته عن ذلك الطلب شاملًا الضريبة. ولا نُسأل عن الأضرار غير المباشرة أو التبعية (فقد الأرباح، ضياع البيانات، ضياع الفرصة) الناشئة عن استخدام الموقع أو أيٍّ من منتجاته. ولا يستثني هذا البند المسؤوليةَ عن الغش أو الإهمال الجسيم أو ما لا يجوز استبعاده نظامًا.</div>',
                    ],
                ],
                [
                    'kicker_en' => '06 · GOVERNING LAW',
                    'h_en'      => 'Governing law and disputes.',
                    'h_ar'      => 'القانون الواجب التطبيق وفض النزاعات.',
                    'body'      => [
                        'These Terms are governed by the laws of the Kingdom of Saudi Arabia. Disputes that cannot be resolved through our customer-support channel are referred to the competent commercial court of the Kingdom of Saudi Arabia, and to the e-commerce dispute mechanism operated by the Ministry of Commerce where applicable.',
                        '<div dir="rtl" lang="ar">تخضع هذه الشروط لأنظمة المملكة العربية السعودية. وتُحال النزاعات التي يتعذّر حلّها عبر قناة دعم العملاء إلى المحكمة التجارية المختصّة بالمملكة العربية السعودية، وإلى آلية تسوية نزاعات التجارة الإلكترونية لدى وزارة التجارة عند الاقتضاء.</div>',
                    ],
                ],
                [
                    'kicker_en' => '07 · CHANGES',
                    'h_en'      => 'Changes to these terms.',
                    'h_ar'      => 'تعديل الشروط.',
                    'body'      => [
                        'We may update these Terms from time to time to reflect changes in the law, our offering, or our processes. The effective date appears at the top of the document. Material changes are announced via an in-account notice and an email to your registered address at least seven (7) days before they take effect. Continued use of the site after the effective date constitutes acceptance of the updated Terms.',
                        '<div dir="rtl" lang="ar">قد نُحدّث هذه الشروط من حينٍ لآخر لتعكس تغيّرات الأنظمة أو خدماتنا أو إجراءاتنا. ويظهر تاريخ السريان في رأس الوثيقة. ويُعلَن عن التغييرات الجوهرية عبر إشعار داخل الحساب وبريد إلى عنوانك المسجَّل قبل سريانها بسبعة (7) أيام على الأقل. ويُعدّ استمرارك في استخدام الموقع بعد تاريخ السريان قبولًا للشروط المُحدَّثة.</div>',
                    ],
                ],
            ],
        ],

        'privacy' => [
            'kicker'   => '07 · PRIVACY',
            'h1_en'    => 'PRIVACY POLICY',
            'h1_ar'    => 'سياسة الخصوصية',
            'lede_en'  => 'This policy explains what personal data NovaKeys Store (the data controller — KSA sole proprietorship, CR 7053130576) collects, why, on what legal basis, with whom we share it (including with our parent NeoTechnology Solutions LLC of Wyoming, USA, acting as a data processor for engineering and infrastructure), and the rights you have under the KSA Personal Data Protection Law (PDPL, Royal Decree M/19, in full effect from September 2024).',
            'lede_ar'  => 'تشرح هذه السياسة البيانات الشخصية التي يجمعها نيوجين ستور (جهة التحكّم في البيانات — مؤسسة فردية سعودية، سجل تجاري 7053130576)، وأغراض الجمع، وأسسه النظامية، والجهات التي نشاركها معها (بما في ذلك الشركة الأم NeoTechnology Solutions LLC في ولاية وايومنغ بالولايات المتحدة، بصفتها جهة معالجة للبيانات لأغراض الهندسة والبنية التحتية)، وحقوقك بموجب نظام حماية البيانات الشخصية السعودي (PDPL، المرسوم الملكي م/19، النافذ بالكامل من سبتمبر 2024).',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · CONTROLLER',
                    'h_en'      => 'Who controls your data.',
                    'h_ar'      => 'الجهة المتحكمة في بياناتك.',
                    'body'      => [
                        sprintf( 'Data controller: %s (CR %s).', esc_html( $cr['legal_name_en'] ), esc_html( $cr['cr'] ) ),
                        sprintf( 'Contact: %s · %s.', esc_html( $cr['email'] ), esc_html( $cr['phone_mobile'] ) ),
                    ],
                ],
                [
                    'kicker_en' => '02 · DATA WE COLLECT',
                    'h_en'      => 'Categories of personal data.',
                    'h_ar'      => 'فئات البيانات الشخصية.',
                    'body'      => [
                        '<strong>Identity</strong> — name, billing/shipping address, phone, date of birth (only when legally required for purchase verification).',
                        '<strong>Contact</strong> — email address, phone number, optional WhatsApp handle.',
                        '<strong>Order data</strong> — products purchased, prices, payment method (we never store full card numbers; the gateway tokenises), redemption-region selection.',
                        '<strong>Encrypted gift-card codes</strong> — codes you have purchased, stored at-rest with AES-256-GCM encryption; only readable by you when logged in.',
                        '<strong>Device &amp; usage</strong> — IP address, browser user-agent, device type, referring page, pages viewed (cookies, see Section 08).',
                        '<strong>Customer-support correspondence</strong> — messages you send to ' . esc_html( $cr['email'] ) . ' and our replies.',
                        '<div dir="rtl" lang="ar"><strong>بيانات الهوية</strong> — الاسم، عنوان الفوترة/الشحن، رقم الهاتف، تاريخ الميلاد (عند طلب نظامي للتحقق من الشراء فقط).</div>',
                        '<div dir="rtl" lang="ar"><strong>بيانات التواصل</strong> — البريد الإلكتروني، رقم الهاتف، حساب واتساب اختياريًّا.</div>',
                        '<div dir="rtl" lang="ar"><strong>بيانات الطلب</strong> — المنتجات المشتراة، الأسعار، وسيلة الدفع (لا نُخزّن أرقام البطاقات الكاملة؛ تُرمَّز عبر بوابة الدفع)، اختيار منطقة الاستخدام.</div>',
                        '<div dir="rtl" lang="ar"><strong>أكواد البطاقات المُشفَّرة</strong> — الأكواد التي اشتريتها، مُخزَّنة باستخدام تشفير AES-256-GCM؛ ولا يقرأها سواك بعد دخولك إلى حسابك.</div>',
                        '<div dir="rtl" lang="ar"><strong>بيانات الجهاز والاستخدام</strong> — عنوان IP، وكيل المتصفح، نوع الجهاز، الصفحة المُحيلة، الصفحات التي زرتها (ملفات الارتباط، انظر القسم 08).</div>',
                        '<div dir="rtl" lang="ar"><strong>مراسلات الدعم</strong> — الرسائل التي ترسلها إلى ' . esc_html( $cr['email'] ) . ' وردودنا عليها.</div>',
                    ],
                ],
                [
                    'kicker_en' => '03 · PURPOSES',
                    'h_en'      => 'Why we process it.',
                    'h_ar'      => 'أغراض المعالجة.',
                    'body'      => [
                        'We process your personal data to:',
                        '<strong>Fulfil your order</strong> — supply the gift-card code to your account and issue the tax invoice.',
                        '<strong>Provide customer support</strong> — answer questions, resolve warranty claims, troubleshoot redemption errors.',
                        '<strong>Comply with our regulatory obligations</strong> — Commercial Registration record-keeping, ZATCA tax invoicing, Anti-Money-Laundering checks where applicable.',
                        '<strong>Detect and prevent fraud</strong> — flag suspicious purchase patterns, throttle abusive endpoint calls, block known fraud actors.',
                        '<strong>Improve the storefront</strong> — anonymous analytics on what products are viewed, what searches return no results, where customers drop out of checkout.',
                        '<strong>Communicate marketing</strong> — only when you have explicitly opted in, and only until you withdraw consent.',
                        '<div dir="rtl" lang="ar">نُعالج بياناتك الشخصية للأغراض التالية:</div>',
                        '<div dir="rtl" lang="ar"><strong>تنفيذ طلبك</strong> — تسليم كود البطاقة إلى حسابك وإصدار الفاتورة الضريبية.</div>',
                        '<div dir="rtl" lang="ar"><strong>تقديم الدعم</strong> — الرد على الاستفسارات ومعالجة طلبات الضمان وحل مشاكل الاستخدام.</div>',
                        '<div dir="rtl" lang="ar"><strong>الالتزام بالأنظمة</strong> — حفظ سجلات السجل التجاري، الفوترة الإلكترونية لدى ZATCA، إجراءات مكافحة غسل الأموال عند الاقتضاء.</div>',
                        '<div dir="rtl" lang="ar"><strong>كشف الاحتيال ومنعه</strong> — رصد أنماط الشراء المشبوهة، تقييد الاستدعاءات المُسيئة لنقاط النهاية، حجب الأطراف المعروفة بالاحتيال.</div>',
                        '<div dir="rtl" lang="ar"><strong>تحسين المتجر</strong> — تحليلات مجهولة الهوية لما يُشاهد من منتجات وما تُرجِعه عمليات البحث الفارغة وأماكن خروج العملاء من الدفع.</div>',
                        '<div dir="rtl" lang="ar"><strong>التواصل التسويقي</strong> — فقط عند موافقتك الصريحة وحتى تسحبها.</div>',
                    ],
                ],
                [
                    'kicker_en' => '04 · LEGAL BASES',
                    'h_en'      => 'Lawful grounds under PDPL.',
                    'h_ar'      => 'الأسس النظامية بموجب PDPL.',
                    'body'      => [
                        'We rely on one or more of the following lawful grounds, per Article 6 of the PDPL:',
                        '<table class="nk-legal-table"><thead><tr><th>Purpose group</th><th>Lawful ground</th></tr></thead><tbody><tr><td>Order fulfilment, account management</td><td>Performance of a contract</td></tr><tr><td>Tax invoicing, CR record-keeping, AML</td><td>Compliance with a legal obligation</td></tr><tr><td>Fraud detection, abuse rate-limiting</td><td>Legitimate interest</td></tr><tr><td>Marketing communications</td><td>Explicit consent</td></tr></tbody></table>',
                        '<div dir="rtl" lang="ar">نستند إلى واحد أو أكثر من الأسس النظامية التالية وفق المادة 6 من PDPL:</div>',
                        '<div dir="rtl" lang="ar"><table class="nk-legal-table"><thead><tr><th>غرض المعالجة</th><th>الأساس النظامي</th></tr></thead><tbody><tr><td>تنفيذ الطلب وإدارة الحساب</td><td>تنفيذ عقد</td></tr><tr><td>الفوترة الضريبية وسجلات السجل التجاري ومكافحة غسل الأموال</td><td>الالتزام بنظام</td></tr><tr><td>كشف الاحتيال وتقييد الاستخدام المُسيء</td><td>المصلحة المشروعة</td></tr><tr><td>الرسائل التسويقية</td><td>الموافقة الصريحة</td></tr></tbody></table></div>',
                    ],
                ],
                [
                    'kicker_en' => '05 · SHARING',
                    'h_en'      => 'Who we share with.',
                    'h_ar'      => 'الجهات التي نشارك معها.',
                    'body'      => [
                        'We share your personal data only with the parties strictly necessary to operate the service:',
                        '<strong>NeoTechnology Solutions LLC (US parent — data processor).</strong> Engineers, hosts, and maintains the novakeys.store platform on behalf of the KSA controller. NTS LLC accesses personal data only as necessary for platform operation, governed by an internal data-processing agreement that follows PDPL processor obligations.',
                        '<strong>Payment gateways</strong> — Mada / SAMA-licensed acquirers, Apple Pay, STC Pay, Tabby, and your card issuer. They receive only the data each needs to authorise the transaction.',
                        '<strong>Hosting and infrastructure</strong> — our website hosting provider and CDN. Data is processed inside their infrastructure to deliver the page you requested.',
                        '<strong>Customer-support tooling</strong> — the email service that handles ' . esc_html( $cr['email'] ) . ' correspondence.',
                        '<strong>Tax authority</strong> — ZATCA receives the e-invoice for every paid order in compliance with Phase-2 e-invoicing.',
                        '<strong>Regulators and courts</strong> — when required by law (Ministry of Commerce, the Saudi Data &amp; AI Authority, competent courts).',
                        'We <strong>do not sell</strong> personal data and we <strong>do not share</strong> it with marketing partners outside your consented marketing communications.',
                        '<div dir="rtl" lang="ar">لا نُشارك بياناتك الشخصية إلا مع الجهات اللازمة لتشغيل الخدمة:</div>',
                        '<div dir="rtl" lang="ar"><strong>NeoTechnology Solutions LLC (الشركة الأم في الولايات المتحدة — جهة معالجة).</strong> تتولّى الهندسة والاستضافة والصيانة لمنصّة novakeys.store بالنيابة عن جهة التحكّم السعودية. لا يصل NTS LLC إلى البيانات الشخصية إلا بالقدر اللازم لتشغيل المنصّة، بموجب اتفاقية معالجة بيانات داخلية تتوافق مع التزامات جهة المعالجة في PDPL.</div>',
                        '<div dir="rtl" lang="ar"><strong>بوابات الدفع</strong> — مدى ومُستحوذي SAMA المرخَّصين، وApple Pay، وSTC Pay، وتابي، ومُصدِر بطاقتك. تتلقى كلّ جهة فقط ما يلزمها لاعتماد المعاملة.</div>',
                        '<div dir="rtl" lang="ar"><strong>الاستضافة والبنية التحتية</strong> — مزوّد الاستضافة وشبكة توصيل المحتوى. تُعالَج البيانات داخل بنيتهم لتسليم الصفحة المطلوبة.</div>',
                        '<div dir="rtl" lang="ar"><strong>أدوات دعم العملاء</strong> — خدمة البريد التي تُدير مراسلات ' . esc_html( $cr['email'] ) . '.</div>',
                        '<div dir="rtl" lang="ar"><strong>الجهة الضريبية</strong> — تستلم ZATCA الفاتورة الإلكترونية لكل طلب مدفوع التزامًا بالمرحلة الثانية للفوترة.</div>',
                        '<div dir="rtl" lang="ar"><strong>الجهات التنظيمية والقضائية</strong> — عند الاشتراط النظامي (وزارة التجارة، الهيئة السعودية للبيانات والذكاء الاصطناعي، المحاكم المختصّة).</div>',
                        '<div dir="rtl" lang="ar"><strong>لا نبيع</strong> البيانات الشخصية، <strong>ولا نُشاركها</strong> مع شركاء تسويق خارج نطاق رسائل التسويق التي وافقت عليها.</div>',
                    ],
                ],
                [
                    'kicker_en' => '06 · RETENTION',
                    'h_en'      => 'How long we keep data.',
                    'h_ar'      => 'مدة الاحتفاظ.',
                    'body'      => [
                        'We retain personal data only for as long as we need it for the purpose it was collected for, or as required by law:',
                        '<table class="nk-legal-table"><thead><tr><th>Category</th><th>Retention period</th></tr></thead><tbody><tr><td>Order records, tax invoices</td><td>Ten (10) years from the order date — minimum required by KSA tax/commercial-records law</td></tr><tr><td>Encrypted gift-card codes</td><td>Ten (10) years from the order date, alongside the order record</td></tr><tr><td>Customer-support correspondence</td><td>Five (5) years from the last reply</td></tr><tr><td>Marketing-consent record</td><td>Until consent is withdrawn, plus two (2) years for proof-of-consent</td></tr><tr><td>Server access logs</td><td>Ninety (90) days</td></tr></tbody></table>',
                        '<div dir="rtl" lang="ar">نحتفظ بالبيانات الشخصية للمدة اللازمة للغرض الذي جُمعت لأجله، أو حسب ما تتطلّبه الأنظمة:</div>',
                        '<div dir="rtl" lang="ar"><table class="nk-legal-table"><thead><tr><th>الفئة</th><th>مدة الاحتفاظ</th></tr></thead><tbody><tr><td>سجلات الطلب والفواتير الضريبية</td><td>عشر (10) سنوات من تاريخ الطلب — الحدّ الأدنى الذي تشترطه الأنظمة الضريبية والتجارية</td></tr><tr><td>الأكواد المُشفَّرة</td><td>عشر (10) سنوات من تاريخ الطلب مع سجل الطلب</td></tr><tr><td>مراسلات الدعم</td><td>خمس (5) سنوات من آخر ردّ</td></tr><tr><td>سجل الموافقة على التسويق</td><td>حتى سحب الموافقة، إضافةً إلى سنتين (2) لإثبات الموافقة</td></tr><tr><td>سجلات وصول الخادم</td><td>تسعون (90) يومًا</td></tr></tbody></table></div>',
                    ],
                ],
                [
                    'kicker_en' => '07 · YOUR RIGHTS',
                    'h_en'      => 'Data subject rights under PDPL.',
                    'h_ar'      => 'حقوقك بموجب PDPL.',
                    'body'      => [
                        'Under the PDPL you have the right to:',
                        '<strong>Be informed</strong> of what we collect and why (this policy).',
                        '<strong>Access</strong> a copy of the personal data we hold about you.',
                        '<strong>Correct</strong> inaccurate personal data.',
                        '<strong>Delete</strong> your personal data subject to our retention obligations.',
                        '<strong>Restrict or object</strong> to processing for marketing or legitimate-interest grounds.',
                        '<strong>Withdraw consent</strong> at any time, where consent is the legal basis.',
                        '<strong>Data portability</strong> — receive your data in a machine-readable format.',
                        'Exercise any right by emailing the data-controller contact above. We respond within thirty (30) days. Where we cannot fulfil a request — for example because of an overriding legal-retention obligation — we explain why.',
                        'If you believe we have not handled your request properly, you may complain to the Saudi Data &amp; AI Authority (SDAIA) — the PDPL supervisory authority.',
                        '<div dir="rtl" lang="ar">تكفل لك PDPL الحقوق التالية:</div>',
                        '<div dir="rtl" lang="ar"><strong>الإحاطة العلمية</strong> بما نجمع وبأغراض الجمع (هذه السياسة).</div>',
                        '<div dir="rtl" lang="ar"><strong>الاطلاع</strong> على نسخة من بياناتك الشخصية لدينا.</div>',
                        '<div dir="rtl" lang="ar"><strong>التصحيح</strong> للبيانات غير الدقيقة.</div>',
                        '<div dir="rtl" lang="ar"><strong>الحذف</strong> لبياناتك مع مراعاة التزاماتنا في الاحتفاظ.</div>',
                        '<div dir="rtl" lang="ar"><strong>تقييد المعالجة</strong> أو الاعتراض عليها لأغراض التسويق أو المصلحة المشروعة.</div>',
                        '<div dir="rtl" lang="ar"><strong>سحب الموافقة</strong> في أي وقت متى كانت الأساس النظامي.</div>',
                        '<div dir="rtl" lang="ar"><strong>نقل البيانات</strong> — استلام بياناتك بصيغة قابلة للمعالجة الآلية.</div>',
                        '<div dir="rtl" lang="ar">تُمارَس الحقوق بالتواصل مع جهة المراقبة عبر بيانات التواصل أعلاه. ونردّ خلال (30) يومًا. وإذا تعذّر الاستجابة — مثلًا بسبب التزام نظامي بالاحتفاظ — نُبيّن السبب.</div>',
                        '<div dir="rtl" lang="ar">وإن رأيت أننا لم نُحسن التعامل مع طلبك، يمكنك التقدّم بشكوى إلى الهيئة السعودية للبيانات والذكاء الاصطناعي (SDAIA) — الجهة الإشرافية على PDPL.</div>',
                    ],
                ],
                [
                    'kicker_en' => '08 · COOKIES',
                    'h_en'      => 'Cookies and tracking.',
                    'h_ar'      => 'ملفات الارتباط والتتبع.',
                    'body'      => [
                        'We use the following cookie categories:',
                        '<table class="nk-legal-table"><thead><tr><th>Category</th><th>Examples</th><th>Consent?</th></tr></thead><tbody><tr><td>Strictly necessary</td><td>session, cart, security tokens</td><td>No — required for the site to work</td></tr><tr><td>Functional</td><td>recently-viewed list (<code>ng_recent</code>), referral attribution (<code>nk_ref</code>)</td><td>No (legitimate interest)</td></tr><tr><td>Analytics</td><td>aggregated, anonymised page-view counters</td><td>Yes</td></tr><tr><td>Marketing</td><td>retargeting pixels, conversion tags</td><td>Yes</td></tr></tbody></table>',
                        'A consent banner is displayed on first visit to capture analytics and marketing consent. You can change your preferences at any time from the <em>Cookies</em> link in the footer.',
                        '<div dir="rtl" lang="ar">نستخدم الفئات التالية من ملفات الارتباط:</div>',
                        '<div dir="rtl" lang="ar"><table class="nk-legal-table"><thead><tr><th>الفئة</th><th>أمثلة</th><th>موافقة مطلوبة؟</th></tr></thead><tbody><tr><td>ضرورية</td><td>الجلسة، السلة، رموز الأمان</td><td>لا — لازمة لعمل الموقع</td></tr><tr><td>وظيفية</td><td>قائمة المشاهَد مؤخرًا (<code>ng_recent</code>)، إحالة الدعوة (<code>nk_ref</code>)</td><td>لا (مصلحة مشروعة)</td></tr><tr><td>تحليلية</td><td>عدّادات صفحات مجهولة الهوية</td><td>نعم</td></tr><tr><td>تسويقية</td><td>بكسلات إعادة الاستهداف، علامات التحويل</td><td>نعم</td></tr></tbody></table></div>',
                        '<div dir="rtl" lang="ar">يُعرض شريط موافقة عند أول زيارة لاتخاذ موافقتك على فئتي التحليل والتسويق. ويمكنك تعديل تفضيلاتك في أي وقت من رابط <em>ملفات الارتباط</em> في تذييل الموقع.</div>',
                    ],
                ],
                [
                    'kicker_en' => '09 · CROSS-BORDER',
                    'h_en'      => 'International data transfers.',
                    'h_ar'      => 'نقل البيانات خارج المملكة.',
                    'body'      => [
                        'Personal data flows from the KSA controller to <strong>NeoTechnology Solutions LLC in Wyoming, USA</strong> for platform engineering, hosting, and security operations. Some additional service providers (payment-gateway tokenisation, email service, CDN edge nodes) also host data in jurisdictions outside the Kingdom of Saudi Arabia. Under PDPL Article 29, cross-border transfers are restricted; we rely on:',
                        '<strong>Adequacy.</strong> Transfers to jurisdictions recognised by SDAIA as offering adequate protection, where applicable.',
                        '<strong>Binding contractual safeguards</strong> with the recipient (data-processing agreement) where adequacy is not established — this is the basis for the KSA → NTS LLC (Wyoming) flow until SDAIA publishes a US adequacy decision.',
                        '<strong>Explicit consent</strong> where neither (1) nor (2) is available.',
                        '<div dir="rtl" lang="ar">تنتقل البيانات الشخصية من جهة التحكّم السعودية إلى <strong>NeoTechnology Solutions LLC في ولاية وايومنغ بالولايات المتحدة</strong> لأغراض هندسة المنصّة واستضافتها وعمليات الأمن. كذلك يستضيف بعض موردي الخدمة الإضافيين (ترميز بوابات الدفع، خدمة البريد، عُقد شبكة توصيل المحتوى) البيانات خارج المملكة العربية السعودية. وبموجب المادة 29 من PDPL يُقيَّد النقل خارج الحدود؛ ونعتمد على:</div>',
                        '<div dir="rtl" lang="ar"><strong>الكفاية.</strong> النقل إلى دول تعترف لها SDAIA بحماية كافية حيثما ينطبق.</div>',
                        '<div dir="rtl" lang="ar"><strong>ضمانات تعاقدية ملزِمة</strong> مع المتلقّي (اتفاقية معالجة بيانات) حال عدم اعتماد الكفاية — وهذا هو الأساس لنقل البيانات من المملكة إلى NTS LLC (وايومنغ) ريثما تصدر SDAIA قرار كفاية بشأن الولايات المتحدة.</div>',
                        '<div dir="rtl" lang="ar"><strong>الموافقة الصريحة</strong> عند عدم توفّر الخيارَين (1) و(2).</div>',
                    ],
                ],
            ],
        ],

        'usage' => [
            'kicker'   => '09 · USAGE',
            'h1_en'    => 'ACCEPTABLE USE POLICY',
            'h1_ar'    => 'سياسة الاستخدام',
            'lede_en'  => 'This policy sets the rules for using novakeys.store, your customer account, and the digital codes you purchase. It is binding alongside the Terms & Conditions and is enforceable under the KSA Anti-Cybercrime Law where conduct crosses the criminal threshold.',
            'lede_ar'  => 'تُحدّد هذه السياسة قواعد استخدام novakeys.store وحسابك العميل والأكواد الرقمية التي تشتريها. وهي مُلزِمة إلى جانب الشروط والأحكام، وتُنفَّذ بموجب نظام مكافحة جرائم المعلوماتية السعودي عند تجاوز السلوك حدّ التجريم.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · ACCEPTANCE',
                    'h_en'      => 'Using the site means accepting this policy.',
                    'h_ar'      => 'استخدام الموقع يعني قبول هذه السياسة.',
                    'body'      => [
                        'By accessing or using novakeys.store you agree to this Acceptable Use Policy. If you do not agree, do not use the site.',
                        '<div dir="rtl" lang="ar">بدخولك إلى novakeys.store أو استخدامه فإنك توافق على سياسة الاستخدام هذه. وإن لم توافق فلا تستخدم الموقع.</div>',
                    ],
                ],
                [
                    'kicker_en' => '02 · PROHIBITED USES',
                    'h_en'      => 'What you must not do.',
                    'h_ar'      => 'ممنوعات الاستخدام.',
                    'body'      => [
                        'You may not, and you may not allow anyone acting on your behalf to:',
                        '<strong>Automate, scrape, or harvest</strong> content, prices, or stock data — including via crawlers, headless browsers, or AI agents — beyond what <code>robots.txt</code> explicitly allows.',
                        '<strong>Place fraudulent or fictitious orders</strong>, or use payment instruments you are not authorised to use.',
                        '<strong>Impersonate</strong> another person, business, or entity, or misrepresent your affiliation with one.',
                        '<strong>Circumvent</strong> access controls, rate limits, region locks, or fraud-detection systems.',
                        '<strong>Probe, scan, or test</strong> the security of the site without prior written permission, or disclose vulnerabilities outside our coordinated-disclosure channel (security@novakeys.store).',
                        '<strong>Upload or transmit</strong> malware, viruses, or any other malicious code.',
                        '<strong>Resell</strong> purchased gift-card codes outside the redemption channel intended by the issuer (most issuer terms prohibit secondary resale; you remain bound by their rules).',
                        '<strong>Infringe</strong> intellectual-property rights, including those of NeoTechnology Solutions LLC (which owns the NovaKeys brand and platform) and the gift-card issuers whose marks appear on our products.',
                        '<strong>Use the site to violate any KSA law</strong>, including the Anti-Cybercrime Law (نظام مكافحة جرائم المعلوماتية), the Anti-Money-Laundering Law, or sanctions law.',
                        '<div dir="rtl" lang="ar">يُحظَر عليك ويُحظَر على من يتصرّف نيابةً عنك:</div>',
                        '<div dir="rtl" lang="ar"><strong>الأتمتة والكشط والجمع</strong> للمحتوى أو الأسعار أو بيانات المخزون — بما في ذلك عبر زواحف الشبكة أو المتصفّحات بلا واجهة أو وكلاء الذكاء الاصطناعي — بما يتجاوز ما يسمح به <code>robots.txt</code> صراحةً.</div>',
                        '<div dir="rtl" lang="ar"><strong>تقديم طلبات احتيالية أو وهمية</strong>، أو استخدام أدوات دفع لا تملك صلاحيتها.</div>',
                        '<div dir="rtl" lang="ar"><strong>انتحال</strong> شخصية شخص أو منشأة أو جهة، أو الإيحاء بانتمائك إليها زورًا.</div>',
                        '<div dir="rtl" lang="ar"><strong>التحايل</strong> على ضوابط الوصول أو حدود الاستخدام أو قيود المناطق أو أنظمة كشف الاحتيال.</div>',
                        '<div dir="rtl" lang="ar"><strong>اختبار أمن الموقع</strong> أو فحصه دون إذن كتابي مسبق، أو الإفصاح عن ثغرات خارج قناة الإفصاح المنسَّق (security@novakeys.store).</div>',
                        '<div dir="rtl" lang="ar"><strong>رفع أو نقل</strong> برمجيات خبيثة أو فيروسات أو أيٍّ من الأكواد الضارّة.</div>',
                        '<div dir="rtl" lang="ar"><strong>إعادة بيع</strong> أكواد البطاقات خارج قناة الاستخدام التي يقصدها المُصدِر (معظم شروط المُصدِرين تمنع إعادة البيع، وتبقى ملتزمًا بقواعدهم).</div>',
                        '<div dir="rtl" lang="ar"><strong>انتهاك حقوق الملكية الفكرية</strong>، بما فيها حقوق شركة NeoTechnology Solutions LLC (مالكة علامة NovaKeys والمنصّة) وحقوق مُصدِري بطاقات الهدايا التي تظهر علاماتهم على منتجاتنا.</div>',
                        '<div dir="rtl" lang="ar"><strong>استخدام الموقع لمخالفة أي نظام سعودي</strong>، ومنه نظام مكافحة جرائم المعلوماتية ونظام مكافحة غسل الأموال وأنظمة العقوبات.</div>',
                    ],
                ],
                [
                    'kicker_en' => '03 · ACCOUNT CONDUCT',
                    'h_en'      => 'Your account, your responsibility.',
                    'h_ar'      => 'الحساب على مسؤوليتك.',
                    'body'      => [
                        'You are responsible for keeping your login credentials confidential, for the accuracy of the information you supply (delivery address, contact details, payment data), and for every activity that occurs under your account. Notify us at ' . esc_html( $cr['email'] ) . ' the moment you suspect your account has been accessed without your authorisation.',
                        '<div dir="rtl" lang="ar">أنت المسؤول عن سريّة بيانات الدخول، وعن دقة ما تُقدّمه من معلومات (عنوان التسليم، بيانات التواصل، معلومات الدفع)، وعن كل نشاط يحدث على حسابك. أبلغنا فورًا على ' . esc_html( $cr['email'] ) . ' عند اشتباهك بدخول غير مُصرَّح به.</div>',
                    ],
                ],
                [
                    'kicker_en' => '04 · USER CONTENT',
                    'h_en'      => 'Reviews, comments, and other submissions.',
                    'h_ar'      => 'التقييمات والتعليقات.',
                    'body'      => [
                        'Where the site allows you to submit content (product reviews, support questions, public comments), you grant us a non-exclusive, worldwide, royalty-free licence to display, store, and moderate that content for the operation of the site. You undertake that the content is lawful, accurate, your own (or licensed for your use), and not defamatory, threatening, or in breach of third-party rights. We may moderate, edit, or remove submissions at our discretion.',
                        '<div dir="rtl" lang="ar">عندما يتيح الموقع لك تقديم محتوى (تقييمات المنتج، أسئلة الدعم، التعليقات العامة)، فإنك تمنحنا ترخيصًا غير حصري وعالمي ومجاني لعرض ذلك المحتوى وتخزينه وإدارته لأغراض تشغيل الموقع. وتُقرّ بأن المحتوى مشروع ودقيق وعائد إليك (أو مرخَّص لاستخدامك)، وأنه لا ينطوي على تشهير أو تهديد أو إخلال بحقوق الغير. ولنا الحق في تعديل المحتوى أو إزالته متى رأينا ذلك.</div>',
                    ],
                ],
                [
                    'kicker_en' => '05 · ENFORCEMENT',
                    'h_en'      => 'Suspension and termination.',
                    'h_ar'      => 'الإيقاف وإنهاء الحساب.',
                    'body'      => [
                        'Where we reasonably believe you have breached this policy or any applicable law, we may, at our discretion and proportionate to the breach: (i) issue a warning; (ii) suspend access to your account; (iii) cancel pending orders; (iv) close your account; (v) refuse future business; (vi) report the conduct to the competent authorities; and (vii) seek to recover any losses we have suffered. Severe breaches — for example fraud, malware distribution, or coordinated abuse — may result in immediate closure without prior notice.',
                        '<div dir="rtl" lang="ar">متى توفّر لدينا اعتقاد معقول بأنك أخلَلت بهذه السياسة أو بأي نظام مُطبَّق، يحق لنا، وفق تقديرنا وبما يتناسب مع المخالفة، أن: (1) نُوجّه إنذارًا؛ (2) نُعلّق الوصول إلى حسابك؛ (3) نُلغي الطلبات قيد التنفيذ؛ (4) نُغلق حسابك؛ (5) نمتنع عن مستقبل التعامل؛ (6) نُبلّغ الجهات المختصّة؛ (7) نسعى إلى استرداد أي خسائر لحقت بنا. وقد تؤدّي المخالفات الجسيمة — مثل الاحتيال، أو نشر البرمجيات الخبيثة، أو الإساءة المُنسَّقة — إلى الإغلاق الفوري دون إنذار.</div>',
                    ],
                ],
                [
                    'kicker_en' => '06 · REPORTING ABUSE',
                    'h_en'      => 'How to report violations.',
                    'h_ar'      => 'كيفية الإبلاغ عن المخالفات.',
                    'body'      => [
                        'Report content or activity you believe violates this policy by emailing <strong>abuse@novakeys.store</strong>. Coordinated security-vulnerability disclosures: <strong>security@novakeys.store</strong>. Both addresses are monitored during business hours (Sun–Thu 09:00–17:00 Asia/Riyadh).',
                        '<div dir="rtl" lang="ar">أبلغ عن المحتوى أو النشاط الذي تعتقد أنه يخالف هذه السياسة عبر <strong>abuse@novakeys.store</strong>. وللإفصاح المنسَّق عن الثغرات الأمنية: <strong>security@novakeys.store</strong>. يُتابَع البريدان خلال ساعات العمل (الأحد–الخميس 09:00–17:00 بتوقيت آسيا/الرياض).</div>',
                    ],
                ],
            ],
        ],

        'contact' => [
            'kicker'   => '08 · CONTACT',
            'h1_en'    => 'CONTACT',
            'h1_ar'    => 'تواصل معنا',
            'lede_en'  => 'Direct lines to the operator behind the desk. We respond within one business day.',
            'lede_ar'  => 'قنوات تواصل مباشرة مع المسؤول. نرد خلال يوم عمل واحد.',
            'draft'    => false,
            'sections' => [
                [
                    'kicker_en' => '01 · CHANNELS',
                    'h_en' => 'How to reach us.',
                    'h_ar' => 'وسائل التواصل.',
                    'body' => [
                        sprintf('Mobile · %s', $cr['phone_mobile']),
                        sprintf('Landline · %s', $cr['phone_landline']),
                        sprintf('Email · %s', $cr['email']),
                        sprintf('Website · %s', $cr['website']),
                    ],
                ],
                [
                    'kicker_en' => '02 · HOURS',
                    'h_en' => 'When we answer.',
                    'h_ar' => 'ساعات الرد.',
                    'body' => [
                        'Saturday – Thursday · 09:00 – 22:00 KSA time. Friday: limited support, expect next-business-day response.',
                    ],
                ],
                [
                    'kicker_en' => '03 · SUPPORT POSTURE',
                    'h_en' => 'What we handle directly.',
                    'h_ar' => 'ما نعالجه مباشرة.',
                    'body' => [
                        'Pre-sales spec questions, order status, shipping issues, returns, warranty intake, build briefs.',
                        'For technical product support beyond setup, manufacturer warranty channels are usually faster — we route those for you.',
                    ],
                ],
            ],
        ],

    ];

    return $cached;
}

/**
 * Wrap a body string in a paragraph if it isn't already a block-level
 * element. The `body` arrays in nk_info_pages() mix plain sentences,
 * `<strong>...`-led paragraphs, RTL `<div>` wrappers, `<table>`s, and
 * `<ul>`s — emit each one in a way that produces valid HTML.
 *
 * @since 0.2.0
 * @param string $s One body element from a section.
 * @return string Sanitised, wrapped HTML.
 */
function nk_info_para_html( $s ) {
    $s = (string) $s;
    if ( '' === ltrim( $s ) ) {
        return '';
    }
    $is_block = (bool) preg_match( '/^\s*<(p|div|table|ul|ol|h[1-6]|figure|blockquote|pre|section)\b/i', $s );
    return $is_block ? wp_kses_post( $s ) : '<p>' . wp_kses_post( $s ) . '</p>';
}

/**
 * Render the HTML for a single info page (returns / warranty / terms /
 * privacy / usage / about / shipping / contact). Used by the virtual
 * post-injection filter below so the FSE page template can render the
 * canonical legal copy without needing a real wp_posts row per slug.
 *
 * @since 0.2.0
 * @param string $key Slug from nk_info_pages().
 * @return string Rendered HTML, or empty string if the slug is unknown.
 */
function nk_render_info_page_content( $key ) {
    $info = nk_info_pages();
    if ( ! isset( $info[ $key ] ) ) {
        return '';
    }
    $page = $info[ $key ];

    ob_start();
    ?>
<div class="nk-info-page" data-page="<?php echo esc_attr( $key ); ?>">
    <?php if ( ! empty( $page['kicker'] ) ) : ?>
        <p class="nk-info-kicker"><?php echo esc_html( $page['kicker'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $page['h1_ar'] ) ) : ?>
        <p class="nk-info-h1-ar" dir="rtl" lang="ar"><?php echo esc_html( $page['h1_ar'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $page['lede_en'] ) ) : ?>
        <p class="nk-info-lede"><?php echo wp_kses_post( $page['lede_en'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $page['lede_ar'] ) ) : ?>
        <p class="nk-info-lede" dir="rtl" lang="ar"><?php echo wp_kses_post( $page['lede_ar'] ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $page['sections'] ) && is_array( $page['sections'] ) ) : ?>
        <?php foreach ( $page['sections'] as $section ) : ?>
            <section class="nk-info-section">
                <?php if ( ! empty( $section['kicker_en'] ) ) : ?>
                    <p class="nk-info-section-kicker"><?php echo esc_html( $section['kicker_en'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $section['h_en'] ) ) : ?>
                    <h2 class="nk-info-section-h"><?php echo esc_html( $section['h_en'] ); ?></h2>
                <?php endif; ?>
                <?php if ( ! empty( $section['h_ar'] ) ) : ?>
                    <p class="nk-info-section-h-ar" dir="rtl" lang="ar"><?php echo esc_html( $section['h_ar'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $section['body'] ) && is_array( $section['body'] ) ) : ?>
                    <?php foreach ( $section['body'] as $para ) : ?>
                        <?php echo nk_info_para_html( $para ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — sanitised inside helper. ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Inject a virtual WP_Post when /returns/, /warranty/, /terms/,
 * /privacy/, /usage/, /about/, /shipping/, /contact/ is requested.
 *
 * The rewrite at `add_rewrite_rule('^slug/?$', '...novakeys_page=slug')`
 * gives WP_Query nothing to match, so without this filter the route
 * 404s (or with `is_404` flipped off in the `wp` action above, lands
 * on an empty page.html). Synthesising a post here lets the FSE
 * page template render the canonical legal copy via wp:post-content.
 */
add_filter( 'the_posts', function ( $posts, $query ) {
    if ( ! $query instanceof WP_Query || ! $query->is_main_query() ) {
        return $posts;
    }
    $key = get_query_var( 'novakeys_page' );
    if ( ! $key ) {
        return $posts;
    }
    if ( ! empty( $posts ) ) {
        return $posts;
    }
    if ( 'legal' === $key ) {
        // page-legal.html template renders novakeys/legal-disclosure
        // pattern directly — leave post_content empty so the FSE
        // template fully owns the body.
        $title   = 'Legal Disclosure';
        $content = '';
    } else {
        if ( ! function_exists( 'nk_info_pages' ) ) {
            return $posts;
        }
        $info = nk_info_pages();
        if ( ! isset( $info[ $key ] ) ) {
            return $posts;
        }
        $page    = $info[ $key ];
        $title   = ! empty( $page['h1_en'] ) ? ucwords( strtolower( $page['h1_en'] ) ) : ucfirst( $key );
        $content = nk_render_info_page_content( $key );
    }
    $now   = current_time( 'mysql' );
    $now_g = current_time( 'mysql', 1 );
    $stub  = (object) array(
        'ID'                    => 0,
        'post_author'           => 1,
        'post_date'             => $now,
        'post_date_gmt'         => $now_g,
        'post_content'          => $content,
        'post_title'            => $title,
        'post_excerpt'          => '',
        'post_status'           => 'publish',
        'comment_status'        => 'closed',
        'ping_status'           => 'closed',
        'post_password'         => '',
        'post_name'             => $key,
        'to_ping'               => '',
        'pinged'                => '',
        'post_modified'         => $now,
        'post_modified_gmt'     => $now_g,
        'post_content_filtered' => '',
        'post_parent'           => 0,
        'guid'                  => home_url( '/' . $key . '/' ),
        'menu_order'            => 0,
        'post_type'             => 'page',
        'post_mime_type'        => '',
        'comment_count'         => 0,
        'filter'                => 'raw',
    );
    $post = new WP_Post( $stub );

    $query->is_404      = false;
    $query->is_singular = true;
    $query->is_single   = false;
    $query->is_page     = true;
    $query->is_archive  = false;
    $query->is_home     = false;
    $query->is_search   = false;
    $query->found_posts = 1;
    $query->post_count  = 1;
    $query->posts       = array( $post );
    $query->post        = $post;
    $query->queried_object    = $post;
    $query->queried_object_id = 0;

    return array( $post );
}, 1, 2 );

/**
 * Inline stylesheet for the info-page renderer. Scoped to .nk-info-page
 * so it cannot leak into other templates. Print only on the routes that
 * use it so /shop/ and /single-product/ stay byte-for-byte unchanged.
 */
add_action( 'wp_head', function () {
    if ( ! get_query_var( 'novakeys_page' ) || 'legal' === get_query_var( 'novakeys_page' ) ) {
        return;
    }
    ?>
<style id="nk-info-page-css">
.nk-info-page{max-width:760px;margin-inline:auto;font-size:1rem;line-height:1.65;color:var(--wp--preset--color--brand-ink,#1a1a1a)}
.nk-info-page .nk-info-kicker{font-size:.75rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--wp--preset--color--brand-slate,#666);margin:0 0 .35em}
.nk-info-page .nk-info-h1-ar{font-size:1.4rem;font-weight:600;margin:.25em 0 1.2em;color:var(--wp--preset--color--brand-ink,#1a1a1a)}
.nk-info-page .nk-info-lede{font-size:1.05rem;line-height:1.7;margin:0 0 1.4em;color:var(--wp--preset--color--brand-slate,#444)}
.nk-info-page .nk-info-section{margin:2.2em 0;padding-block:1.4em 0;border-top:1px solid var(--wp--preset--color--brand-mist,#e6e6e6)}
.nk-info-page .nk-info-section:first-of-type{border-top:0;padding-top:0}
.nk-info-page .nk-info-section-kicker{font-size:.7rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--wp--preset--color--brand-slate,#666);margin:0 0 .35em}
.nk-info-page .nk-info-section-h{font-size:1.18rem;font-weight:600;margin:0 0 .5em;line-height:1.35}
.nk-info-page .nk-info-section-h-ar{font-size:1.05rem;font-weight:500;margin:0 0 .5em;color:var(--wp--preset--color--brand-slate,#666)}
.nk-info-page p{margin:0 0 1em}
.nk-info-page strong{font-weight:600}
.nk-info-page code{background:var(--wp--preset--color--brand-mist,#f1f3f5);padding:.05em .35em;border-radius:.25em;font-size:.92em}
.nk-info-page [dir="rtl"]{font-family:"IBM Plex Sans Arabic","Tajawal",system-ui,sans-serif}
.nk-legal-table{width:100%;border-collapse:collapse;margin:1em 0;font-size:.95em}
.nk-legal-table th,.nk-legal-table td{padding:.55em .75em;border:1px solid var(--wp--preset--color--brand-mist,#e6e6e6);text-align:start;vertical-align:top}
.nk-legal-table th{background:var(--wp--preset--color--brand-mist,#f5f5f5);font-weight:600}
[dir="rtl"] .nk-legal-table th,[dir="rtl"] .nk-legal-table td{text-align:start}
</style>
    <?php
}, 99 );

// Resolve asset dir + URL regardless of where the deploy plugin clones us.
$ng_theme_asset_dir = __DIR__ . '/neogen-theme-assets';
$ng_theme_rel       = str_replace(
    wp_normalize_path(WP_CONTENT_DIR),
    '',
    wp_normalize_path($ng_theme_asset_dir)
);
if (!defined('NG_THEME_ASSET_DIR')) {
    define('NG_THEME_ASSET_DIR', $ng_theme_asset_dir);
}
if (!defined('NG_THEME_ASSET_URL')) {
    define('NG_THEME_ASSET_URL', content_url($ng_theme_rel));
}

/**
 * Enqueue Google Fonts + chrome CSS + chrome JS sitewide.
 *
 * The chrome stylesheet + JS ship inside this plugin at
 * `assets/chrome/`. The legacy NG_THEME_ASSET_URL points at a
 * mu-plugins path that didn't survive the phase-4 cleanup; loading
 * from NK_COMMERCE_URL keeps the chrome asset bundle deployable
 * with the rest of the plugin.
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'neogen-google-fonts',
        'https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&family=Major+Mono+Display&family=Manrope:wght@300;400;500;600;700&family=Rakkas&family=Reem+Kufi:wght@400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap',
        [],
        null
    );

    $css_path = NK_COMMERCE_DIR . 'assets/chrome/neogen.css';
    $js_path  = NK_COMMERCE_DIR . 'assets/chrome/neogen.js';

    $css_ver = file_exists($css_path) ? (string) filemtime($css_path) : NK_COMMERCE_VERSION;
    $js_ver  = file_exists($js_path)  ? (string) filemtime($js_path)  : NK_COMMERCE_VERSION;

    wp_enqueue_style(
        'novakeys-chrome',
        NK_COMMERCE_URL . 'assets/chrome/neogen.css',
        ['neogen-google-fonts'],
        $css_ver
    );

    wp_enqueue_script(
        'novakeys-chrome',
        NK_COMMERCE_URL . 'assets/chrome/neogen.js',
        [],
        $js_ver,
        true
    );
}, 20);

/**
 * Preconnect hints so Google Fonts ship fast.
 */
add_action('wp_head', function () {
    echo "\n" . '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    // Match the manifest theme_color (#38BDF8 — R6 Sky + Cool White) + light operator-console aesthetic.
    echo '<meta name="theme-color" content="#38BDF8">' . "\n";
    echo '<meta name="color-scheme" content="light only">' . "\n";
}, 2);

/**
 * Force light mode on the HTML root before first paint so the parent
 * Blocksy theme's dark-mode initialization can't briefly flash dark.
 * Runs at wp_head priority 0 — before any other inline scripts.
 *
 * Phase 2c: gated by ng_blocksy_dark_mode_allowed(). When the operator
 * opts into dark mode in Tools → NovaKeys Blocksy Handoff, this script
 * stops emitting, the data-prefers-color-scheme attribute is no
 * longer forced, and Blocksy's Customize → Color Scheme picker is
 * free to set the attribute itself.
 */
add_action('wp_head', function () {
    if ( function_exists('ng_blocksy_dark_mode_allowed') && ng_blocksy_dark_mode_allowed() ) return;
    echo "\n<script>" .
         "document.documentElement.setAttribute('data-prefers-color-scheme','light');" .
         "document.documentElement.classList.remove('ct-dark','dark','dark-mode');" .
         "</script>\n";
}, 0);

/**
 * Favicon + PWA icon set. Brand kit v1.1 approves the 8-point star
 * for favicon use specifically (it reads cleanly at 16x16 unlike
 * the NG monogram lockup, which needs typography).
 *
 * Cascade:
 *   - SVG primary (modern browsers Chrome 80+, Firefox 41+, Safari 9+)
 *   - 32x32 PNG fallback (older browsers)
 *   - 180x180 apple-touch-icon (iOS home screen)
 *   - 192x192 + 512x512 PNG via webmanifest (PWA / Android home screen)
 *   - WP's wp_site_icon() default is suppressed by remove_action below
 *     so we have a single source of truth.
 */
remove_action('wp_head', 'wp_site_icon', 99);

add_action('wp_head', function () {
    $base = NG_THEME_ASSET_URL . '/icons';
    echo "\n";
    echo '<link rel="icon" href="' . esc_url($base . '/favicon.ico') . '" sizes="any">' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($base . '/favicon.svg') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url($base . '/icon-16.png') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($base . '/icon-32.png') . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($base . '/apple-touch-icon.png') . '">' . "\n";
    echo '<link rel="mask-icon" href="' . esc_url($base . '/safari-pinned-tab.svg') . '" color="#38BDF8">' . "\n";
    echo '<link rel="manifest" href="' . esc_url($base . '/site.webmanifest') . '">' . "\n";
}, 1);

/**
 * Google Tag Manager — head script.
 * Container ID is admin-overridable via option `ng_gtm_container_id`;
 * default is the production container.
 */
add_action('wp_head', function () {
    if ( is_admin() || is_customize_preview() ) return;
    $gtm_id = (string) get_option( 'nk_gtm_container_id', '' );
    if ( '' === $gtm_id ) {
        $gtm_id = (string) get_option( 'ng_gtm_container_id', 'GTM-PRTBSHTW' );
    }
    $gtm_id = preg_replace( '/[^A-Za-z0-9_\-]/', '', $gtm_id );
    if ( $gtm_id === '' ) return;
    ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');</script>
<!-- End Google Tag Manager -->
    <?php
}, 1);

/**
 * Google Tag Manager — noscript iframe at the very top of <body>.
 */
add_action('wp_body_open', function () {
    if ( is_admin() || is_customize_preview() ) return;
    $gtm_id = (string) get_option( 'nk_gtm_container_id', '' );
    if ( '' === $gtm_id ) {
        $gtm_id = (string) get_option( 'ng_gtm_container_id', 'GTM-PRTBSHTW' );
    }
    $gtm_id = preg_replace( '/[^A-Za-z0-9_\-]/', '', $gtm_id );
    if ( $gtm_id === '' ) return;
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
}, 1);

/**
 * Schema.org Store JSON-LD so crawlers can verify the business.
 * Only fields from the active MOC register are emitted — no
 * fabricated VAT, address, or contact.
 */
add_action('wp_head', function () {
    // R5 cutover: when the engine is ON it emits a complete @graph
    // (Store + WebSite + WebPage + per-surface nodes) from
    // mu-plugins/neogen-seo-engine.php. Avoid dual emission here.
    if ( function_exists('ng_seo_engine_enabled') && ng_seo_engine_enabled() ) return;

    $cr       = nk_cr();
    $tel_e164 = preg_replace('/\s+/', '', $cr['phone_mobile']); // +966570131122
    $home     = rtrim(home_url('/'), '/') . '/';

    $store = [
        '@type'         => 'Store',
        '@id'           => $home . '#organization',
        'name'          => $cr['brand_en'],
        'alternateName' => [$cr['brand_ar'], $cr['legal_name_en']],
        'legalName'     => $cr['legal_name_ar'],
        'url'           => home_url('/'),
        'email'         => $cr['email'],
        'telephone'     => $tel_e164,
        'foundingDate'  => $cr['registered_ad'],
        'founder'       => ['@type' => 'Person', 'name' => $cr['owner']],
        'address'       => ['@type' => 'PostalAddress', 'addressCountry' => 'SA'],
        'areaServed'    => ['@type' => 'Country', 'name' => 'Saudi Arabia'],
        'inLanguage'    => ['ar-SA', 'en'],
        'identifier'    => array_merge(
            [[
                '@type'      => 'PropertyValue',
                'propertyID' => 'Saudi Ministry of Commerce Unified CR Number',
                'value'      => $cr['cr'],
            ]],
            array_map(function ($r) {
                return [
                    '@type'      => 'PropertyValue',
                    'propertyID' => $r['authority_en'] . ' Registration',
                    'value'      => $r['number'],
                ];
            }, $cr['regulatory']),
            array_map(function ($a) {
                return [
                    '@type'      => 'PropertyValue',
                    'propertyID' => 'ISIC v4 ' . $a['code'],
                    'value'      => $a['en'],
                ];
            }, $cr['activities'])
        ),
        'knowsAbout'    => [
            'System integration', 'Network infrastructure', 'Homelab hardware',
            'Smart home systems', 'Gaming hardware', 'E-commerce',
        ],
        'contactPoint'  => [[
            '@type'             => 'ContactPoint',
            'telephone'         => $tel_e164,
            'email'             => $cr['email'],
            'contactType'       => 'customer support',
            'availableLanguage' => ['Arabic', 'English'],
            'areaServed'        => 'SA',
        ]],
    ];

    $website = [
        '@type'    => 'WebSite',
        '@id'      => $home . '#website',
        'url'      => $home,
        'name'     => $cr['brand_en'],
        'inLanguage' => ['ar-SA', 'en'],
        'publisher' => ['@id' => $home . '#organization'],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $home . '?s={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $webpage = [
        '@type'      => 'WebPage',
        '@id'        => trailingslashit( ( is_front_page() || is_home() ) ? $home : home_url( add_query_arg( null, null ) ) ) . '#webpage',
        'url'        => ( is_front_page() || is_home() ) ? $home : home_url( add_query_arg( null, null ) ),
        'name'       => wp_get_document_title(),
        'isPartOf'   => ['@id' => $home . '#website'],
        'about'      => ['@id' => $home . '#organization'],
        'inLanguage' => is_rtl() ? 'ar-SA' : 'en',
    ];

    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [ $store, $website, $webpage ],
    ];

    $graph = apply_filters('novakeys_org_jsonld_graph', $graph, $cr);
    $json = wp_json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json) {
        echo "\n" . '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
    }
}, 5);

/**
 * Custom routes — /legal/ + the seven info pages from nk_info_pages().
 * One rewrite rule per slug, all driven by the novakeys_page query var.
 * Rewrite cache flushed exactly once per theme version (keyed option).
 */
add_action('init', function () {
    $slugs = array_merge(['legal'], array_keys(nk_info_pages()));
    foreach ($slugs as $slug) {
        // v1.31.0: yield to a real published WP page if one exists at
        // this slug — lets the operator edit the content via wp-admin.
        // /legal/ stays virtual since it's auto-derived from nk_cr().
        if ($slug !== 'legal') {
            $real = get_page_by_path($slug, OBJECT, 'page');
            if ($real instanceof WP_Post && $real->post_status === 'publish') {
                continue;
            }
        }
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'index.php?novakeys_page=' . $slug, 'top');
    }

    $flag = 'novakeys_rewrites_flushed_' . str_replace('.', '_', NOVAKEYS_THEME_VERSION);
    if (!get_option($flag)) {
        flush_rewrite_rules(false);
        update_option($flag, 1, true);
    }
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'novakeys_page';
    return $vars;
});

// Fix conditional state late, after WP_Query has finished. Without this,
// /terms/, /privacy/, /legal/, etc. keep is_home=true (no post-type
// identifier in their parsed query) which makes Rank Math emit
// CollectionPage schema, gives the body 'blog' class, and uses the
// homepage <title>.
add_action('wp', function () {
    $key = get_query_var('novakeys_page');
    if (!$key) { return; }
    global $wp_query;
    $wp_query->is_home    = false;
    $wp_query->is_archive = false;
    $wp_query->is_404     = false;
    // Set `pagename` so get_page_template() picks page-{slug}.html.
    // Without this, the virtual post (ID=0) hits the `! $pagename && $id`
    // gate in core and the slug hierarchy is skipped — page-legal.html
    // would never be considered. The query var is also what FSE uses to
    // resolve the block template by slug.
    set_query_var('pagename', $key);
    $wp_query->query_vars['pagename'] = $key;
    $wp_query->query['pagename']      = $key;
});

// Without a real WP post backing /terms/ etc., the document title falls back
// to "Page Array – Site Name". Override per-route. Priority 999 so SEO
// plugins (Rank Math) that filter the title earlier still get overridden.
add_filter('pre_get_document_title', function ($title) {
    $page = get_query_var('novakeys_page');
    if (!$page) { return $title; }
    $site = get_bloginfo('name');
    if ($page === 'legal') {
        return 'Legal Disclosure — ' . $site;
    }
    if (function_exists('nk_info_pages')) {
        $info = nk_info_pages();
        if (isset($info[$page]['h1_en']) && $info[$page]['h1_en']) {
            return ucwords(strtolower($info[$page]['h1_en'])) . ' — ' . $site;
        }
    }
    return ucfirst($page) . ' — ' . $site;
}, 999);

// When a virtual /terms/, /privacy/, /legal/, etc. route fires, the matched
// query has no post-type identifier so WP defaults is_home=true. The active
// theme's priority-999 template_include then swaps in app-shell.php for any
// is_home() request, clobbering our legal template. Flip is_home off here so
// the theme filter no longer matches and the body class loses 'blog'.

add_filter('template_include', function ($template) {
    if (is_admin()) { return $template; }

    // 404 — operator-console "ROUTE NOT FOUND" page.
    if (is_404()) {
        $tpl = NG_THEME_ASSET_DIR . '/templates/404.php';
        if (file_exists($tpl)) {
            if (!defined('NG_RENDER_404')) { define('NG_RENDER_404', true); }
            return $tpl;
        }
    }

    // Single blog post — editorial reading template (post type `post` only).
    if (is_singular('post')) {
        $tpl = NG_THEME_ASSET_DIR . '/templates/single-post.php';
        if (file_exists($tpl)) {
            if (!defined('NG_RENDER_POST')) { define('NG_RENDER_POST', true); }
            return $tpl;
        }
    }

    // Empty search — themed "no results" surface. Non-empty searches
    // fall through so Blocksy / default search.php renders normally.
    if (is_search()) {
        global $wp_query;
        if ($wp_query && (int) $wp_query->found_posts === 0) {
            $tpl = NG_THEME_ASSET_DIR . '/templates/search.php';
            if (file_exists($tpl)) {
                if (!defined('NG_RENDER_SEARCH_EMPTY')) { define('NG_RENDER_SEARCH_EMPTY', true); }
                return $tpl;
            }
        }
    }

    $page = get_query_var('novakeys_page');
    if (!$page) { return $template; }

    if ($page === 'legal') {
        $legal = NG_THEME_ASSET_DIR . '/templates/legal.php';
        if (!file_exists($legal)) { return $template; }
        if (!defined('NG_RENDER_LEGAL_PAGE')) {
            define('NG_RENDER_LEGAL_PAGE', true);
        }
        return $legal;
    }

    $info = nk_info_pages();
    if (isset($info[$page])) {
        $tpl = NG_THEME_ASSET_DIR . '/templates/info-page.php';
        if (!file_exists($tpl)) { return $template; }
        if (!defined('NG_RENDER_INFO_PAGE')) {
            define('NG_RENDER_INFO_PAGE', $page);
        }
        return $tpl;
    }

    return $template;
// Priority 1001 so we run AFTER the active theme's priority-999
// template_include in themes/novakeys/functions.php, which otherwise
// blanket-rewrites is_home() requests to its app-shell.php and
// clobbers our virtual /terms/, /legal/, /returns/, /privacy/,
// /warranty/, /usage/ routes (those keep is_home=true because WP has
// no post-type identifier in their parsed query).
}, 1001);

/**
 * Route WooCommerce template parts to our overrides. Keeps all
 * deployable code inside mu-plugins/ (the known-reliable deploy
 * target) instead of themes/blocksy-child/woocommerce/.
 *
 * Only `content-product.php` is overridden at this stage — the
 * shop-archive loop card. Single-product stays Blocksy-default
 * until explicitly themed.
 */
add_filter('wc_get_template_part', function ($template, $slug, $name) {
    $map = [
        'content|product'        => 'content-product.php',
        'content|single-product' => 'content-single-product.php',
    ];
    $key = $slug . '|' . $name;
    if (isset($map[$key])) {
        $override = NG_THEME_ASSET_DIR . '/templates/woocommerce/' . $map[$key];
        if (file_exists($override)) {
            return $override;
        }
    }
    return $template;
}, 10, 3);

/**
 * v1.34.3: drop WC's default loop thumbnail hook.
 *
 * Our content-product.php override renders the product image inline
 * (with brand polish + LED badge) before do_action(
 * 'woocommerce_before_shop_loop_item_title' ). WC ships
 * woocommerce_template_loop_product_thumbnail hooked there at prio 10,
 * which was printing a duplicate image stacked above ours on every
 * archive (shop / product_cat / tag / search). Remove it once.
 */
add_action( 'init', function () {
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
}, 20 );

/**
 * Route full Woo template paths (not template_parts) to our overrides.
 * Used for checkout/thankyou.php etc.
 */
add_filter('wc_get_template', function ($template, $template_name, $args, $template_path, $default_path) {
    $overrides = [
        'checkout/thankyou.php',
        'cart/cart.php',
        'cart/cart-empty.php',
        'cart/cart-totals.php',
        'emails/email-header.php',
        'emails/email-footer.php',
        'emails/email-styles.php',
        'emails/customer-processing-order.php',
        'myaccount/navigation.php',
        'myaccount/dashboard.php',
        'myaccount/orders.php',
        'myaccount/form-login.php',
        'checkout/form-checkout.php',
    ];
    if (in_array($template_name, $overrides, true)) {
        $candidate = NG_THEME_ASSET_DIR . '/templates/woocommerce/' . $template_name;
        if (file_exists($candidate)) {
            return $candidate;
        }
    }
    return $template;
}, 10, 5);

/**
 * Bilingual title field on the Woo product editor.
 * Writes to _ng_ar_title — the meta key our content-product.php and
 * content-single-product.php templates both read (falls back to the
 * English title when empty).
 */
add_action('woocommerce_product_options_general_product_data', function () {
    global $post;
    woocommerce_wp_text_input([
        'id'          => '_ng_ar_title',
        'label'       => __('Arabic title', 'neogen'),
        'placeholder' => get_post_meta($post->ID, '_ng_ar_title', true) ?: '',
        'desc_tip'    => true,
        'description' => __('Shown above the English title on shop and single-product pages. Leave blank to reuse the English title.', 'neogen'),
        'value'       => get_post_meta($post->ID, '_ng_ar_title', true),
    ]);
});

add_action('woocommerce_process_product_meta', function ($post_id) {
    if (!current_user_can('edit_post', $post_id)) { return; }
    $val = isset($_POST['_ng_ar_title']) ? sanitize_text_field((string) wp_unslash($_POST['_ng_ar_title'])) : '';
    if ($val === '') {
        delete_post_meta($post_id, '_ng_ar_title');
    } else {
        update_post_meta($post_id, '_ng_ar_title', $val);
    }
});

/**
 * Category archive header — styled intro block above the product loop
 * on /product-category/<slug>/ pages. Uses the term's description
 * verbatim (admins can enter AR + EN there).
 */
add_action('woocommerce_archive_description', function () {
    if (!is_product_taxonomy()) { return; }
    $term = get_queried_object();
    if (!$term || empty($term->term_id)) { return; }

    $count = (int) $term->count;
    $desc  = term_description($term->term_id, $term->taxonomy);
    ?>
    <header class="ng-cat-header">
      <div class="ng-cat-kicker">
        <span class="led on"></span>
        <span>الفئة / <?php echo esc_html( $term->taxonomy === 'product_cat' ? 'رف' : 'وسم' ); ?></span>
        <span class="sep"></span>
        <span><?php echo esc_html(sprintf(_n('%d SKU', '%d SKUs', $count, 'neogen'), $count)); ?></span>
      </div>
      <h1 class="ng-cat-h1"><?php echo esc_html( nk_ar_label( $term->name ) ); ?></h1>
      <?php if ($desc) : ?>
        <div class="ng-cat-desc"><?php echo wp_kses_post($desc); ?></div>
      <?php endif; ?>
    </header>
    <?php
}, 5);

/**
 * Swap the front-page template for our branded one. The template itself
 * guards against direct-require via the NG_RENDER_FRONT_PAGE sentinel.
 */
add_filter('template_include', function ($template) {
    if (is_admin() || !is_front_page()) {
        return $template;
    }
    $front = NG_THEME_ASSET_DIR . '/templates/front-page.php';
    if (!file_exists($front)) {
        return $template;
    }
    if (!defined('NG_RENDER_FRONT_PAGE')) {
        define('NG_RENDER_FRONT_PAGE', true);
    }
    return $front;
}, 99);

/**
 * Fallback shortcode for when the front page is configured to display a
 * specific static page that still renders through Blocksy's content
 * area — content editors can drop [novakeys_home_sections] into that page.
 */
add_shortcode('novakeys_home_sections', function () {
    $front = NG_THEME_ASSET_DIR . '/templates/front-page.php';
    if (!file_exists($front)) { return ''; }
    if (!defined('NG_RENDER_FRONT_PAGE')) {
        define('NG_RENDER_FRONT_PAGE', true);
    }
    // The template calls get_header()/get_footer() — those are already in
    // progress when a shortcode runs, so we need the body markup only.
    // Parse out everything between the first <header class="ng-hero"> and
    // the closing </section> of the voice band.
    ob_start();
    include $front;
    $html = ob_get_clean();
    // Strip header/footer that the template emitted (we're inside content).
    $start = strpos($html, '<header class="ng-hero"');
    $end   = strrpos($html, '</section>');
    if ($start === false || $end === false) {
        return $html;
    }
    return substr($html, $start, $end - $start + strlen('</section>'));
});

/**
 * Sitewide sysbar + top nav injected right after <body>.
 * Blocksy's own header is hidden by CSS in neogen.css.
 */
add_action('wp_body_open', function () {
    if (is_admin()) { return; }
    // Phase 2b: respect the Blocksy handoff toggle. When ON, this
    // injector no-ops and Blocksy's header builder takes over.
    if ( function_exists('ng_blocksy_chrome_handoff') && ng_blocksy_chrome_handoff() ) { return; }

    $home = home_url('/');
    // Defensive resolution — if WC page options are unset/misconfigured
    // and the Woo helpers fall back to home, force the canonical paths
    // so the header icons never silently link to the storefront root.
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
    if ( $shop === '' || untrailingslashit( $shop ) === untrailingslashit( $home ) ) {
        $shop = home_url('/shop/');
    }
    $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';
    if ( $cart === '' || untrailingslashit( $cart ) === untrailingslashit( $home ) ) {
        $cart = home_url('/cart/');
    }
    $acct = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : '';
    if ( $acct === '' || untrailingslashit( $acct ) === untrailingslashit( $home ) ) {
        $acct = home_url('/my-account/');
    }
    // Search target — go straight to /?s= so the icon click lands on a search box.
    $search = home_url('/?s=');

    $cart_count = 0;
    if (function_exists('WC') && WC() && WC()->cart) {
        $cart_count = (int) WC()->cart->get_cart_contents_count();
    }

    // Top 5 live product categories for the nav (cached, 1h TTL).
    $cats = nk_top_product_cats(5);

    // Queue seed — a plausible in-range number; nudged by JS client-side.
    $queue_seed = 14;
    ?>
<div class="ng-sysbar" aria-label="حالة النظام">
  <span class="led" aria-hidden="true"></span>
  <span>الساعة <b id="ng-clock">00:00:00</b> الرياض</span>
  <span class="sep"></span>
  <span>المخزون <b class="cyan">مباشر</b></span>
  <span class="sep hide-sm"></span>
  <span class="hide-sm">في الانتظار <b id="ng-queue"><?php echo esc_html( $queue_seed ); ?></b> طلب</span>
  <span class="sep hide-sm"></span>
  <span class="hide-sm">شحن 2-5 أيام · الرياض · جدة · الدمام</span>
  <span class="spacer"></span>
  <span>الضريبة <b>15%</b> شاملة</span>
  <span class="sep hide-sm"></span>
  <span class="hide-sm">عربي</span>
</div>

<nav class="ng-topnav" aria-label="القائمة الرئيسية">
  <a class="ng-lockup" href="<?php echo esc_url( $home ); ?>" aria-label="الصفحة الرئيسية NovaKeys Store">
    <img class="ng-lockup-mark" src="<?php echo esc_url( NK_COMMERCE_URL . 'assets/chrome/nk-mark.svg' ); ?>" alt="NovaKeys" width="38" height="38" decoding="async">
    <span class="sep"></span>
    <span class="wordmark"><span class="neo">NOVA</span><span class="gen">KEYS</span></span>
  </a>
  <div class="ng-nav-cats">
    <?php foreach ( $cats as $term ) :
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) { continue; }
    ?>
      <a href="<?php echo esc_url( $link ); ?>"><span class="dot"></span><?php echo esc_html( nk_ar_label( $term->name ) ); ?></a>
    <?php endforeach; ?>
      <a href="<?php echo esc_url( home_url("/vouchers/") ); ?>"><span class="dot"></span>بطاقات</a>
  </div>
  <div class="ng-nav-tools">
    <a class="ng-nav-tool" href="<?php echo esc_url( $search ); ?>" aria-label="البحث في المتجر" rel="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <span class="tool-label">البحث</span>
    </a>
    <a class="ng-nav-tool" href="<?php echo esc_url( $acct ); ?>" aria-label="الحساب">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4.5-6 8-6s7 2 8 6"/></svg>
      <span class="tool-label">الحساب</span>
    </a>
    <a class="ng-nav-tool" href="<?php echo esc_url( $cart ); ?>" aria-label="السلة">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 5h3l2 12h10l2-8H7"/><circle cx="10" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
      <span class="tool-label">السلة</span>
      <span class="count<?php echo $cart_count > 0 ? '' : ' is-empty'; ?>"><?php echo esc_html( $cart_count ); ?></span>
    </a>
  </div>
</nav>
    <?php
});

/**
 * Newsletter subscribe handler (footer form). Verifies the
 * `ng_newsletter` nonce and bot-rate-limits per IP, then sanitises
 * the email and stores it under the `nk_newsletter_subscribers`
 * option as an opt-in record. Fires `nk_newsletter_subscribe` so a
 * real ESP integration can hook in later. Always redirects back to
 * the referrer with a `?nk_news=ok|err` query so the front-end can
 * show feedback without exposing internals.
 *
 * @since 0.2.1
 * @return void
 */
function nk_newsletter_subscribe_handler() {
    $back = wp_get_referer();
    if ( ! $back ) {
        $back = home_url( '/' );
    }

    if ( ! isset( $_POST['ng_newsletter_nonce'] )
        || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ng_newsletter_nonce'] ) ), 'ng_newsletter' )
    ) {
        wp_safe_redirect( add_query_arg( 'nk_news', 'err', $back ) );
        exit;
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( '' === $email || ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'nk_news', 'err', $back ) );
        exit;
    }

    $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $rate_k  = 'nk_news_' . md5( $ip );
    if ( $ip && false !== get_transient( $rate_k ) ) {
        wp_safe_redirect( add_query_arg( 'nk_news', 'ok', $back ) ); // Pretend success on rate-limit.
        exit;
    }

    $subs = get_option( 'nk_newsletter_subscribers', array() );
    if ( ! is_array( $subs ) ) {
        $subs = array();
    }
    if ( ! in_array( $email, $subs, true ) ) {
        $subs[] = $email;
        update_option( 'nk_newsletter_subscribers', $subs, false );

        /**
         * Fires after a new email is added to the local subscriber list.
         * ESP integrations should hook here to forward to their provider.
         *
         * @since 0.2.1
         * @param string $email Sanitised subscriber email.
         */
        do_action( 'nk_newsletter_subscribe', $email );
    }

    if ( $ip ) {
        set_transient( $rate_k, 1, MINUTE_IN_SECONDS );
    }

    wp_safe_redirect( add_query_arg( 'nk_news', 'ok', $back ) );
    exit;
}
add_action( 'admin_post_ng_newsletter_subscribe',        'nk_newsletter_subscribe_handler' );
add_action( 'admin_post_nopriv_ng_newsletter_subscribe', 'nk_newsletter_subscribe_handler' );

/**
 * Live cart-count update via Woo AJAX fragments.
 */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    if (!function_exists('WC') || !WC() || !WC()->cart) { return $fragments; }
    $count = (int) WC()->cart->get_cart_contents_count();
    $fragments['.ng-nav-tools a[aria-label="السلة"] .count'] =
        '<span class="count' . ($count > 0 ? '' : ' is-empty') . '">' . esc_html($count) . '</span>';
    return $fragments;
});

/**
 * Sitewide footer injected on wp_footer. Blocksy's original is hidden by CSS.
 */
add_action('wp_footer', function () {
    if (is_admin()) { return; }
    // Phase 2b: respect the Blocksy handoff toggle. When ON, this
    // footer no-ops and Blocksy's footer builder takes over.
    if ( function_exists('ng_blocksy_chrome_handoff') && ng_blocksy_chrome_handoff() ) { return; }

    $home = home_url('/');
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : $home;
    $cats = nk_top_product_cats(6);
    $year = date_i18n('Y');
    ?>
<footer class="ng-footer">
  <!-- Trust badges row (v1.28.0) -->
  <div class="ng-foot-trust" aria-label="ضمانات المتجر">
    <div class="ng-foot-trust-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <rect x="5" y="11" width="14" height="9" rx="1"/>
        <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
      </svg>
      <span class="ng-foot-trust-label">دفع آمن</span>
      <span class="ng-foot-trust-sub">Mada · Apple Pay · STC Pay · Tabby</span>
    </div>
    <div class="ng-foot-trust-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <path d="M3 7h11v9H3z"/>
        <path d="M14 10h4l3 3v3h-7"/>
        <circle cx="7" cy="18" r="2"/>
        <circle cx="17" cy="18" r="2"/>
      </svg>
      <span class="ng-foot-trust-label">شحن سريع</span>
      <span class="ng-foot-trust-sub">2-5 أيام داخل المملكة</span>
    </div>
    <div class="ng-foot-trust-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <path d="M3 12a9 9 0 1 0 3-6.7"/>
        <path d="M3 4v5h5"/>
      </svg>
      <span class="ng-foot-trust-label">إرجاع 14 يوم</span>
      <span class="ng-foot-trust-sub">بدون أسئلة</span>
    </div>
    <div class="ng-foot-trust-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>
        <path d="m9 12 2 2 4-4"/>
      </svg>
      <span class="ng-foot-trust-label">ضمان 12 شهر</span>
      <span class="ng-foot-trust-sub">على كل المنتجات</span>
    </div>
  </div>

  <!-- Newsletter capture (v1.28.0) -->
  <div class="ng-foot-newsletter">
    <div class="ng-foot-newsletter-inner">
      <div class="ng-foot-newsletter-text">
        <h3>عروض المشغّلين · مباشرة لبريدك</h3>
        <p>منتجات جديدة، تخفيضات حصرية، ونصائح تقنية مختصرة. لا سبام — يمكنك إلغاء الاشتراك في أي وقت.</p>
      </div>
      <form class="ng-foot-newsletter-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
        <input type="hidden" name="action" value="ng_newsletter_subscribe">
        <?php wp_nonce_field( 'ng_newsletter', 'ng_newsletter_nonce' ); ?>
        <label class="ng-foot-newsletter-field">
          <span class="screen-reader-text">بريدك الإلكتروني</span>
          <input type="email" name="email" placeholder="بريدك الإلكتروني" autocomplete="email" required>
        </label>
        <button type="submit" class="btn btn-primary">
          اشترك
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </button>
      </form>
    </div>
  </div>

  <div class="ng-foot-inner">
    <div class="ng-foot-col ng-foot-brand">
      <a class="ng-lockup" href="<?php echo esc_url( $home ); ?>" style="margin-bottom:4px;">
        <img class="ng-lockup-mark" src="<?php echo esc_url( NK_COMMERCE_URL . 'assets/chrome/nk-mark.svg' ); ?>" alt="NovaKeys" width="32" height="32" decoding="async">
        <span class="sep" style="height:20px;"></span>
        <span class="wordmark" style="font-size:24px;"><span class="neo">NOVA</span><span class="gen">KEYS</span></span>
      </a>
      <p>متجر تقني سعودي. نختار المنتجات بعناية، نوضح المواصفات بدون مبالغة، ونبني تجربة شراء تناسب المستخدم التقني الذي يعرف ما يحتاجه.</p>
    </div>
    <div class="ng-foot-col">
      <h4>// الكتالوج</h4>
      <ul>
        <?php if ( !empty( $cats ) ) :
            foreach ( $cats as $term ) :
                $link = get_term_link( $term );
                if ( is_wp_error( $link ) ) { continue; }
        ?>
          <li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( nk_ar_label( $term->name ) ); ?></a></li>
        <?php   endforeach;
        else : ?>
          <li><a href="<?php echo esc_url( $shop ); ?>">تصفّح المتجر</a></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="ng-foot-col">
      <h4>// الدعم</h4>
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/my-account/orders/' ) ); ?>">تتبّع الطلب</a></li>
        <li><a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">الإرجاع · 14 يوم</a></li>
        <li><a href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">الضمان · 12 شهر</a></li>
        <li><a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">الشحن · 2-5 أيام</a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">تواصل معنا</a></li>
      </ul>
    </div>
    <div class="ng-foot-col">
      <h4>// معلومات</h4>
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">من نحن</a></li>
        <li><a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">سياسة الشحن</a></li>
        <li><a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">سياسة الاسترجاع</a></li>
        <li><a href="<?php echo esc_url( home_url( '/warranty/' ) ); ?>">سياسة الضمان</a></li>
        <li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">الشروط والأحكام</a></li>
        <li><a href="<?php echo esc_url( home_url( '/usage/' ) ); ?>">سياسة الاستخدام</a></li>
        <li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">سياسة الخصوصية</a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">تواصل معنا</a></li>
        <li><a href="<?php echo esc_url( home_url( '/legal/' ) ); ?>">هوية المنشأة</a></li>
      </ul>
    </div>
    <?php $cr = nk_cr(); ?>
    <div class="ng-foot-col">
      <h4>// المتجر</h4>
      <ul>
        <li>سجل تجاري · <?php echo esc_html( $cr['cr'] ); ?></li>
        <?php foreach ( $cr['regulatory'] as $r ) : ?>
          <li><?php echo esc_html( $r['authority_ar'] ?? strtoupper( $r['key'] ) ); ?> · <?php echo esc_html( $r['number'] ); ?></li>
        <?php endforeach; ?>
        <li>الضريبة · 15% شاملة</li>
        <li>الرياض · جدة · الدمام</li>
        <li>عربي</li>
      </ul>
    </div>
  </div>
  <div class="ng-foot-pay" aria-label="الدفع والشحن">
    <span class="ng-foot-pay-label">// الدفع · الشحن</span>
    <div class="ng-foot-pay-row">
      <span class="ng-pay-chip">Mada</span>
      <span class="ng-pay-chip">Apple Pay</span>
      <span class="ng-pay-chip">STC Pay</span>
      <span class="ng-pay-chip">Tabby</span>
      <span class="ng-foot-pay-sep"></span>
      <span class="ng-pay-chip">SMSA</span>
      <span class="ng-pay-chip">Aramex</span>
    </div>
  </div>
  <div class="ng-disclosure" role="complementary" aria-label="هوية المنشأة">
    <div class="ng-disclosure-en">
      <span><?php echo esc_html( $cr['entity_type_ar'] ?? $cr['entity_type'] ); ?></span>
      <span class="sep"></span>
      <span><?php echo esc_html($cr['owner']); ?></span>
      <span class="sep"></span>
      <span>سجل تجاري <b><?php echo esc_html($cr['cr']); ?></b></span>
      <span class="sep"></span>
      <span class="ok"><?php echo esc_html( $cr['status_ar'] ?? $cr['status'] ); ?></span>
      <span class="sep"></span>
      <span><?php echo esc_html($cr['phone_mobile']); ?></span>
    </div>
    <a class="ng-disclosure-link" href="<?php echo esc_url( home_url('/legal/') ); ?>">
      هوية المنشأة
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
  </div>
  <div class="ng-foot-bottom">
    <span>© <?php echo esc_html( $year ); ?> <b><?php echo esc_html($cr['brand_en']); ?></b> · جميع الحقوق محفوظة</span>
    <span>الرياض · جدة · الدمام</span>
    <span>NOVAKEYS.STORE</span>
  </div>
</footer>
    <?php
}, 5);
