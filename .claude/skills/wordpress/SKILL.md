---
name: wordpress
description: "NovaKeys-specific WordPress / WooCommerce engineering rules. Defers to the canonical user-scope wordpress-engineer skill for the WPCS contract; this file holds the per-repo gotchas (symbol prefixes, postmeta lock, FSE migration state, text domains, deploy quirks)."
license: MIT
---

# NovaKeys WordPress engineering

The canonical contract lives at `~/.claude/skills/wordpress-engineer/SKILL.md`. Read that first. This file holds NovaKeys-specific overrides and gotchas only.

## Trigger phrase

*"Audit the current file using the standards in `wordpress-engineer` and fix any violations."*

The legacy phrase *"…in `.claude/skills/wordpress.md`…"* still works (back-compat) — both routes load `wordpress-engineer` plus this project skill plus `woocommerce-specialist` (when the file touches Woo).

## Project shape (post-Phase-4, as of 2026-05-07)

- `mu-plugins/` — **gone** (deleted in `9409445`). Don't recreate it.
- `plugins/novakeys-commerce/` — companion plugin. All commerce + chrome modules live here under `includes/<module>/`.
- `themes/novakeys/` — **FSE block theme** (shipped in `2d351fc`). `theme.json` carries every brand token; templates under `templates/*.html`; parts under `parts/`; patterns under `patterns/`.
- `scripts/` — WP-CLI utilities + the `deploy-phase-4-to-live.sh` runbook. Web-inaccessible.
- `tests/` — `test-gift-card-matcher.php` (smoke). Cipher round-trip test still TODO.

## Symbol prefix discipline (do not unify)

| Prefix | Used for | Locked? |
|---|---|---|
| `ng_*` | Public function names (legacy from NeoGen split). ~50 functions. | Match existing prefix in any file you edit. Don't rename. |
| `_ng_*` | Postmeta keys (`_ng_gift_card_*`, `_ng_ar_title`, `_ng_gc_regions`). | **LOCKED.** Live order data depends on these keys; renaming orphans every issued gift-card code. |
| `nk_*` | Newer feature funcs (NK Points, referral, voucher gallery, REST namespace `nk/v1/`). | Use for genuinely new features. |
| `NK_*` | PHP constants (`NK_COMMERCE_VERSION`, `NK_POINTS_PER_SAR=10`, `NK_TESTED_WC`, `NK_CSP_ENFORCE`). | Use for constants. |
| `NG_*` | Legacy constants (mostly removed). | Don't reintroduce. |

## Text domains

- `novakeys` — theme strings. Domain Path: theme `/languages`.
- `novakeys-commerce` — companion plugin strings. Domain Path: plugin `/languages`.
- `neogen` — **legacy** (one stray reference at `theme-bridge.php:1598`). Don't propagate; fix when touched.

## What goes in the FSE theme vs. the companion plugin

- **Theme** (`themes/novakeys/`) — visual layer only: `theme.json`, templates, parts, patterns, brand assets. The slim `functions.php` does theme-support flags + `wp_enqueue_*` only. **No business logic.**
- **Plugin** (`plugins/novakeys-commerce/`) — every business module: gift-cards (matcher / vault / store / refund-revoker / customer-endpoint / admin / bootstrap-tool), loyalty (coupon-rest / referral / gift-mailer / points), recommendations, vouchers, icons, product-meta, seo (headers / routes / Rank Math bridge), site (timezone, WC compat, public REST read), security (mcp-meta-guard), theme (theme-bridge — the one place plugin reads back into theme).

## Locked behaviors (don't "fix" without operator approval)

- **Timezone**: Asia/Riyadh, force-locked via `pre_option_timezone_string` filter in `class-customizations.php`. WP Admin → Settings → General timezone field is effectively read-only.
- **`_ng_*` postmeta keys**: see above — locked, live-data dependent.
- **REST namespace `nk/v1/`**: stays as-is.
- **Cookie names**: `ng_recent` (recently-viewed, 30d), `nk_ref` (referral, 7d). Renaming breaks live attribution chains.
- **`enc:v1:` ciphertext envelope**: legacy AES-256-CTR data exists; keep the v1 decrypt path forever.

## Deploy

- Manual via `bash scripts/deploy-phase-4-to-live.sh`. SCP to `fsalmansour@162.254.39.146:21098`. Atomic `mv` rename for the swap. Rollback documented in the script (lines 147-152).
- **No CI/CD deploy.** If your local Mac is unavailable, no deploy path. Plan accordingly for incidents.
- Pre-deploy: `composer validate --strict` is the only CI gate.

## Tests

- `php tests/test-gift-card-matcher.php` — 14/14 passing as of `9409445`.
- `tests/test-cipher-roundtrip.php` — **NOT YET CREATED**. Should cover v1 decrypt, v2 round-trip, GCM tamper-detect, IV uniqueness. See readiness report `~/.claude/reports/novakeys/readiness-2026-05-07.md` H12.

## Project-specific BLOCKERs from latest readiness audit

(`~/.claude/reports/novakeys/readiness-2026-05-07.md`)

- **B5** logic-ordering bug in `class-referral.php:81` — `sanitize_key()` runs before `valid_code()`, mis-attributing referrals.
- **B6** `xmlrpc.php` reachable; needs `add_filter('xmlrpc_enabled', '__return_false')`.
- **B7** `wp-cron.php` publicly triggerable; needs `define('DISABLE_WP_CRON', true)` + system cron.
- **B8** `.gitignore` missing entirely — add before next commit.
- **H4** `/wp-json/wp/v2/users` exposes admin username.

When auditing, prefer fixing these BLOCKERs over surface-level WPCS sweeps.

## See also

- `~/.claude/skills/wordpress-engineer/` — the master WPCS contract
- `~/.claude/skills/woocommerce-specialist/` — Woo CRUD / HPOS / Action Scheduler patterns
- `~/.claude/plans/gentle-kindling-biscuit.md` — the migration plan that produced this state
- `~/.claude/reports/novakeys/readiness-2026-05-07.md` — full backlog
