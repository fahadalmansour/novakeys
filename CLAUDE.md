# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

NovaKeys is a Saudi gift-card / software-key WooCommerce store. The repo is a **WordPress block theme + companion plugin** dropped into a WP install — no core, no `wp-config.php`, no `wp-content/` tree.

Two deployable trees:

- **`themes/novakeys/`** — FSE block theme. Owns the visual layer: `theme.json` design tokens (colour, typography, spacing), block templates, parts, patterns. Slim `functions.php` (theme support + enqueue only).
- **`plugins/novakeys-commerce/`** — companion plugin. Owns commerce + chrome logic, plus PDPL cookie consent and the vault key rotation flow. Owns most of the runtime behaviour: gift-card pipeline (matcher, key vault with rotatable enc:v3:, customer endpoint, refund revoker, bootstrap admin), NK Points loyalty + REST endpoints, vouchers shortcode (typographic monograms, no third-party brand art), SEO/security headers, recommendations (consent-gated `ng_recent`), product-meta metabox, user-enum shield, PDPL cookie banner + per-category consent gate, theme-bridge (legal-page virtual-post renderer, sysbar/topnav/footer chrome, newsletter form + notice, locale-aware `<html lang>`).

The codebase was split out of NeoGen Store on 2026-05-07. Phase 1 scaffolded the plugin; phase 2 migrated all 11 mu-plugins into the plugin's `includes/<module>/` tree and renamed `ng_*` symbols to `nk_*` (postmeta keys + cookies + REST namespace stayed verbatim — those are live-data contracts). Phase 3 lands the FSE theme; phase 4 is cleanup. Phase 5 (this session) added PDPL consent + vault rotation + shipped 5 design-audit rounds + the readiness 2026-05-08 batch. See `.claude/plans/gentle-kindling-biscuit.md` for the latest plan.

**Engineering standards:** every PHP commit follows `.claude/skills/wordpress.md` — WPCS, Yoda conditions, sanitize-on-input + late-escape, nonces on every state mutation, capability checks, WC CRUD over postmeta, HPOS-compatible. Trigger an audit anytime with *"Audit the current file using the standards in .claude/skills/wordpress.md."*

## Layout

- `themes/novakeys/` — FSE block theme.
  - `theme.json` — design tokens (10-colour palette anchored on `#38BDF8`; `brand-link` `#0369A1` is the AA-compliant body-text link colour). Three font families, fluid typography, 7-step spacing scale, 1100px content / 1280px wide. `styles.elements.link.color.text` routed through `brand-link`.
  - `style.css` — theme metadata header (no body styles).
  - `functions.php` — theme support flags + `wp_enqueue_*` for `assets/app.js` (when present); no `template_include` filters.
  - `templates/` — `index.html`, `page.html`, `page-legal.html`, `page-my-account.html`, `single.html`, `search.html`, `404.html`, `cart.html`, `checkout.html`, `single-product.html`, `archive-product.html`, `taxonomy-product_cat.html`. Most Woo block templates use `wp:woocommerce/legacy-template` so classic WC hooks still fire.
  - `parts/header.html`, `parts/footer.html` — empty placeholders (chrome lives in the plugin).
  - `patterns/legal-disclosure.php` — MOC + NTS LLC identity readout fed by `nk_cr()`.
- `plugins/novakeys-commerce/` — companion plugin.
  - `novakeys-commerce.php` — bootstrap, `NK_COMMERCE_*` constants, activation/deactivation hooks, HPOS declaration via `before_woocommerce_init`.
  - `includes/class-plugin.php` — singleton; loads modules in dependency order.
  - `includes/migrations/class-option-migrator.php` — one-shot `ng_*` → `nk_*` option-key rename on activation.
  - `includes/<module>/` — one folder per module. Currently: gift-cards, loyalty, seo, site, recommendations, vouchers, icons, product-meta, security, theme, consent.
  - `assets/chrome/` — `neogen.css` (1300+ lines), `neogen.js` (clock + newsletter pill auto-dismiss + manage-consent modal toggle), `nk-mark.svg` (brand mark).
