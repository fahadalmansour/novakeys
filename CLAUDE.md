# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

NovaKeys is a Saudi gift-card / software-key WooCommerce store. The repo is a **WordPress block theme + companion plugin** dropped into a WP install — no core, no `wp-config.php`, no `wp-content/` tree.

Two deployable trees:

- **`themes/novakeys/`** — FSE block theme. Owns the visual layer: `theme.json` design tokens (colour, typography, spacing), block templates, parts, patterns. Slim `functions.php` (theme support + enqueue only).
- **`plugins/novakeys-commerce/`** — companion plugin. Owns commerce + chrome logic: gift-card pipeline (matcher, key vault, customer endpoint, refund revoker, bootstrap admin), NK Points loyalty + REST endpoints, vouchers shortcode, SEO/security headers, recommendations, product-meta metabox, theme-bridge fallbacks for chrome WP can't render via FSE blocks.

The codebase was split out of NeoGen Store on 2026-05-07. Phase 1 scaffolded the plugin; phase 2 migrated all 11 mu-plugins into the plugin's `includes/<module>/` tree and renamed `ng_*` symbols to `nk_*` (postmeta keys + cookies + REST namespace stayed verbatim — those are live-data contracts). Phase 3 lands the FSE theme. Phase 4 is cleanup. See `.claude/plans/gentle-kindling-biscuit.md`.

**Engineering standards:** every PHP commit follows `.claude/skills/wordpress.md` — WPCS, Yoda conditions, sanitize-on-input + late-escape, nonces on every state mutation, capability checks, WC CRUD over postmeta, HPOS-compatible. Trigger an audit anytime with *"Audit the current file using the standards in .claude/skills/wordpress.md."*

## Layout

- `themes/novakeys/` — FSE block theme.
  - `theme.json` — design tokens (8-colour palette anchored on `#38BDF8`, three font families, fluid typography, 7-step spacing scale, 1100px content / 1280px wide).
  - `style.css` — theme metadata header (no body styles).
  - `functions.php` — theme support flags + `wp_enqueue_*` for `assets/app.js` (when present); no `template_include` filters.
  - `templates/` — `index.html`, `page.html`, `page-legal.html`, `single.html`, `search.html`, `404.html`. Phase 3 ships only the basics; Woo block templates (`single-product`, `archive-product`, `cart`, `checkout`, `my-account`) inherit Woo defaults + overrides from the companion plugin until block versions land.
  - `parts/` — `header.html`, `footer.html`. Site-wide chrome pulled from the plugin's `theme-bridge.php` via `wp_body_open` / `wp_footer` hooks.
  - `patterns/` — `legal-disclosure.php` (MOC identity readout fed by `nk_cr()`).
- `plugins/novakeys-commerce/` — companion plugin.
  - `novakeys-commerce.php` — bootstrap, version constant, activation/deactivation hooks.
  - `includes/class-plugin.php` — singleton; loads modules in dependency order.
  - `includes/compat/class-ng-shims.php` — `function_exists`-guarded `ng_*` aliases for back-compat.
  - `includes/migrations/class-option-migrator.php` — one-shot `ng_*` → `nk_*` option-key rename on activation.
  - `includes/<module>/` — one folder per module (gift-cards, loyalty, seo, site, recommendations, vouchers, icons, product-meta, security, theme).
- `scripts/` — one-off WP-CLI utilities (`wp eval-file`, not web-accessible).
- `snippets/gift-cards-header.php` — gift-card picker + region selector. Standalone snippet, not auto-loaded.
- `tests/test-gift-card-matcher.php` — single plain-PHP smoke test (no PHPUnit). Loads the matcher + compat shims.
- `data/migrated-from-neogen-*/` — migration audit log + CSVs. Documentation only; no code reads from it.
- `assets/` — brand SVGs/webps consumed by the asset matcher.
- `.github/workflows/php.yml` + `composer.json` — minimal manifest (`php >=8.0`, no deps) so CI's `composer validate --strict` passes.

## Plugin module map

Under `plugins/novakeys-commerce/includes/`:

