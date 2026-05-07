=== NovaKeys Commerce ===
Contributors: fahadalmansour
Tags: woocommerce, gift-cards, loyalty
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.0
License: Proprietary

Companion plugin for the NovaKeys Store. Owns gift-card commerce, NK Points loyalty, vouchers, recommendations, SEO/security headers, icons, and legal-route rewrites.

== Description ==

This plugin pairs with the `novakeys` FSE block theme. The plugin owns commerce + chrome logic; the theme owns the visual layer (templates, parts, patterns, theme.json tokens).

This is the phase-1 skeleton. Modules are migrated from `mu-plugins/` to `plugins/novakeys-commerce/includes/<module>/` incrementally — each phase-2 commit moves one module and renames the corresponding mu-plugin to `.php.disabled` so we never double-register hooks.

== Changelog ==

= 0.1.0 =
* Initial scaffold: bootstrap, plugin singleton, option migrator, compat shims placeholder.
* No behaviour change — modules ship inert.
