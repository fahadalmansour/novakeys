<?php
/**
 * Backwards-compatibility shims: `ng_*` aliases for `nk_*` symbols.
 *
 * Phase 2 ports each mu-plugin to the new namespace. Until phase 4
 * cleanup, snippets, scripts, and any third-party hooks may still call
 * `ng_*` names. Each shim is `function_exists`-guarded so the file can
 * be safely required even when the underlying `nk_*` function hasn't
 * been registered yet (the call falls through to the original
 * mu-plugin's `ng_*` definition).
 *
 * Add a `ng_*` shim here every time a function moves to `nk_*`.
 *
 * @package NovaKeys\Commerce
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

// Shims are added per-module in phase 2. Phase 1 ships the file empty
// so subsequent commits can extend without touching the loader.