- `gift-cards/` — the backbone.
  - `gift-cards-matcher.php` — `nk_gift_card_asset_map()` + 11 helpers + 8 WC filter callbacks. Procedural global-namespace because filter registrations reference callback names by string. Postmeta `_ng_gift_card_brand` (preserved verbatim) is the per-product override.
  - `class-vault.php` — AES-256-GCM (`enc:v2:`) for new writes; AES-256-CTR (`enc:v1:`) read back-compat. Key derived from `wp_salt('logged_in')`.
  - `class-store.php` — `Store::set_code()` / `Store::get_keys_for_user()`. Per-line-item meta: `_ng_gift_card_code`, `_ng_gift_card_status` (pending/active/consumed/revoked), `_ng_gift_card_expires_at`, `_ng_gift_card_brand`, `_ng_gift_card_region`.
  - `class-admin.php` — order-edit metabox (HPOS-aware). `nk_gck_save_<order_id>` nonce + `edit_shop_orders` cap.
  - `class-refund-revoker.php` — flips status to `revoked` on `woocommerce_order_status_changed` (terminal statuses) AND `woocommerce_order_refunded` (partial refunds). Idempotent.
  - `class-customer-endpoint.php` — `/my-account/gift-card-keys/` (`NK_GCK_ENDPOINT`). Bilingual EN/AR labels, copy-to-clipboard, status pills.
  - `class-bootstrap-tool.php` — Tools → NovaKeys Gift Cards · Bootstrap. Idempotent product seeder (creates drafts only). Page slug `neogen-gift-cards-bootstrap` preserved as URL contract.
  - `gift-card-keys-functions.php` — procedural wrappers (`nk_gck_*`, `nk_gift_card_set_code`, `nk_get_gift_card_keys`).
- `loyalty/` — NK Points + referral + share-to-unlock coupon.
  - `class-points.php` — 10 pts/SAR (2× for `nk_is_premium`), `NK_WELCOME_POINTS=50`, `NK_REFERRAL_POINTS=250`. Idempotent `_nk_points_awarded` order-meta written via WC CRUD.
  - `class-points-rest.php` — `GET /wp-json/nk/v1/points` (login required).
  - `class-referral.php` — `?ref=u<ID>` URL handler + `GET /wp-json/nk/v1/referral/<code>`. **Strict `^u\d+$` validation** (audit-2 fix), per-IP rate limit (10/min), Lax/HttpOnly cookie. Self-referral blocked.
  - `class-coupon-rest.php` — `POST /wp-json/nk/v1/coupon`. **Login required + per-user 1/24h transient throttle** (audit-1 fix). 10% off, single-use, expires next day. WC_Coupon CRUD writes.
  - `class-gift-mailer.php` — gift email + WhatsApp note on order completion. **`esc_html()` on `$gift_phone`, `esc_url()` on the WhatsApp deep link** (audit-4 fix).
  - `loyalty-functions.php` — `nk_get_points()`, `nk_add_points()`.
- `seo/` — security headers + text routes + Rank Math bridge + legacy host rewriter.
  - `class-headers.php` — CSP / HSTS / X-Frame / Referrer / Permissions. CSP enforcement via `NK_CSP_ENFORCE` (legacy `NG_CSP_ENFORCE` honoured). `nk_csp_directives` filter.
  - `class-text-routes.php` — `/ads.txt` (option `nk_adsense_client_id`), `/llms.txt`, `robots.txt` citation-crawler filter.
  - `class-legacy-host-rewriter.php` — `ngs1.blazr.net` → `novakeys.store` rewrites across content/widgets/menus/Rank Math sitemap.
  - `class-rank-math-bridge.php` — homepage title/description/robots/canonical, JSON-LD entity scrubber (drops Person, demo.local, dup Store/WebSite), Twitter card cleanup, OG image emission, author display rewrite.
  - `seo-functions.php` — `nk_home_*`, `nk_seo_rewrite_legacy_host()`, `nk_og_image_url()`, `nk_twitter_image_url()`.
- `site/class-customizations.php` — timezone lock to Asia/Riyadh, admin-bar version badge, WC compat sentinel, public WC REST read opener (paired with `security/class-mcp-meta-guard.php` to scrub `_ng_*` meta from REST), `window.NK` bootstrap.
- `recommendations/class-recommender.php` — recently-viewed cookie (`ng_recent`, 30d, HttpOnly, SameSite=Lax) + rule-based recs. Shortcodes: `[nk_recommendations]` + legacy `[neogen_recommendations]` alias.
- `vouchers/class-shortcode.php` — `[nk_vouchers]` filterable voucher gallery (6 categories, RTL-aware). Brand artwork URL filterable via `nk_vouchers_brand_url_base`.
- `icons/class-icon-registry.php` — 44-icon SVG sprite + `nk_icon()` / `nk_icon_use()` / `nk_icon_sprite()`. CSS classes (`ngrd-icon`) and sprite IDs preserved for stylesheet compat.
- `product-meta/class-arabic-title.php` — Arabic-title metabox on product edit; writes `_ng_ar_title` via WC CRUD.
- `security/class-mcp-meta-guard.php` — strips `_ng_gift_card_*` from outbound REST/MCP responses (defense-in-depth for the WC REST public-read opener).
- `theme/theme-bridge.php` — sitewide chrome consolidation (taxonomy ordering, shop category tiles, gift-cards archive helpers, info-page registry, legal/info-page virtual routes with the priority-1001 routing fix, Schema.org Store JSON-LD, sysbar/header/footer markup, WC template overrides). Phase 3 will keep eating into this as more chrome moves into FSE patterns.

