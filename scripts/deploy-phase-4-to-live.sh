#!/usr/bin/env bash
# Live deploy script for the phase-2/3/4 refactor.
#
# Lifts plugins/novakeys-commerce/ and themes/novakeys/ to the live
# Namecheap cPanel host, activates the plugin (option migrator runs),
# verifies endpoints, then removes the legacy mu-plugin tree.
#
# IDEMPOTENT-ish: re-running is safe up to the legacy-tree removal step.
# Each section gates on a prompt — no surprise destructive actions.
#
# Run from the repo root:
#   ./scripts/deploy-phase-4-to-live.sh
#
# Requires:
#   - SSH access to fsalmansour@162.254.39.146:21098 (key already trusted).
#   - WP-CLI on live (`wp` in $PATH at /home/fsalmansour/novakeys.store).

set -Eeuo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SSH_HOST="fsalmansour@162.254.39.146"
SSH_PORT="21098"
LIVE_ROOT="/home/fsalmansour/novakeys.store"
LIVE_PLUGINS="$LIVE_ROOT/wp-content/plugins"
LIVE_THEMES="$LIVE_ROOT/wp-content/themes"
LEGACY_MU="$LIVE_ROOT/wp-content/mu-plugins/novakeys-custom"
LEGACY_LOADER="$LIVE_ROOT/wp-content/mu-plugins/novakeys-custom-loader.php"
TS=$(date +%Y%m%d-%H%M%S)
BACKUP_TAR="$LIVE_ROOT/wp-content/mu-plugins/novakeys-custom.backup-${TS}.tar.gz"

ssh_run() { ssh -p "$SSH_PORT" "$SSH_HOST" "$@"; }
scp_to()  { scp -P "$SSH_PORT" -q -r "$@"; }

green() { printf '\033[32m%s\033[0m\n' "$1"; }
yellow(){ printf '\033[33m%s\033[0m\n' "$1"; }
red()   { printf '\033[31m%s\033[0m\n' "$1"; }

prompt() {
	local msg="$1"
	yellow ""
	yellow "→ $msg"
	read -r -p "Continue? [y/N] " ans
	[[ "$ans" =~ ^[yY]$ ]] || { red "Aborted."; exit 1; }
}

# ----------------------------------------------------------------
# 0. Sanity
# ----------------------------------------------------------------
green "==> Phase-4 live deploy"
green "    repo root: $REPO_ROOT"
green "    live host: $SSH_HOST:$SSH_PORT  ($LIVE_ROOT)"
green "    timestamp: $TS"

# Ensure our local files exist before we promise anything to live.
[[ -d "$REPO_ROOT/plugins/novakeys-commerce" ]] \
	|| { red "plugins/novakeys-commerce/ missing locally"; exit 1; }
[[ -d "$REPO_ROOT/themes/novakeys" ]] \
	|| { red "themes/novakeys/ missing locally"; exit 1; }

# Smoke test must pass before we touch live.
green "==> Local smoke test"
php "$REPO_ROOT/tests/test-gift-card-matcher.php" >/dev/null \
	|| { red "Smoke test failed locally; aborting."; exit 1; }
green "    OK"

# ----------------------------------------------------------------
# 1. Snapshot the legacy mu-plugin tree
# ----------------------------------------------------------------
prompt "Step 1 — back up the legacy novakeys-custom mu-plugin tree to $BACKUP_TAR"
ssh_run "tar -czf '$BACKUP_TAR' -C '$LIVE_ROOT/wp-content/mu-plugins' novakeys-custom novakeys-custom-loader.php 2>/dev/null && ls -la '$BACKUP_TAR'"
green "    backup created"

# ----------------------------------------------------------------
# 2. Upload the companion plugin
# ----------------------------------------------------------------
prompt "Step 2 — upload plugins/novakeys-commerce/ to $LIVE_PLUGINS"
ssh_run "mkdir -p '$LIVE_PLUGINS'"
# rsync would be ideal, but cPanel hosts often lack it. Fall back to scp.
ssh_run "rm -rf '$LIVE_PLUGINS/novakeys-commerce.upload-tmp'"
scp_to "$REPO_ROOT/plugins/novakeys-commerce" "$SSH_HOST:$LIVE_PLUGINS/novakeys-commerce.upload-tmp"
ssh_run "rm -rf '$LIVE_PLUGINS/novakeys-commerce.previous' && \
	[ -d '$LIVE_PLUGINS/novakeys-commerce' ] && mv '$LIVE_PLUGINS/novakeys-commerce' '$LIVE_PLUGINS/novakeys-commerce.previous' || true && \
	mv '$LIVE_PLUGINS/novakeys-commerce.upload-tmp' '$LIVE_PLUGINS/novakeys-commerce'"
