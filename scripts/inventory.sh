#!/usr/bin/env bash
# Repo inventory for NovaKeys.
# Usage: bash scripts/inventory.sh
#
# Outputs a structured manifest to stdout. Pipe to tee for a snapshot:
#   bash scripts/inventory.sh | tee .claude/inventory-$(date +%Y%m%d).txt

set -euo pipefail

cd "$(dirname "$0")/.."

echo "=== $(basename "$PWD") ==="
echo "branch: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '(not a git repo)')"
echo "head:   $(git log -1 --oneline 2>/dev/null || echo '(no commits)')"
echo

echo "--- top-level (first 40 lines) ---"
ls -la | head -40
echo

echo "--- detect type ---"
[ -f wp-config.php ]       && echo "TYPE: full WordPress install"
[ -f style.css ]           && grep -q "^Theme Name:" style.css 2>/dev/null && echo "TYPE: theme (parent or child)"
[ -d themes/novakeys ]     && [ -f themes/novakeys/theme.json ] && echo "TYPE: FSE block theme present at themes/novakeys/"
[ -d plugins/novakeys-commerce ] && echo "TYPE: companion plugin present at plugins/novakeys-commerce/"
[ -d mu-plugins ]          && echo "TYPE: mu-plugins/ directory present (WARN: should be deleted post-Phase-4)"
echo

echo "--- composer / package ---"
if [ -f composer.json ]; then
  if command -v jq >/dev/null 2>&1; then
    jq '{name, require, "require-dev": ."require-dev", scripts}' composer.json 2>/dev/null || cat composer.json
  else
    cat composer.json
  fi
fi
[ -f composer.lock ]   && echo "(composer.lock present)" || echo "(no composer.lock)"
[ -f package.json ]    && echo "(package.json present)"  || echo "(no package.json — expected for NovaKeys)"
echo

echo "--- AI tooling ---"
[ -d .claude ]    && ls .claude/ || echo "(no .claude/)"
[ -f CLAUDE.md ]  && echo "(CLAUDE.md exists — review before overwriting)"
echo

echo "--- WooCommerce signals ---"
echo "Files referencing FeaturesUtil / WC_Order / wc_get_order:"
grep -r --include="*.php" -l "WC_Order\|wc_get_order\|FeaturesUtil::declare_compatibility" . 2>/dev/null | head -10
echo

echo "--- modules under plugins/novakeys-commerce/includes/ ---"
[ -d plugins/novakeys-commerce/includes ] && ls plugins/novakeys-commerce/includes/ 2>/dev/null
echo

echo "--- tests ---"
ls tests/ 2>/dev/null
echo

echo "--- inventory complete ==="
