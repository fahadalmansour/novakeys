# theme-bridge.php + chrome pipeline — map

`plugins/novakeys-commerce/includes/theme/theme-bridge.php` (2,394 lines) plus
`assets/chrome/{neogen.css,neogen.js}` (1,073 + 59 lines). Single largest file
in the plugin. Owns sitewide visual chrome and replaces most of the parent
Blocksy theme's header / footer / template hierarchy at runtime via WP filters.

## Purpose

Replace Blocksy's own chrome with NovaKeys-branded sitewide markup, route a
fixed set of legal / info URLs (`/legal/`, `/about/`, `/shipping/`, `/returns/`,
`/warranty/`, `/terms/`, `/privacy/`, `/usage/`, `/contact/`) through a single
template, override targeted WooCommerce template parts, emit Schema.org
JSON-LD + GTM + favicon set, and inject the operator-console hero section
on the front page. The bridge is a **phase-2 step** between the legacy
`mu-plugins/`-based chrome and the eventual FSE template hierarchy
(`theme-bridge.php:13–16`). Symbol prefix is mid-migration: new code uses
`nk_*`, leftover symbols (`ng_*` closures, `_ng_*` postmeta, `NG_*` constants)
are intentionally preserved for live-data compatibility.

## Public surface

### Constants

| Symbol | Where | Notes |
|---|---|---|
| `NOVAKEYS_THEME_VERSION` | `theme-bridge.php:24–26` | `1.5.9`. Used as a rewrite-flush key (`novakeys_rewrites_flushed_<ver>`). |
| `NK_CR` | `theme-bridge.php:35–106` | CR record literal. Owner / phones / email / parent-LLC block / regulatory list (ZATCA + Council of Saudi Chambers). The Latin brand is `NovaKeys Store` and the Arabic brand is intentionally `نيوجين ستور` (NeoGen Store) — the IP clause at `theme-bridge.php:1083–1084` lists both as official; this is a deliberate Latin-Arabic split, not a stale literal. |
| `NG_THEME_ASSET_DIR` | `theme-bridge.php:1487–1496` | `__DIR__ . '/neogen-theme-assets'`. Legacy NG prefix kept; phase-4 cleanup didn't rename. |
| `NG_THEME_ASSET_URL` | `theme-bridge.php:1497–1499` | URL form via `content_url()`. |

### Public functions

| Function | Lines | Returns |
|---|---|---|
| `nk_cr()` | 112–119 | CR data, filtered through `novakeys_org_data`. **Always use this — never `NK_CR` directly.** |
| `nk_ar_label( $s )` | 128–136 | Strips `English | Arabic` → AR side when AR side contains AR characters. |
| `nk_top_product_cats( $limit = 6 )` | 172–203 | Top product_cats by SKU count, transient-cached 1 h, excludes `homelab` + `uncategorized` by default. |
| `nk_primary_product_cat_slug( $product )` | 226–251 | Resolves Rank Math → Yoast → alphabetical-first product_cat. |
| `nk_category_image_fallback( $slug ) ` | 271–319 | Slug → image URL via filter map → `img/cat/<slug>.svg` → `img/brands/<slug>/_default.{webp,jpg,png}` → `img/cat/_default.svg`. |
| `nk_shop_category_tiles_top()` / `_bottom()` / `nk_shop_category_tiles()` | 336–433 | Rack of 6 category cards. Top placement on bare `/shop/`, bottom on `/product-category/.../`. |
| `nk_gift_cards_archive_extras()` | 491–549 | Gift-cards-only region tabs + trust strip above the product loop. |
| `nk_gift_cards_brand_grid()` | 567–709 | Three-lane brand grid (Game / App Stores / Subscriptions) above the gift-cards loop. Direct `$wpdb` query keyed on `_ng_gift_card_brand`. |
| `nk_info_pages()` | 720–1291 | Static registry of 8 legal/info pages (about, shipping, returns, warranty, terms, privacy, usage, contact). Bilingual content built from `nk_cr()`. |
| `nk_info_para_html( $s )` | 1303–1310 | Wraps a body string in `<p>` unless it's already a block-level element; runs through `wp_kses_post()`. |
| `nk_render_info_page_content( $key )` | 1322–1367 | Returns assembled HTML for one info page, used by virtual-post injection. |
| `nk_newsletter_subscribe_handler()` | 2156–2206 | Admin-post handler. Nonce-verified, IP-rate-limited, stores in `nk_newsletter_subscribers` option, fires `nk_newsletter_subscribe` action. |

### Hooks registered (42 total in `theme-bridge.php`)

**Bilingual title** — `term_name`, `single_term_title`, `single_cat_title`, `list_cats`, `woocommerce_product_title`, `the_title` (scoped to `product`), `woocommerce_breadcrumb_main_term` — all at priority 5 (lines 141–161).

