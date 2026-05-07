---
name: check-all
description: "Run the full pre-merge audit on the NovaKeys repo — WPCS, security, HPOS, performance, modern WP. Use before opening a PR or whenever the user types '/check-all' or asks 'audit the current file'. Dispatches the wp-woo-standards-auditor agent against the recent changes."
license: MIT
---

# Check-All — NovaKeys pre-merge audit

Run when the user asks to audit a file/branch, types `/check-all`, or finishes a chunk of PHP work.

## What this does

1. **Detects scope.** If an argument was passed (file path or branch ref), audit just that. Otherwise audit the recently-modified PHP/JS files (`git status --porcelain | grep -E '\.(php|js)$' | head -10`).
2. **Cutover guard.** If `.cutover-active` exists at repo root, refuse and explain. (NovaKeys is post-Phase-4; this guard should never trigger now, but keep it as a safety net for future migrations.)
3. **Dispatch the auditor.** Use the `Agent` tool with `subagent_type: "wp-woo-standards-auditor"`. Brief it with:
   - Target file(s) absolute paths
   - Standards source: `wordpress-engineer` (user-scope) + this project's `wordpress` skill
   - Pre-Flight Checklist requirement (every item gets `[x]` / `[!]` / `[n/a]`)
   - Severity tags (BLOCKER / HIGH / MEDIUM / LOW / INFO)
   - "Do not auto-apply fixes — present them for confirmation"
4. **Format the response.** Per-file sections; violations table; summary with counts by severity; recommended next action.

## Inputs

- **No arg** → audit recently-modified `.php` / `.js` files in working tree.
- **`<path>`** → audit just that file.
- **`<branch>`** → audit every file changed since that branch diverged from `main`.

## What this does NOT do

- **Does not auto-apply fixes.** Surface violations and propose patches; the user merges them.
- **Does not run in cutover.** If `.cutover-active` is present, the audit refuses. (The sentinel mechanism prevents agent memory poisoning during transient module-cutover states.)
- **Does not duplicate the readiness study.** That's `/study-site`; this is a per-PR check.

## Output expected

```
## Audit: <file>

### Pre-Flight Checklist
- [x] / [!] / [n/a] item ... (line ref for violations)

### Violations
1. [BLOCKER] <Category> — <file>:<line>
   Issue: ...
   Fix: ```php
   <code>
   ```

### Summary
0 BLOCKER, N HIGH, N MEDIUM, N LOW. Recommended: <next action>.
```

## Tools allowed

`Agent`, `Read`, `Bash`. The `Agent` dispatch is the heart of this skill.

## See also

- `~/.claude/skills/wordpress-engineer/` — master contract this audits against
- `~/.claude/skills/wordpress/` (this project) — NovaKeys-specific gotchas the auditor should respect
- `~/.claude/commands/check-all.md` — the slash-command wrapper for the same workflow