- `scripts/` — one-off WP-CLI utilities (`wp eval-file`, not web-accessible).
- `snippets/gift-cards-header.php` — gift-card picker + region selector. Standalone snippet, not auto-loaded.
- `tests/test-gift-card-matcher.php` — 14-case plain-PHP smoke test (no PHPUnit). `tests/test-theme-render.php` — theme-side smoke test (loaded by hand).
- `data/migrated-from-neogen-*/` — migration audit log + CSVs. Documentation only; no code reads from it.
- `assets/` — brand SVGs/webps consumed by the gift-cards asset matcher.
- `docs/` — `legal-policies-draft-2026-05-07.md`, `operator-runbook-2026-05-08.md` (server-config items only the operator can run).
- `.github/workflows/php.yml` + `composer.json` — minimal manifest (`php >=8.0`, no deps) so CI's `composer validate --strict` passes.

## Plugin module map

Under `plugins/novakeys-commerce/includes/`:

- `gift-cards/` — the backbone.
  - `gift-cards-matcher.php` — `nk_gift_card_asset_map()` + 11 helpers + 8 WC filter callbacks. Procedural global-namespace because filter registrations reference callback names by string. Postmeta `_ng_gift_card_brand` (preserved verbatim) is the per-product override.
  - `class-vault.php` — three envelopes: `enc:v1:` AES-256-CTR (legacy, read-only), `enc:v2:` AES-256-GCM under `wp_salt('logged_in')` (read-only after 0.3.1), `enc:v3:` AES-256-GCM under `Vault_Key` (all new writes). decrypt_v3 tries current key, falls back to previous key during rewrap window.
  - `class-vault-key.php` — dedicated 32-byte random key in `nk_vault_key_v3_current` option (lazy-init on first encrypt). `rotate_to_new()` / `commit_rotation()` / `previous_key()` for the rotation flow. Audit log in `nk_vault_rotation_log`.
  - `class-vault-cli.php` — WP-CLI commands `wp nk vault status|rewrap|rotate`. Cursor-driven loop, 1h transient lock, idempotent.
  - `class-store.php` — `Store::set_code()` / `Store::get_keys_for_user()`. Per-line-item meta: `_ng_gift_card_code`, `_ng_gift_card_status` (pending/active/consumed/revoked), `_ng_gift_card_expires_at`, `_ng_gift_card_brand`, `_ng_gift_card_region`.
  - `class-admin.php` — order-edit metabox (HPOS-aware). `nk_gck_save_<order_id>` nonce + `edit_shop_orders` cap.
  - `class-refund-revoker.php` — flips status to `revoked` on `woocommerce_order_status_changed` (terminal statuses) AND `woocommerce_order_refunded` (partial refunds). Idempotent.
  - `class-customer-endpoint.php` — `/my-account/gift-card-keys/` (`NK_GCK_ENDPOINT`). Bilingual EN/AR labels, copy-to-clipboard via `replaceChildren()` + SVG, status pills.
  - `class-bootstrap-tool.php` — Tools → NovaKeys Gift Cards · Bootstrap. Idempotent product seeder (creates drafts only). Page slug `neogen-gift-cards-bootstrap` preserved as URL contract.
  - `gift-card-keys-functions.php` — procedural wrappers (`nk_gck_*`, `nk_gift_card_set_code`, `nk_get_gift_card_keys`).
- `loyalty/` — NK Points + referral + share-to-unlock coupon.
  - `class-points.php` — 10 pts/SAR (2× for `nk_is_premium`), `NK_WELCOME_POINTS=50`, `NK_REFERRAL_POINTS=250`. Idempotent `_nk_points_awarded` order-meta written via WC CRUD.
  - `class-points-rest.php` — `GET /wp-json/nk/v1/points` (login required).
  - `class-referral.php` — `?ref=u<ID>` URL handler + `GET /wp-json/nk/v1/referral/<code>`. **Strict `^u\d+$` validation BEFORE any sanitisation** (audit-2 + readiness-B5 fix), per-IP rate limit (10/min), Lax/HttpOnly cookie. REST route regex tightened to `u\d+` + `validate_callback` so malformed input never dispatches. **Cookie write gated on `nk_consent_has('marketing')`.**
  - `class-coupon-rest.php` — `POST /wp-json/nk/v1/coupon`. Login required + per-user 1/24h transient throttle (audit-1 fix). 10% off, single-use, expires next day. WC_Coupon CRUD writes — postmeta fallback retired.
  - `class-gift-mailer.php` — gift email + WhatsApp note on order completion. `esc_html()` on `$gift_phone`, `esc_url()` on the WhatsApp deep link (audit-4 fix).
  - `loyalty-functions.php` — `nk_get_points()`, `nk_add_points()`.
