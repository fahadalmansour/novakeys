# Migration: NeoGen Store → NovaKeys (2026-05-07)

This directory holds the gift-card and software-keys content that was moved out of `~/sites/neogen-store` on 2026-05-07.

## What was moved

### Code (now under `~/sites/novakeys/`)

| Type | Location | Files |
|---|---|---|
| mu-plugins | `mu-plugins/` | `neogen-gift-cards.php`, `neogen-gift-cards-bootstrap.php`, `neogen-gift-card-keys.php` |
| Snippets | `snippets/` | `gift-cards-header.php` |
| Scripts (one-time WP-CLI) | `scripts/` | `neogen-gift-cards-bulk.php`, `neogen-reprice-gift-cards.php`, `neogen-gift-cards-brand-cats.php`, `neogen-amazon-sa-reprice-gc.php`, `neogen-delete-netflix.php` |
| Tests | `tests/` | `test-gift-card-matcher.php` |
| Image assets | `assets/gift-cards/`, `assets/cat/`, `assets/hero/` | 12 brand .webp files (adobe, amazon, apple, google-play, kaspersky, office, playstation, steam, windows, xbox, youtube) + category/hero SVGs |

### Data (in this directory)

- `gift-cards-software-keys-master.csv` — 83 SKU rows from the NeoGen master catalog (pre-deletion snapshot, all 44 columns intact). These are the products that were in NeoGen's `Gift Cards & Software Keys` category before the 2026-05-07 prune.
- `removed-skus-audit-log.json` — full audit log of what was removed from NeoGen (122 SKUs total — 83 gift-cards + 39 gaming; only the 83 gift-cards are intended to land here).

## What was NOT moved

- The 39 SKUs in NeoGen's `Gaming` category — those were deleted, not relocated, per user instruction on 2026-05-07.
- Inline references inside other NeoGen files (`neogen-theme.php`, `neogen-redesign.php`, `neogen-deploy-tools.php`, `class-module-redesign.php`) — those were commented out in-place rather than removed, so the source remains for rollback. NovaKeys does not need those files.

## Rollback

If anything in NeoGen broke from the prune, restore via:

```bash
cd ~/sites/neogen-store
# 1. Restore master catalog
cp data/catalogs/master/Neogen_Master_Catalog_Blueprint.csv.backup-20260507-065041 \
   data/catalogs/master/Neogen_Master_Catalog_Blueprint.csv
cp data/catalogs/master/Neogen_Master_Catalog_Blueprint.xlsx.backup-20260507-065041 \
   data/catalogs/master/Neogen_Master_Catalog_Blueprint.xlsx

# 2. Move code files back from NovaKeys
# (see "Code" table above for source/dest mapping)

# 3. Re-run downstream regen
npm run woo:generate
npm run sourcing:generate
npm run price:guard
```

## Next steps in NovaKeys

The relocated code references NeoGen-specific functions (`ng_*`, `_ng_gift_card_*` postmeta, NeoGen brand tokens, etc.) and is **not yet adapted for NovaKeys**. Before deploying any of these into a live NovaKeys WordPress install:

1. Rename function/constant prefixes from `ng_` / `NG_` to a NovaKeys equivalent, OR keep the prefixes if NovaKeys is happy to inherit them.
2. Verify image URL bases (`NG_THEME_ASSET_URL`) resolve in the NovaKeys context.
3. Re-import the 83 SKUs from `gift-cards-software-keys-master.csv` via the NovaKeys WooCommerce import flow.
4. Re-test the gift-card admin UIs (order metabox, coverage report) once they auto-load via NovaKeys' own loader.
