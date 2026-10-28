# Elgg Developer Guide: Performance

Delta distillation of the elgg-benchmark skill (upstream store
`tmp/upstream/elgg-llm-skills`, distilled 2026-10-28). Records only the
Elgg-specific facts that counter stale training-data memory; general
benchmark methodology (deterministic metrics over wall-clock, clean
throwaway containers) is assumed. Update in place when the upstream
skill changes — not from manual diffs (there is no `performance.rst`).

## Measuring Elgg query performance

- The deterministic verdict for a database change is the MySQL/MariaDB
  `Handler_read_*` counter delta (`Handler_read_next` = rows the engine
  walked) plus the `EXPLAIN` plan change; wall-clock time is
  cache-dependent and only corroborates. Measure with `FLUSH STATUS` /
  `SHOW STATUS` around the query.
- Seed realistic data with native Elgg seeding:
  `elgg-cli database:seed --limit=<n>`. Native seeding runs every row
  through Elgg's real save lifecycle, producing production-shaped data
  (~10 metadata rows per entity on a measured reference site);
  formula-generated bulk rows are fine for single-table SQL
  micro-benchmarks but not for API-layer measurements.

## The metadata composite index

- Metadata fetches by `(entity_guid, name)` dominate query cost on
  large sites: ~45% of mined core+plugin query shapes hit metadata by
  that pair. Elgg 7.x core ships the `metadata (entity_guid, name)`
  composite index; on sites upgraded from older versions, verify it
  exists — a worked before/after proof measured `Handler_read_next`
  dropping 130,003 → 0 for 10,000 `getIDsByName` lookups, and −83/−84%
  SQL time on the metadata-fetch shapes.

## Query-cost gate for plugin entities

- For a plugin that owns entities, gate query cost in CI: measure
  `Handler_read_next` per entity shape (delta method — no elevated DB
  privilege) against a committed `.perf-baseline.json` and fail on
  >25% drift; the first run records the baseline and skips.
