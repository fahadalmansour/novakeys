# NovaKeys — Visual/UX Audit

Last run: **2026-05-08** · Run ID: **2026-05-08-1500**
Captured: `~/.claude/reports/novakeys/screenshots/2026-05-08/` (24 shots: home + shop + cart + account × 3 viewports × 2 locales; product timed out and isn't covered this run)
HEAD: `57372fb`
Notion: https://www.notion.so/e38bdfd54e3343109402b1def5e8c693

This file is the operator-readable punch list. Each finding also lives in the Notion **Site Audit Tracker** with Before screenshot path attached and lifecycle status. Mark items `[ack]` (acknowledged), `[wontfix]`, or `[fixed]` in this file — the next `/visual-audit novakeys` run preserves those markers.

> Constraint of this audit: targeted component-level fixes only. No theme rewrites. Findings that would require a rewrite live under **OUT-OF-SCOPE**.

**Tally:** 2 BLOCKER · 15 HIGH · 14 MEDIUM · 6 LOW · 1 OUT-OF-SCOPE = **38 findings**

---

## BLOCKER

### B1. Cart empty-state has no heading, no CTA, no illustration at 360 (en + ar)
- Viewport / page / locale: **360 / cart / en + ar**
- Source: `themes/novakeys/templates/cart.html:12` (also: `myaccount/cart-empty.php` registered as override at `plugins/novakeys-commerce/includes/theme/theme-bridge.php:2121` but **the override file does not exist on disk**)
- Screenshot: `~/.claude/reports/novakeys/screenshots/2026-05-08/cart-360-en.png` and `cart-360-ar.png`
- Fix: Create the missing `cart/cart-empty.php` override OR replace `wp:post-content` in cart.html with a hardcoded `wp:group` containing an icon + H2 ("Your cart is empty") + `wp:button` to `/shop/`. Wrap bilingual content in `dir="rtl"` and use `--wp--preset--font-family--arabic`.
- Evidence: At 360 the white card is nearly blank — no text, no CTA, no illustration. WC's default upsell template has no override on disk so it falls through with nothing render-worthy on the narrow viewport.

### B2. (companion of B1) Cart 768/1280 shows a product carousel inside the empty-state card
- Viewport / page / locale: **768 + 1280 / cart / en + ar**
- Source: same as B1 — `themes/novakeys/templates/cart.html:12` + missing override
- Screenshot: `cart-768-en.png`, `cart-1280-en.png`
- Fix: Same fix as B1. The product grid appears because WC's default `cart-empty.php` injects upsell products; with no override, that's what renders.
- Evidence: Four product placeholder cards with "Add to cart" CTAs render inside what should be the cart empty-state card. User mental model: they think they are in the shop, not the cart.

---

## HIGH

### H1. Brand-grid tiles render unstyled — `.ng-gc-*` classes have no CSS
- Viewport / page / locale: all / shop / en+ar
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:849`
- Screenshot: `shop-1280-en.png`
- Fix: Add CSS rules for `.ng-gc-brand-tile`, `.ng-gc-lane`, `.ng-gc-lane-strip`, `.ng-gc-lane-head`, `.ng-gc-brands-grid-wrap`, `.ng-gc-brands-head` in `plugins/novakeys-commerce/assets/chrome/neogen.css`.
- Evidence: PHP emits 9 `.ng-gc-*` classes; grep of neogen.css (1314 lines) returns zero matches.

### H2. Nav tool tap targets 40×40 px — fails iOS HIG 44 px / Material 48 dp
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:188-189`
- Screenshot: `home-360-en.png`
- Fix: `width: 40px → 44px`, `height: 40px → 44px`.

### H3. Nav category links wrap to 3 lines on 360 — header eats first fold
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:238`
- Screenshot: `home-360-en.png`
- Fix: At 720 px breakpoint, set `.ng-nav-cats { overflow-x: auto; white-space: nowrap; flex-wrap: nowrap }`.

### H4. Shop page AR shot looks identical to EN at 360 — RTL parity unverifiable
- Source: lang filter at `plugins/novakeys-commerce/includes/theme/theme-bridge.php:165` + `themes/novakeys/functions.php:192`
- Screenshot: `shop-360-ar.png`
- Fix: Verify `[dir=rtl]` cascades on `?lang=ar` shop routes; add an integration test asserting `<html dir="rtl">` on AR routes.

### H5. Rack tile (`.ng-rack-unit`) padding too tight on 360
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:1236-1238`
- Fix: Inside `@media (max-width: 600px)`, `min-height: 88px` + `padding: 1rem 0.85rem`.

### H6. Cart page renders a redundant NOVA KEYS logotype below the site header
- Viewport / page / locale: 768 / cart / en + ar
- Source: `themes/novakeys/templates/cart.html:6-8` (`wp:image` block)
- Screenshot: `cart-768-en.png`, `cart-768-ar.png`
- Fix: Delete the `wp:image` block at lines 6-8; the header already carries the brand mark.
- Evidence: Screenshot shows the wordmark twice — once in the sticky header, once inside the main content above the white card. Eats ~72 px at 768.

### H7. Cart at 768/1280 shows product grid (shop items) instead of focused empty-cart message
- (companion of B2 — listing as HIGH because the card layout itself is the visible problem; same fix)

### H8. Account login form has no visible field labels at any viewport
- Viewport / page / locale: 360 / account / en (also 768/1280 — same)
- Source: `themes/novakeys/templates/page-my-account.html:12-13` (override `myaccount/form-login.php` registered at `theme-bridge.php:2130` but **missing on disk**)
- Screenshot: `account-360-en.png`
- Fix: Create the `myaccount/form-login.php` override with explicit visible `<label>` elements above each input. Don't rely on placeholder-as-label (fails WCAG 1.3.1).

### H9. Account 768 — "Log in" button right-aligned, register form below the fold, asymmetric layout
- Source: `themes/novakeys/templates/page-my-account.html:11-13`
- Screenshot: `account-768-en.png`
- Fix: In the form-login.php override set submit to `width: 100%; min-height: 44px`; constrain login section to `max-width: 360px` centered on tablet.

### H10. Account "Lost your password?" link is invisible at 360 and 768
- Source: `themes/novakeys/templates/page-my-account.html:12-13`
- Fix: Render the lost-password link as a standalone anchor below the submit button with brand-link color and `font-size: 0.875rem`.

### H11. Account form on AR — inputs are LTR-oriented (text aligns left) instead of RTL
- Viewport / page / locale: 768 / account / ar
- Source: `themes/novakeys/templates/page-my-account.html:11-13`
- Screenshot: `account-768-ar.png`
- Fix: In form-login.php override add `dir="rtl"` on `<form>`; scope `input { text-align: right; direction: rtl }` under `[lang="ar"]`.

### H12. Account "Log in" submit button under 44 px tap target at 360
- Source: `themes/novakeys/templates/page-my-account.html:12-13`
- Fix: `.woocommerce-Button { min-height: 44px; width: 100% }`.

### H13. Account "Register" submit button — same under-sized tap target at 360
- Same fix as H12.

---

## MEDIUM

### M1. Sysbar wraps to 2 lines on 360 — doubles header height
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:66-78` (also `.hide-sm` at line 95)
- Fix: `white-space: nowrap` + extend `.hide-sm` to also hide the VAT item below 720 px.

### M2. Product-card "ADD TO CART" 12 px uppercase strains legibility
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:1152-1158`
- Fix: `font-size: 12px → 13px`, `font-weight: 500 → 600`.

### M3. Wrong `.ng-section-en` class wraps Arabic heading "اختر فئة"
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:535`
- Fix: Change `<h2 class="ng-section-en">` to `<h2 class="ng-section-ar">`.

### M4. Footer trust strip orphans 4th item at 768 — `auto-fit minmax(200px,1fr)` only fits 3
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:447`
- Fix: At 768, `repeat(2, 1fr)` or shrink minmax to 160 px.

### M5. Static RTL rack-arrow doesn't mirror; only hover state flips
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:1050-1051`
- Fix: Static `[dir="rtl"] .ng-rack-unit .ng-rack-link svg { transform: scaleX(-1) }`.

### M6. Pagination buttons 36×36 — below HIG 44
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:1211-1213`
- Fix: 44×44.

### M7. No skip-to-content link
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:2286` (`wp_body_open`)
- Fix: Inject a focusable skip-link as the first child before `.ng-sysbar`.

### M8. Result-count vs sort form misaligned by 0.5 rem
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:1168-1175`
- Fix: Both `margin-top: 1rem` for shared baseline.

### M9. Cart white card padding too tight at 360 — `var:preset|spacing|50` (~3.375 rem) compresses inner width
- Source: `themes/novakeys/templates/cart.html:10-14`
- Fix: Use `var:preset|spacing|30` (~1.5 rem) at 360 via mobile overrides block.

### M10. Account white card same padding issue at 360 — inputs render <240 px wide
- Source: `themes/novakeys/templates/page-my-account.html:10-14`
- Fix: Same as M9 — both templates share the card block markup, one override targets both.

### M11. Account "Remember me" checkbox not visible/discoverable at any viewport
- Source: `themes/novakeys/templates/page-my-account.html:12-13`
- Fix: In form-login.php override, render rememberme row with `display: flex; align-items: center; gap: 8px` and 24×24 px touch target.

### M12. Account 1280 — login + register stacked vertically with excessive gap, register below the fold
- Source: `themes/novakeys/templates/page-my-account.html:10-14`
- Fix: In form-login.php override, wrap both `.woocommerce-form` sections in a flex row at ≥768 px so login + register sit side-by-side above the fold.

### M13. Cart logo image hardcoded path `/wp-content/themes/novakeys/assets/novakeys-logo.svg` — breaks on non-root installs
- Source: `themes/novakeys/templates/cart.html:7`
- Fix: Remove the `wp:image` block (same fix as H6); if kept, use a dynamic block-pattern with `theme_url()`.

### M14. Account page-title renders H1 inside the white card — duplicates header H1
- Source: `themes/novakeys/templates/page-my-account.html:12`
- Fix: Change `wp:post-title` `level` from 1 to 2 (`"level":2`) or suppress entirely.

---

## LOW

### L1. Sysbar `aria-label` Arabic-only — fails EN locale a11y
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:2316`
- Fix: Wrap aria-label in a locale check (pattern exists at line 2326).

### L2. `.tool-label` span double-announces — inside parent anchor's aria-label
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:2347` + `assets/chrome/neogen.css:207-216`
- Fix: `aria-hidden="true"` on every `.tool-label` `<span>`.

### L3. Footer brand description `max-width: 32ch` not RTL-aware
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:590`
- Fix: `max-width: min(32ch, 280px)` or just `280px`.

### L4. Mobile disclosure bottom row centres legal text — convention is start-aligned
- Source: `plugins/novakeys-commerce/assets/chrome/neogen.css:719-720`
- Fix: `text-align: start` inside the mobile media query.

### L5. Cart 768 AR — trust-bar icons LTR (no `dir="rtl"` on container)
- Source: `plugins/novakeys-commerce/includes/theme/theme-bridge.php:662`
- Fix: Add `dir="rtl"` to the `.ng-gc-trust` div.

### L6. Account 360 AR — Latin "NOVA KEYS" wordmark on AR-locale page with no `lang="en"` scope
- Source: `themes/novakeys/templates/page-my-account.html:6-8`
- Fix: Remove the duplicate logo block (also resolves H6/M13). If kept, add `lang="en"` on the `<figure>` (WCAG 3.1.2).

---

## OUT-OF-SCOPE

### OOS1. No dedicated front-page hero — homepage IS the shop archive
- Source: `themes/novakeys/templates/index.html:1-33` falls through to `archive-product.html`
- Why out of scope: requires a new `front-page.html` FSE template — that's a new template, not a component fix.
- Evidence: `home-1280-en.png` opens directly on the "اختر فئة" rack with no hero, no value prop, no primary CTA above the fold.

---

## Summary

The single biggest pattern in this audit is **two missing WC template overrides** (`cart/cart-empty.php` and `myaccount/form-login.php`, both registered in `theme-bridge.php` at lines 2121 + 2130 but absent on disk). Creating those two files with proper bilingual + accessible markup resolves B1, B2, H8, H9, H10, H11, H12, H13, M11, M12 in one sweep — 10 findings collapse to one fix.

Second pattern: **two duplicate-logo `wp:image` blocks** in `cart.html:6-8` and `page-my-account.html:6-8`. Deleting both blocks resolves H6, M13, L6 — 3 findings to one fix.

Third pattern: **sub-44 px tap targets** (H2, H5, H12, H13, M6) — every interactive control on mobile that's currently 36-40 px needs to land at 44+ px.

Brand chrome is mature at 1280; failures concentrate at 360 and on AR locale.