ssh_run "find '$LIVE_PLUGINS/novakeys-commerce' -name '*.php' -print0 | xargs -0 -I {} php -l '{}' | grep -v 'No syntax errors' || echo 'all clean'"
green "    plugin uploaded"

# ----------------------------------------------------------------
# 3. Upload the FSE theme
# ----------------------------------------------------------------
prompt "Step 3 — upload themes/novakeys/ to $LIVE_THEMES (existing files backed up to .backup-$TS)"
ssh_run "[ -d '$LIVE_THEMES/novakeys' ] && tar -czf '$LIVE_THEMES/novakeys.backup-${TS}.tar.gz' -C '$LIVE_THEMES' novakeys || true"
ssh_run "rm -rf '$LIVE_THEMES/novakeys.upload-tmp'"
scp_to "$REPO_ROOT/themes/novakeys" "$SSH_HOST:$LIVE_THEMES/novakeys.upload-tmp"
# Preserve existing theme-side assets (app.js, etc.) that aren't in repo.
ssh_run "if [ -d '$LIVE_THEMES/novakeys/assets' ]; then mv '$LIVE_THEMES/novakeys/assets' '$LIVE_THEMES/novakeys.upload-tmp/assets'; fi"
ssh_run "rm -rf '$LIVE_THEMES/novakeys.previous' && \
	[ -d '$LIVE_THEMES/novakeys' ] && mv '$LIVE_THEMES/novakeys' '$LIVE_THEMES/novakeys.previous' || true && \
	mv '$LIVE_THEMES/novakeys.upload-tmp' '$LIVE_THEMES/novakeys'"
ssh_run "find '$LIVE_THEMES/novakeys' -name '*.php' -print0 | xargs -0 -I {} php -l '{}' | grep -v 'No syntax errors' || echo 'all clean'"
green "    theme uploaded"

# ----------------------------------------------------------------
# 4. Activate the plugin (runs the option migrator)
# ----------------------------------------------------------------
prompt "Step 4 — activate novakeys-commerce (runs the ng_* → nk_* option migrator)"
ssh_run "cd '$LIVE_ROOT' && wp plugin activate novakeys-commerce 2>&1 | grep -v -i warning"
ssh_run "cd '$LIVE_ROOT' && wp plugin list --format=csv | grep novakeys-commerce"
green "    plugin active"

# ----------------------------------------------------------------
# 5. Smoke check the live endpoints
# ----------------------------------------------------------------
prompt "Step 5 — smoke check live endpoints"
for p in "" cart checkout my-account terms-and-conditions terms legal returns privacy warranty usage shop my-account/gift-card-keys; do
	code=$(curl -sS -o /dev/null -w "%{http_code}" -L "https://www.novakeys.store/$p/?cb=$RANDOM")
	printf "    /%-32s → %s\n" "$p/" "$code"
done

# ----------------------------------------------------------------
# 6. Remove the legacy mu-plugin tree
# ----------------------------------------------------------------
red ""
red "==> WARNING: step 6 deletes the legacy novakeys-custom tree from live."
red "    Confirm step 5's endpoint output looks right before continuing."
prompt "Step 6 — delete legacy $LEGACY_MU + $LEGACY_LOADER"
ssh_run "rm -rf '$LEGACY_MU' '$LEGACY_LOADER'"
green "    legacy mu-plugin tree removed"

# ----------------------------------------------------------------
# 7. Flush rewrite rules + final smoke check
# ----------------------------------------------------------------
prompt "Step 7 — flush rewrite rules + final smoke check"
ssh_run "cd '$LIVE_ROOT' && wp rewrite flush --hard 2>&1 | grep -i -E 'success|error' || true"
for p in cart my-account terms legal returns privacy; do
	code=$(curl -sS -o /dev/null -w "%{http_code}" -L "https://www.novakeys.store/$p/?cb=$RANDOM")
	printf "    /%-32s → %s\n" "$p/" "$code"
done

green ""
green "==> Phase-4 deploy done."
green "    Backups left at:"
green "      $BACKUP_TAR"
green "      $LIVE_THEMES/novakeys.backup-${TS}.tar.gz (only if a previous theme existed)"
green "      $LIVE_PLUGINS/novakeys-commerce.previous (if a previous plugin existed)"
green "      $LIVE_THEMES/novakeys.previous (if a previous theme existed)"
green ""
green "    Rollback steps if needed:"
green "      1. wp plugin deactivate novakeys-commerce"
green "      2. mv $LIVE_PLUGINS/novakeys-commerce $LIVE_PLUGINS/novakeys-commerce.failed"
green "      3. mv $LIVE_PLUGINS/novakeys-commerce.previous $LIVE_PLUGINS/novakeys-commerce"
green "      4. tar -xzf $BACKUP_TAR -C $LIVE_ROOT/wp-content/mu-plugins/"
green "      5. wp rewrite flush --hard"