**Shared `_ng_*` postmeta namespace** ties the gift-card pipeline together. Keep that prefix for new gift-card meta — postmeta keys are a live-data contract.

## Common operations

- **Run the matcher smoke test:** `php tests/test-gift-card-matcher.php`. WP-less; uses inline stubs.
- **Run a one-off WP-CLI utility:** `wp eval-file scripts/<name>.php --user=1` from the WP install root.
  - `neogen-gift-cards-bulk.php` — bulk-creates ~70 SKUs across ~14 brands. Idempotent by SKU. USD → SAR at 3.75 + 7% markup.
  - `neogen-reprice-gift-cards.php` — repricing pass to a 20% gross-margin floor; backs original to `_ng_pre_reprice_regular_price`.
  - `neogen-amazon-sa-reprice-gc.php` — KSA Amazon-specific repricing.
  - `neogen-delete-netflix.php` — one-time Netflix product purge.
  - `neogen-gift-cards-brand-cats.php` — generates brand sub-terms under the `gift-cards` `product_cat`.
- **Bootstrap products from assets:** WP Admin → Tools → NovaKeys Gift Cards · Bootstrap.
- **Build / lint:** none. No npm, no Makefile. `composer validate --strict` runs in CI.

## Project-specific gotchas

- **`_ng_*` postmeta keys never rename.** They back live order/product data. CI grep should fail any new `update_post_meta($x, '_nk_gift_card_*'`.
- **`enc:v1:` decrypt path stays forever.** Existing customer gift-card codes are encrypted under v1 (CTR); new writes use v2 (GCM). Removing v1 read = breaking live keys.
- **Procedural functions used as filter callbacks must be in the global namespace.** When adding to `gift-cards-matcher.php` or extending `theme-bridge.php`, don't wrap in a `namespace ...;` declaration.
- **Cookie names `ng_recent` and `nk_ref` are data contracts.** Customers carry these from prior sessions; renaming evicts referral attribution and recently-viewed history.
- **REST namespace `nk/v1/` is canonical.** Do not introduce `ng/v1/`.
- **The bootstrap tool never publishes products and never sets prices.** Operator publishes manually after pricing — intentional.
- **Order-item gift-card codes are encrypted at rest.** Don't `error_log()` raw codes; always go through `nk_get_gift_card_keys()` / `Vault::decrypt()`.
- **Timezone is force-locked to Asia/Riyadh** by `site/class-customizations.php`. The WP admin Settings → General timezone field is effectively read-only.

## Operator-blocked items (publish-readiness)

Tracked in the Notion publish-readiness study. Three items still require operator action and cannot be completed by code alone:

- **B2 — Legal copy sign-off.** `theme/theme-bridge.php`'s `nk_info_pages()` returns the policy registry. Counsel must finalise the body text and remove the `ng-pending` "draft" chips.
- **G3 — CSP enforcement.** Define `NK_CSP_ENFORCE` true in `wp-config.php` after a clean reporting window. Plugin already reads both `NK_CSP_ENFORCE` and legacy `NG_CSP_ENFORCE`.
- **G6 — AdSense publisher ID.** Set option `nk_adsense_client_id` to the `pub-XXXXXXXXXXXXXXXX` value (or wire AdSense via Site Kit). `/ads.txt` falls back to a placeholder until then.

## Pre-flight checklist (binding before any commit) — added 2026-05-07

Every PHP commit goes through the standards contract before merge:

- **Trigger:** *"Audit the current file using the standards in `wordpress-engineer` and fix any violations."*
- **Skill stack loaded:** `wordpress-engineer` (user-scope, master) → `wordpress` (project-local, NovaKeys gotchas) → `woocommerce-specialist` (when the file touches Woo).
- **Slash command:** `/check-all` (runs the auditor against current file or branch arg).
- **Auditor agent:** `wp-woo-standards-auditor` (with persistent memory at `~/.claude/agent-memory/wp-woo-standards-auditor/`).
- **Cutover guard:** if `.cutover-active` exists at repo root, audit refuses (Phase-4 is done; this should never trigger now).

## Repo-readiness backlog

- Latest readiness audit: `~/.claude/reports/novakeys/readiness-2026-05-07.md`
- Open BLOCKERs: B1 `<html lang>` mismatch, B2 cart `aria-label="state.*"` literals, B3 LSCache off, B4 legal copy sign-off, B5 `sanitize_key` ordering bug in referral, B6 xmlrpc reachable, B7 wp-cron public, B8 `.gitignore` missing.
- See full HIGH/MEDIUM list in the report.