**Top-cats cache bust** — `edited_product_cat`, `created_product_cat`, `delete_product_cat` (lines 215–217).

**Shop archive injectors** — `woocommerce_before_shop_loop` priorities 5 (top tiles), 8 (gift-cards extras), 9 (gift-cards brand grid); `woocommerce_after_shop_loop` priority 15 (bottom tiles) (lines 333–334, 490, 566).

**Gift-cards filter** — `pre_get_posts` adds `_ng_gift_card_region` / `_ng_gift_card_brand` meta_query when `?region=` / `?brand=` is present on `/product-category/gift-cards/` and descendants (lines 452–488).

**Virtual page injection** — `the_posts` priority 1 + ‘init’ (rewrite reg) + `query_vars` + `wp` + `pre_get_document_title` priority 999 + `template_include` priority 1001 (lines 1379–1885).

**WC template overrides** — `wc_get_template_part` for `content-product.php` / `content-single-product.php`; `wc_get_template` for full Woo template paths (cart / checkout / emails / myaccount); `init` priority 20 to drop `woocommerce_template_loop_product_thumbnail` (lines 1896–1952).

**Product editor** — `woocommerce_product_options_general_product_data` adds the `_ng_ar_title` text input; `woocommerce_process_product_meta` saves it (lines 1960–1980).

**Category-archive header** — `woocommerce_archive_description` priority 5 (lines 1987–2008).

**Front-page swap** — `template_include` priority 99 routes `is_front_page()` to `<asset_dir>/templates/front-page.php` (lines 2014–2026). Plus `[novakeys_home_sections]` fallback shortcode (lines 2033–2053).

**Chrome injection** — `wp_body_open` (sysbar + topnav, lines 2059–2142), `wp_footer` priority 5 (footer, lines 2224–2393), `woocommerce_add_to_cart_fragments` for cart count (lines 2213–2218). All gated by `ng_blocksy_chrome_handoff()` external function so Blocksy can take over.

**Newsletter** — `admin_post_ng_newsletter_subscribe` + `_nopriv_*` (lines 2207–2208). Note the action name keeps the `ng_` prefix for the form's `name="action"` field — renaming would break in-flight submissions.

**Head emissions** — `wp_enqueue_scripts` priority 20 (chrome CSS/JS, fonts, lines 1510–1538); `wp_head` at priorities 0 (light-mode forcing), 1 (favicon set), 1 (GTM), 2 (preconnects + theme-color), 5 (Schema.org JSON-LD), 99 (info-page inline CSS).

### Admin-post / AJAX endpoints

| Action | Handler | Purpose |
|---|---|---|
| `admin_post_ng_newsletter_subscribe` (auth + nopriv) | `nk_newsletter_subscribe_handler` | Footer email opt-in. |

### Shortcodes

| Tag | Purpose |
|---|---|
| `[novakeys_home_sections]` | Fallback when front page is a static WP page; renders the front-page template body. |

### Filters offered for downstream code

| Filter | Default | Purpose |
|---|---|---|
| `novakeys_org_data` | `NK_CR` | Override CR fields from a mu-plugin without editing the constant. |
| `novakeys_top_cats_exclude_slugs` | `['homelab', 'uncategorized']` | Hide slugs from the top-cats nav. |
| `novakeys_theme_category_image_fallbacks` | `['gift-cards' => playstation.webp]` | Map slug → image URL fallback. |
| `novakeys_theme_category_icons` | `[]` | Map slug → SVG icon for the rack. |
| `novakeys_org_jsonld_graph` | full @graph | Override / extend Schema.org emission. |

### Action hooks fired by this file

| Action | Args | Purpose |
|---|---|---|
| `nk_newsletter_subscribe` | `string $email` | New email added to subscriber list — ESP integrations hook here. |

## Data model

| Storage | Key | Set by | Read by |
|---|---|---|---|
| transient | `novakeys_top_cats_<n>` | `nk_top_product_cats()` line 175 | same; busted on category CRUD |
| option | `novakeys_rewrites_flushed_<ver>` | `init` rewrite registration line 1764 | same |
| option | `nk_newsletter_subscribers` | `nk_newsletter_subscribe_handler` line 2188 | same |
| option | `nk_gtm_container_id` (preferred) / `ng_gtm_container_id` (legacy fallback) | external admin UI | wp_head GTM script |
| transient | `nk_news_<md5(ip)>` | newsletter rate-limit line 2201 | same; 1 min TTL |
| postmeta | `_ng_ar_title` | product editor field (line 1973) | content-product.php override |
| postmeta | `_ng_gift_card_region` | external bulk-import script | `pre_get_posts` filter line 475 |
| postmeta | `_ng_gift_card_brand` | external bulk-import script | brand-grid query line 575, `pre_get_posts` line 482 |
| query var | `novakeys_page` | `init` rewrite + `query_vars` filter | `template_include`, `pre_get_document_title`, `the_posts` virtual injection |

