# WordPress & WooCommerce Engineering Standards

The contract every PHP commit in this repo (themes, plugins, mu-plugins, snippets, scripts) is graded against. Trigger a self-audit any time with: *"Audit the current file using the standards in .claude/skills/wordpress.md and fix any violations."*

## 1. Coding standards & syntax

- **PHP standards:** WordPress PHP Coding Standards (WPCS).
- **Naming:** `snake_case` for functions, variables, hook names. `PascalCase` for classes.
- **Yoda conditions:** `if ( true === $variable )` — constant on the left.
- **PHPDoc:** every function gets a `/** ... */` block with `@param`, `@return`, `@since`.

## 2. Security (non-negotiable)

- **Sanitise on input:** `sanitize_text_field()`, `absint()`, `sanitize_email()`, `sanitize_key()`, `wp_unslash()`-then-sanitize on `$_POST`/`$_GET`.
- **Late-escape on output:** `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`. Escape at the point of rendering, not earlier.
- **Nonces:** every form, AJAX, and REST mutation includes `wp_create_nonce()` and verifies via `check_admin_referer()` / `check_ajax_referer()` (or REST `permission_callback` that checks the nonce).
- **Capabilities:** `current_user_can()` gate before any sensitive action.

## 3. WooCommerce integration

- **CRUD over postmeta:** use `$product->get_price()`, `$order->get_meta()`, `$item->get_meta()`, `$order->save()` — do NOT reach into `get_post_meta()` / `update_post_meta()` for product or order data.
- **Template overrides:** never edit WooCommerce core. Use `add_action`/`add_filter`, or override templates in the child theme's `woocommerce/` folder.
- **HPOS compatibility:** all order code must work with High-Performance Order Storage. No queries against `wp_postmeta` for order data.

## 4. Performance & database

- **Transients:** `set_transient()` / `get_transient()` for expensive API calls or heavy queries. Use `MINUTE_IN_SECONDS`, `HOUR_IN_SECONDS`, `DAY_IN_SECONDS` constants for TTLs.
- **Direct SQL:** avoid `$wpdb` unless `WP_Query` cannot do the job. If forced, always `$wpdb->prepare()` with placeholders.
- **Scripts/styles:** always `wp_enqueue_script()` / `wp_enqueue_style()` via `wp_enqueue_scripts` (frontend) or `admin_enqueue_scripts` (admin). Never hardcode `<script>` / `<link>` in `header.php` or templates.

## 5. Modern WordPress features

- **Block editor first:** prefer block-based solutions over shortcodes for new UI.
- **theme.json:** for any styling change, check `theme.json` for global tokens (color, typography, spacing) before writing custom CSS.
- **Localization:** wrap user-facing strings in `__( 'String', 'text-domain' )` / `_e()` / `esc_html__()`. Text domains: `novakeys` (theme), `novakeys-commerce` (plugin).

## Pre-flight checklist (the "Check All" protocol)

Before producing or applying any code, run this mental check:

- [ ] Compatible with PHP 8.0+.
- [ ] Every input sanitised and every output escaped.
- [ ] Hook-first — does it expose `do_action()` / `apply_filters()` so other devs can extend?
- [ ] Follows the project's directory structure (see CLAUDE.md).
- [ ] (If WooCommerce) uses CRUD methods, not raw `*_post_meta()` on orders/products.

## How to invoke

- **Force an audit on the open file:** *"Audit the current file using the standards in .claude/skills/wordpress.md and fix any violations."*
- **Build a new feature:** *"Build a custom shipping calculator for WooCommerce following our WordPress skill file."*