- `seo/` — security headers + text routes + Rank Math bridge + legacy host rewriter.
  - `class-headers.php` — CSP / HSTS / X-Frame / Referrer / Permissions. CSP enforcement via `NK_CSP_ENFORCE` (legacy `NG_CSP_ENFORCE` honoured). `nk_csp_directives` filter.
  - `class-text-routes.php` — `/ads.txt` (option `nk_adsense_client_id`), `/llms.txt`, `robots.txt` citation-crawler filter.
  - `class-legacy-host-rewriter.php` — `ngs1.blazr.net` → `novakeys.store` rewrites across content/widgets/menus/Rank Math sitemap.
  - `class-rank-math-bridge.php` — homepage title/description/robots/canonical, JSON-LD entity scrubber. Drops Person + demo.local + dup Store/WebSite. **Patches `inLanguage` → `ar-SA` on every `*Page` node** (round-3 H3).
  - `seo-functions.php` — `nk_home_*`, `nk_seo_rewrite_legacy_host()`, `nk_og_image_url()`, `nk_twitter_image_url()`.
- `site/class-customizations.php` — timezone lock to Asia/Riyadh, admin-bar version badge, WC compat sentinel, public WC REST read opener (paired with `security/class-mcp-meta-guard.php` to scrub `_ng_*` meta from REST), `window.NK` bootstrap.
- `recommendations/class-recommender.php` — recently-viewed cookie (`ng_recent`, 30d, HttpOnly, SameSite=Lax) + rule-based recs. Shortcodes: `[nk_recommendations]` + legacy `[neogen_recommendations]` alias. **Cookie write gated on `nk_consent_has('functional')`.**
- `vouchers/class-shortcode.php` — `[nk_vouchers]` filterable voucher gallery (6 categories, RTL-aware). 18 cards rendered as keyboard-accessible `<a>` anchors (B1 round-4) with typographic 2-letter monograms (`.nk-card-mono`) — no third-party brand artwork bundled. The `nk_vouchers_brand_url_base` filter was retired in `3b1ab94`.
- `icons/class-icon-registry.php` — 44-icon SVG sprite + `nk_icon()` / `nk_icon_use()` / `nk_icon_sprite()`. CSS classes (`ngrd-icon`) and sprite IDs preserved for stylesheet compat.
- `product-meta/class-arabic-title.php` — Arabic-title metabox on product edit; writes `_ng_ar_title` via WC CRUD.
- `security/class-mcp-meta-guard.php` — strips `_ng_gift_card_*` from outbound REST/MCP responses (defense-in-depth for the WC REST public-read opener).
- `security/class-user-enum-shield.php` — `rest_endpoints` filter unsetting `/wp/v2/users` for guests; `template_redirect` priority 1 + `parse_request` shim that 301s `?author=N` and `?author_name=` to `home_url('/')`.
- `consent/class-cookie-consent.php` — PDPL cookie consent. `nk_cookie_consent` cookie (JSON v=1, 1-year TTL, JS-readable), `Cookie_Consent::has($cat)` / `is_set()` / `set()`, `admin_post_nk_consent_save` handler. Procedural shortcut `nk_consent_has()`. Categories: `necessary` (always-on), `functional` (gates `ng_recent`), `marketing` (gates `nk_ref`).
- `theme/theme-bridge.php` — sitewide chrome consolidation. Owns: taxonomy ordering, shop category tiles, gift-cards archive helpers (also fired on `woocommerce_no_products_found` for empty archives + via `render_block_woocommerce/product-collection` filter for FSE block archives), info-page registry (`nk_info_pages()`), legal/info-page virtual-post renderer (`the_posts` filter — synthesises a `WP_Post` so FSE `page.html` renders the body), Schema.org Store JSON-LD (BCP-47 `availableLanguage`), sysbar/topnav/footer markup, locale-aware `<html lang>` filter, newsletter form + AR-first feedback pill, PDPL consent banner + manage modal, WC template overrides, deprecated `wp_get_attachment_image_attributes` filter to swap WC placeholder alt to product title.

