---
name: inventory
description: "Quick repo inventory — top-level layout, stack detection, composer/package state, AI tooling presence, WooCommerce signals. Use to bootstrap context when arriving in this repo cold, or to refresh the picture after a major refactor."
license: MIT
---

# NovaKeys repo inventory

A one-shot inventory that produces a structured manifest of the repo. Useful for: cold start in a new session, post-refactor sanity check, or when the user asks "what's the state of the repo right now?".

## How to run

```bash
bash scripts/inventory.sh
```

The script lives in `scripts/inventory.sh` (committed to the repo). It writes to stdout — pipe to `tee` if you want a snapshot:

```bash
bash scripts/inventory.sh | tee .claude/inventory-$(date +%Y%m%d).txt
```

## What it reports

- **Top-level layout** (first 40 lines of `ls -la`)
- **Stack detection**: WP install? Theme parent/child? mu-plugin? Plugin?
- **composer.json + package.json** content (stack/script summary)
- **AI tooling presence**: does `.claude/` exist? Is there a CLAUDE.md?
- **WooCommerce signals**: which files reference WC_Order, FeaturesUtil, etc.

## When to use this skill vs. just running `find`

- **Use the skill** when you need a structured snapshot to paste into a study, hand to another agent, or compare against a previous run.
- **Just run `find` / `grep`** for one-off lookups.

## Cross-references

- `~/.claude/skills/study-and-document/SKILL.md` — the longer-form structured study workflow that this skill feeds into.