## Flow

### Virtual legal/info routes

```
GET /terms/
        │
        ├─ rewrite rule (init):   ^terms/?$ → index.php?novakeys_page=terms
        │     [theme-bridge.php:1748–1768]
        │
        ├─ pre_get_document_title (prio 999): override "Page Array – Site"
        │     → "Terms And Conditions — NovaKeys Store"
        │     [theme-bridge.php:1800–1814]
        │
        ├─ wp action: flip is_home/is_archive/is_404 off, set pagename
        │     so FSE picks page-{slug}.html
        │     [theme-bridge.php:1780–1795]
        │
        ├─ the_posts filter (prio 1): inject synthetic WP_Post(ID=0) with
        │     content from nk_render_info_page_content('terms')
        │     [theme-bridge.php:1379–1453]
        │
        ├─ template_include (prio 1001): route to
        │     <asset_dir>/templates/info-page.php
        │     [theme-bridge.php:1822–1885]
        │
        └─ wp_head (prio 99): emit scoped .nk-info-page inline CSS
              [theme-bridge.php:1460–1485]
```

### Shop archive injection

```
GET /shop/
        │
        ├─ woocommerce_before_shop_loop @ 5  → nk_shop_category_tiles_top()
        │     renders <section class="ng-section ng-shop-cats">
        │     6 .ng-rack-unit cards — uses nk_top_product_cats(6)
        │
        ├─ woocommerce_before_shop_loop @ 8  → nk_gift_cards_archive_extras()
        │     [skipped on /shop/ — gates on is_product_category('gift-cards')]
        │
        ├─ woocommerce_before_shop_loop @ 9  → nk_gift_cards_brand_grid()
        │     [skipped — same gate]
        │
        ├─ <product loop> (theme/Woo)
        │
        └─ woocommerce_after_shop_loop @ 15  → nk_shop_category_tiles_bottom()
              [skipped on /shop/ — gates on is_product_category]


GET /product-category/gift-cards/
        │
        ├─ before_shop_loop @ 5   → top tiles (skipped — `is_product_category('gift-cards')` early-returns)
        ├─ before_shop_loop @ 8   → region tabs + trust strip (renders)
        ├─ before_shop_loop @ 9   → 3-lane brand grid (renders if no ?brand=)
        ├─ <product loop>
        └─ after_shop_loop @ 15   → cross-nav rack (renders) [theme-bridge.php:341–433]
```

## Dependencies

- **Parent theme:** Blocksy. Uses `ng_blocksy_chrome_handoff()` and `ng_blocksy_dark_mode_allowed()` external functions (not defined here) to no-op the chrome injection / light-mode forcing when handoff is enabled — both Phase-2b/2c gates.
- **WooCommerce:** archive hooks, `wc_get_page_permalink`, `wc_get_template`, `wc_get_template_part`, `WC()->cart`, `wc_get_product`. HPOS-irrelevant — no order data accessed here.
- **Rank Math / Yoast SEO:** read-only — `nk_primary_product_cat_slug()` reads their primary-cat postmeta. SEO-engine mu-plugin (`ng_seo_engine_enabled()`) takes over Schema.org emission when active.
- **Plugin constants:** `NK_COMMERCE_DIR`, `NK_COMMERCE_URL`, `NK_COMMERCE_VERSION` from the bootstrap (`includes/class-plugin.php`, not in this file).
- **Asset bundle:** `<plugin>/assets/chrome/{neogen.css,neogen.js,nk-mark.svg}` (rebuilt in `feat(chrome): rebuild neogen.css/js inside the plugin, drop legacy mu-plugins refs` 05e07b3) plus `<plugin>/includes/theme/neogen-theme-assets/` (templates + icons + img/).
- **Google Fonts:** Chakra Petch, IBM Plex Mono, Major Mono Display, Manrope, Rakkas, Reem Kufi, Tajawal — enqueued at line 1513.
- **External chrome JS API:** none; the file is self-contained 59 lines (`assets/chrome/neogen.js`).

## Constraints