**Shared `_ng_*` postmeta namespace** ties the gift-card pipeline together. Keep that prefix for new gift-card meta — postmeta keys are a live-data contract.

## Common operations

- **Run the matcher smoke test:** `php tests/test-gift-card-matcher.php`. WP-less; uses inline stubs.
- **Run a one-off WP-CLI utility:** `wp eval-file scripts/<name>.php --user=1` from the WP install root.
  - `neogen-gift-cards-bulk.php` — bulk-creates ~70 SKUs across ~14 brands. Idempotent by SKU. USD → SAR at 3.75 + 7% markup.
  - `neogen-reprice-gift-cards.php` — repricing pass to a 20% gross-margin floor; backs original to `_ng_pre_reprice_regular_price`.
  - `neogen-amazon-sa-reprice-gc.php` — KSA Amazon-specific repricing.
  - `neogen-delete-netflix.php` — one-time Netflix product purge.
  - `neogen-gift-cards-brand-cats.php` — generates brand sub-terms under the `gift-cards` `product_cat`.
- **Vault key inspection / rotation (live):**
  - `wp nk vault status` — fingerprint, pending state, per-envelope counts, recent rotations.
  - `wp nk vault rewrap [--batch=N] [--source=v1|v2|all] [--dry-run]` — migrate v1/v2 records forward to v3.
  - `wp nk vault rotate [--batch=N]` — generate new key, rewrap everything to it, commit, log.
- **Bootstrap products from assets:** WP Admin → Tools → NovaKeys Gift Cards · Bootstrap.
- **Deploy:** `scripts/deploy-phase-4-to-live.sh` (scp working tree to `162.254.39.146:21098`). **Don't run while a dirty FSE-rewrite stash is unpopped — the script ships working files, not committed files.**
- **Build / lint:** none. No npm, no Makefile. `composer validate --strict` runs in CI.

## Project-specific gotchas

- **`_ng_*` postmeta keys never rename.** They back live order/product data. CI grep should fail any new `update_post_meta($x, '_nk_gift_card_*'`.
- **`enc:v1:` AND `enc:v2:` decrypt paths stay forever.** Existing customer gift-card codes are encrypted under v1 (CTR) or v2 (GCM under wp_salt). v3 (GCM under `Vault_Key`) is the new-write envelope. Removing v1 or v2 read = breaking live keys.
- **Vault key lives in `nk_vault_key_v3_current` option, NOT in wp_salt.** Lazy-initialised on first encrypt. Rotating the option doesn't log users out (decoupled from auth). Back it up off-host (operator runbook §10).
- **Cookies `ng_recent` and `nk_ref` are written only after consent.** Recommender + Referral both call `Cookie_Consent::has()` before any setcookie. Fail-closed when no consent decision yet exists.
- **Procedural functions used as filter callbacks must be in the global namespace.** When adding to `gift-cards-matcher.php` or extending `theme-bridge.php`, don't wrap in a `namespace ...;` declaration.
- **Cookie names `ng_recent` / `nk_ref` / `nk_cookie_consent` are data contracts.** Customers carry these from prior sessions; renaming evicts referral attribution / recently-viewed history / consent state.
- **REST namespace `nk/v1/` is canonical.** Do not introduce `ng/v1/`.
- **The bootstrap tool never publishes products and never sets prices.** Operator publishes manually after pricing — intentional.
- **Order-item gift-card codes are encrypted at rest.** Don't `error_log()` raw codes; always go through `nk_get_gift_card_keys()` / `Vault::decrypt()`.
- **Timezone is force-locked to Asia/Riyadh** by `site/class-customizations.php`. The WP admin Settings → General timezone field is effectively read-only.
- **Site language is en-US base; `<html lang>` is locale-aware.** A `language_attributes` filter in `theme-bridge.php` reads `get_locale()` and emits `ar-SA` on `/ar/*` (TranslatePress flips locale) or `en-US` everywhere else. `dir` is always `ltr` until a separate site-wide RTL plan ships.
- **The `cookieadmin-pro` plugin was per-site deactivated 2026-05-07.** Files remain on disk. Don't reactivate without rewriting its banner copy — the GDPR text it ships violates the marketing-ksa-shared rule.

## Operator-blocked items (publish-readiness)

Live runbook for these: **`docs/operator-runbook-2026-05-08.md`**.

