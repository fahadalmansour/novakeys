# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

NovaKeys is a Saudi gift-card / software-key WooCommerce store. The repo holds **only the custom code that drops into a WordPress install** — mu-plugins, a near-empty stub theme, one-off WP-CLI scripts, a smoke test, and a migration audit folder. It is not a self-contained WordPress install: there is no `wp-config.php`, no core, no `wp-content/` tree.

The codebase was split out of the NeoGen Store project on 2026-05-07 (see `data/migrated-from-neogen-20260507-072457/MIGRATION-NOTES.md`). Many files still use the legacy `ng_*` / `neogen-*` prefix — keep that prefix when extending those modules; use `nk_*` / `novakeys-*` only where it already exists.

## Layout

- `mu-plugins/` — the real application. Drop-in to `wp-content/mu-plugins/`.
- `themes/novakeys/` — a near-empty stub (`header.php` only). Visual/legal "theme" logic lives in `mu-plugins/novakeys-theme.php`, not here.
- `scripts/` — one-off WP-CLI utilities (run via `wp eval-file`, not web-accessible).
- `snippets/gift-cards-header.php` — gift-card picker + region selector. Not auto-loaded; intended to be wired into a future snippet loader or activated manually.
- `tests/test-gift-card-matcher.php` — single plain-PHP smoke test (no PHPUnit).
- `data/migrated-from-neogen-*/` — migration audit log + CSVs. Documentation only; no code reads from it.
- `assets/` — brand SVGs/webps consumed by the asset mapper.
- `.github/workflows/php.yml` — expects a `composer.json` that does not exist yet (workflow currently fails). Don't add Composer just to satisfy CI — confirm the user actually wants Composer first.

## Mu-plugin architecture

Three layers, with the rest as independent feature plugins:

1. **Core matcher** — `mu-plugins/neogen-gift-cards.php`
   Defines `ng_gift_card_asset_map()` (brand → webp/svg paths for ~18 brands incl. Apple, PlayStation, Steam, STC, Mobily, Etisalat) and the matching helpers (`ng_gift_card_asset_for_product`, `ng_gift_card_image_url`, `ng_gift_card_clean_product_name`, `ng_gift_card_normalize_match_text`). Constants: `NG_THEME_ASSET_DIR`, `NG_THEME_ASSET_URL`.
2. **Product seeder** — `mu-plugins/neogen-gift-cards-bootstrap.php`
   Admin page at **Tools → NeoGen Gift Cards · Bootstrap**. Idempotent: scans the asset map, creates one **draft** WC product per brand slot keyed by SKU `gc-<slot>`, sets postmeta `_ng_gift_card_brand`. Does NOT set prices or publish — operator does that manually.
3. **Order fulfillment / key vault** — `mu-plugins/neogen-gift-card-keys.php`
   Per-order-item gift-card code storage. AES-256-CTR at-rest encryption keyed off `wp_salt('logged_in')` via `ng_gck_encrypt()` / `ng_gck_decrypt()`. Item meta: `_ng_gift_card_code`, `_ng_gift_card_status` (pending/active/consumed), `_ng_gift_card_expires_at`, `_ng_gift_card_brand`, `_ng_gift_card_region`. Adds a metabox on the WC order edit screen.

Independent feature plugins (no cross-deps):

- `novakeys-theme.php` — sitewide visual/legal skin: footer, header, `/legal` route (MOC identity, CR 7053130576), Schema.org Store JSON-LD, bilingual `"English | Arabic"` labels via `ng_ar_label()`, custom taxonomy ordering, WC template overrides under `mu-plugins/neogen-theme-assets/templates/woocommerce/`.
- `novakeys-recommendations.php` — recently-viewed cookie (`ng_recent`, 30d, HttpOnly, SameSite=Lax) + rule-based recs. Shortcode `[neogen_recommendations]`. Auto-injects on `woocommerce_after_single_product`. Admin debug: `?ng_simulate_recent=12,15,22`.
- `novakeys-seo.php` — security headers (CSP report-only, HSTS, X-Frame-Options), `/ads.txt`, `/llms.txt`, robots.txt AI-crawler blocking.
- `novakeys-site-custom.php` — global tweaks. **Locks timezone to Asia/Riyadh** (`pre_option_timezone_string`/`pre_option_gmt_offset` filters). WC compat check (`NG_TESTED_WC`). Opens WC REST API for unauthenticated reads via `woocommerce_rest_check_permissions`.
- `novakeys-vouchers.php` — `[nk_vouchers]` shortcode rendering a filterable voucher gallery (gaming / shopping / entertainment / apps / telecom / productivity). Inlines its own CSS + vanilla JS, no external assets.

**Shared `_ng_*` postmeta namespace** ties the gift-card plugins together — e.g. bootstrap writes `_ng_gift_card_brand`, the matcher and the keys plugin both read it. Keep that prefix for new gift-card meta.

## Common operations

- **Run the matcher smoke test:** `php tests/test-gift-card-matcher.php`. It uses inline WP/WC stubs; no PHPUnit, no fixtures dir.
- **Run a one-off script:** `wp eval-file scripts/<name>.php --user=1` from the WP install root. They are not safe to load via web — never `require` them from a plugin.
  - `neogen-gift-cards-bulk.php` — bulk-creates ~70 SKUs across ~14 brands. Idempotent by SKU. FX: USD→SAR at 3.75 + 7% markup.
  - `neogen-reprice-gift-cards.php` — repricing pass to a 20% gross-margin floor; backs up original to `_ng_pre_reprice_regular_price`.
  - `neogen-amazon-sa-reprice-gc.php` — KSA-specific Amazon repricing.
  - `neogen-delete-netflix.php` — one-time Netflix product purge.
  - `neogen-gift-cards-brand-cats.php` — generates brand sub-terms under the gift-cards `product_cat`.
- **Bootstrap products from assets:** WP Admin → Tools → NeoGen Gift Cards · Bootstrap.
- **Build / lint:** none. No Composer, no npm, no Makefile.

## Project-specific gotchas

- Some functions use the `nk_*` prefix (`nk_cr`, `[nk_vouchers]`) and others use `ng_*` (`ng_gift_card_*`, `ng_gck_*`, `ng_ar_label`, `ng_recent`, `_ng_*` meta). Match the existing prefix in the file you're editing rather than renaming — the postmeta namespace especially must stay `_ng_*` for cross-plugin compatibility.
- The bootstrap plugin **never publishes products and never sets prices**. Don't "fix" that — it's intentional so the operator reviews each SKU.
- Order-item gift-card codes are encrypted at rest. Don't log raw codes; always go through `ng_get_gift_card_keys()` / `ng_gck_decrypt()`.
- Timezone is force-locked to Asia/Riyadh by `novakeys-site-custom.php`. The WP admin Settings → General timezone field is effectively read-only.
- The CI workflow at `.github/workflows/php.yml` runs `composer validate --strict` against a `composer.json` that does not exist. It will fail on every push until either the file is added or the workflow is updated.
- `themes/novakeys/` is a stub — adding theme features there is almost always wrong; extend `mu-plugins/novakeys-theme.php` instead.