1. **Postmeta keys must remain `_ng_*`-prefixed.** Renaming `_ng_ar_title`, `_ng_gift_card_region`, `_ng_gift_card_brand` would orphan live product data and break: Woo product editor field, content-product.php override, gift-cards `pre_get_posts` filter, brand-grid SQL. Documented intent (CLAUDE-style header at lines 13–16: "rename `ng_*` symbols to `nk_*` with compat shims" — postmeta excluded).
2. **`NK_CR['brand_ar']` is intentionally `'نيوجين ستور'`** at line 39 (deliberate Latin-Arabic brand split, confirmed 2026-05-08). The IP clause at lines 1083–1084 lists both `"NovaKeys"` and `"نيوجين ستور"` as official names, and the same Arabic string appears across the legal-page ledes (returns / warranty / terms / privacy) and in `class-rank-math-bridge.php` / `class-text-routes.php`. Don't auto-replace.
3. **Newsletter form action name `ng_newsletter_subscribe`** must stay — the form posts to `admin-post.php?action=ng_newsletter_subscribe` and a rename breaks any in-flight POST and any cached page.
4. **Chrome injectors (`wp_body_open`, `wp_footer`@5)** must run before / after Blocksy's own; they assume Blocksy's header/footer are CSS-hidden. Toggling `ng_blocksy_chrome_handoff()` ON without un-hiding Blocksy gives a missing header.
5. **template_include priority 1001** is deliberate — runs after the active theme's priority-999 filter that otherwise blanket-rewrites `is_home()` requests to `app-shell.php` (clobbering the virtual-page route). Don't reduce this priority.
6. **Rewrite flush is keyed by version** — `NOVAKEYS_THEME_VERSION` must increment when adding a new info-page slug, otherwise the rule isn't flushed and the new slug 404s.
7. **GTM default `'GTM-PRTBSHTW'`** is a real container ID that ships when no override is set. Verify before re-using this code on a different store.

## Known issues

| Severity | Where | Issue |
|---|---|---|
| ~~HIGH~~ | `assets/chrome/neogen.js` | ~~`#ng-queue` indicator is **fake**~~ — **resolved 2026-05-07 in commit `4f9244f`**: queue line removed from the sysbar markup; `nudgeQueue` deleted from neogen.js. |
| MEDIUM | Whole file | No `__()` / `_e()` calls — visible Arabic strings are hard-coded. Cannot be localized to other languages without rewriting. **Deferred** — needs a translation-infrastructure decision first (text domain, translator workflow, whether the site even needs a non-Arabic locale). |
| ~~MEDIUM~~ | `theme-bridge.php:2298–2316` (newsletter) | ~~"Pretend success on rate-limit" reads undocumented~~ — **resolved 2026-05-08**: behaviour preserved (silent dedup is intentional UX) but the inline comment now spells out the trade-off explicitly. |
| ~~MEDIUM~~ | `theme-bridge.php:683` (brand-grid SQL) | ~~Direct `$wpdb->get_results()` bypasses object cache~~ — **resolved 2026-05-08**: wrapped in `wp_cache_get` / `wp_cache_set` (group `nk_commerce`, key `nk_gc_brand_grid_rows_v1`, 5-minute TTL); `$nk_bust_cats` busts the key on category CRUD. |
| ~~MEDIUM~~ | `theme-bridge.php:210` | ~~Closure `$ng_bust_cats` still uses `ng_` prefix~~ — **resolved 2026-05-08**: renamed to `$nk_bust_cats`. |
| LOW | `theme-bridge.php:1487–1499` | `NG_THEME_ASSET_DIR` / `_URL` constants still NG-prefixed; `__DIR__ . '/neogen-theme-assets'` is the underlying path. **Deferred** — multi-file rename + on-disk dir move. |
| ~~LOW~~ | `theme-bridge.php:1571–1596` (was `1466–1483`) | ~~Inline `<style id="nk-info-page-css">` adds render-blocking CSS on the info routes~~ — **resolved 2026-05-08**: rules moved to `assets/chrome/neogen.css` under the "Legal / info pages — `.nk-info-page`" section; the wp_head emission was retired. |
| ~~LOW~~ | `theme-bridge.php:2065–2070` | ~~`remove_action` for the WC loop thumbnail silently no-ops if the callback is renamed/re-prioritised~~ — **resolved 2026-05-08**: wrapped in `function_exists( 'woocommerce_template_loop_product_thumbnail' )` guard. |
| INFO | Whole file | 2,394 lines is too large for a single file. Natural seams: brand identity / nk_cr (1–119), nk_ar_label + filters (120–162), top-cats + image fallback (163–319), shop tiles (320–433), gift-cards extras (434–709), info-pages registry + virtual-post + render (710–1485), asset paths + chrome enqueue + favicons + GTM (1486–1638), Schema.org (1639–1741), virtual routes (1742–1885), WC template overrides (1886–1952), product-meta + archive header + front-page (1953–2053), sysbar/topnav (2054–2142), newsletter (2143–2208), cart fragment + footer (2209–2393). |

## Citations

Every claim above has a `theme-bridge.php:<line>` or `assets/chrome/<file>:<line>` reference. The file lengths and hook counts are from `wc -l` / `grep -c` over the head commit (current HEAD: `bf2fd80`).

The `bf2fd80` commit was made during this study session to address a related issue: missing CSS for `.ng-rack-unit` cards rendered by `nk_shop_category_tiles()`. See `learnings.md` 2026-05-07.