Only items still requiring operator action:

- **CSP enforcement (B3).** Define `NK_CSP_ENFORCE` true in `wp-config.php` after a clean reporting window.
- **AdSense publisher ID (G6).** `wp option update nk_adsense_client_id pub-XXXXXXXXXXXXXXXX`.
- **Disable wp-cron + system cron.** `define('DISABLE_WP_CRON', true)` + a `*/5 * * * *` curl to `/wp-cron.php`.
- **DMARC / DKIM / CAA.** DNS panel work; runbook §4–6.
- **LSCache plugin install.** Runbook §7.
- **Cloudflare CDN.** Runbook §8.
- **Uptime Kuma.** Runbook §9.
- **Vault-key off-host backup.** 5-min mitigation in runbook §10 (copy `nk_vault_key_v3_current` value to password manager).

**Resolved this session (commits in this stack):**
- ~~Legal copy sign-off~~ — bodies for Returns / Warranty / Terms / Privacy / Acceptable-Use ported (`977ec54`).
- ~~`<html lang>` mismatch~~ — locale-aware filter (`c62ba8f`).
- ~~B5 referral `sanitize_key` ordering~~ — validate before sanitize (`339b066` + `1330179`).
- ~~`.gitignore` missing~~ — added `cb5a617`.
- ~~User enumeration via REST + `?author=`~~ — shield (`1330179` + `5e4922d`).
- ~~Missing HPOS declaration~~ — `FeaturesUtil::declare_compatibility` (`1330179`).
- ~~Duplicate `<h1>` on category archives~~ — `woocommerce_show_page_title` filter (`c6fb067`).
- ~~PDP placeholder gallery `opacity:0`~~ — CSS override (`c6fb067`).
- ~~Phase-2 ZATCA wording overclaim~~ — walked back (`1ef24ce`).
- ~~Newsletter pill WCAG AA contrast~~ — emerald-600 / amber-700 (`e14b01d`).
- ~~Sky link contrast on paper body~~ — new `--nk-color-link` `#0369A1`, theme.json palette + `elements.link` (`c62ba8f` + `f951821`).
- ~~Voucher cards keyboard-inaccessible~~ — `<a>` anchors + focus ring (`a6937d1`).
- ~~Voucher brand `<img>` 404s~~ — typographic monograms (`3b1ab94`).
- ~~PDPL consent bypass~~ — Cookie_Consent module + banner + cookie gates (`d93286a`).
- ~~Vault key off-host rotation flow~~ — `Vault_Key` + `wp nk vault` CLI (`09072ab`).
- ~~5 design-audit rounds~~ — 60+ findings shipped, contrast/RTL/a11y/structure all closed.

## Pre-flight checklist (binding before any commit) — added 2026-05-07

Every PHP commit goes through the standards contract before merge:

- **Trigger:** *"Audit the current file using the standards in `wordpress-engineer` and fix any violations."*
- **Skill stack loaded:** `wordpress-engineer` (user-scope, master) → `wordpress` (project-local, NovaKeys gotchas) → `woocommerce-specialist` (when the file touches Woo).
- **Slash command:** `/check-all` (runs the auditor against current file or branch arg).
- **Auditor agent:** `wp-woo-standards-auditor` (with persistent memory at `~/.claude/agent-memory/wp-woo-standards-auditor/`).
- **Cutover guard:** if `.cutover-active` exists at repo root, audit refuses (Phase-4 is done; this should never trigger now).

## Repo-readiness backlog

- Latest readiness audit: `~/.claude/reports/novakeys/readiness-2026-05-08.md` — verdict NOT-READY, but every code-fixable BLOCKER + HIGH + MEDIUM is now closed in the live stack.
- Operator runbook: `docs/operator-runbook-2026-05-08.md` for the server-config items (CSP enforce, wp-cron, DMARC, LSCache, CDN, Uptime Kuma, vault key backup).
- Open architectural item: **`stash@{0}` — parallel FSE-rewrite WIP** (12 modified theme files + 17 untracked patterns/parts/fonts). Decision pending: ship/branch/discard. The deploy script `scp`s working files, so don't run it while the stash is unpopped without intent.
- Open large workstream: **ZATCA Phase-2 FATOORA integration**. Needs operator credentials from the ZATCA Sandbox portal before the clearance call can be wired.
