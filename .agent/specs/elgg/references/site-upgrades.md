# Site Upgrades (Elgg)

Narrative reference for upgrading an entire Elgg installation — core plus
every plugin — between major versions. Covers the iron laws, the
verification gates, the anonymized-seed footguns, and the production
cutover. Load via the elgg pack's `SKILL.md` references index whenever a
task crosses a site-level version boundary; per-API change lists live in
`upgrade-notes/`, not here.

## Iron Laws

Five rules make a site upgrade recoverable. Violate any one and a bad
upgrade stops being a rollback and becomes a restoration.

1. **One major version at a time.** 2.3→3.3→4.3→5.1→6.1→7.0, then
   minor-update to the latest stable 7.x per the pack freshness check.
   Skipping versions skips the upgrade scripts that migrate schema and
   data; the site appears to work and then fails weeks later when
   something touches an un-migrated table.
2. **Latest minor before jumping major.** From 2.3.x you may start on
   3.x; from 2.0.x you may not. Minor releases carry the compatibility
   shims the next major assumes have already run.
3. **The data upgrades must actually run.** `elgg-cli upgrade` executes
   only the system upgrades and silently skips every asynchronous
   `Elgg\Upgrade\Batch`. Use `elgg-cli upgrade all -n -f` at every tier
   and assert zero pending `elgg_upgrade` entities before moving on.
   Skip this and the upgrades pile up until a later release deletes
   their class — at which point the work they carried is unreachable
   forever.
4. **Prepare completely before executing.** The dev workflow must
   produce a fully tested migration branch before production is
   touched. Production is where you apply solutions you already
   verified, not where you discover problems.
5. **Tag migration branches — no floating dev refs.** After migrating a
   plugin and verifying it activates, tag the branch (`git tag N.0.0`)
   and require `^N.0` in the root `composer.json`. Floating
   `dev-migrate/...` refs make deployments non-reproducible: any push
   to the branch changes what `composer install` installs.

## Upgrade path

| From | Steps |
| :--- | :--- |
| 2.x | 2.3 → 3.3 → 4.3 → 5.1 → 6.1 → 7.0 |
| 3.x | 3.3 → 4.3 → 5.1 → 6.1 → 7.0 |
| 4.x | 4.3 → 5.1 → 6.1 → 7.0 |
| 5.x | 5.1 → 6.1 → 7.0 |
| 6.x | 6.1 → 7.0 |

Every chain ends at its last major boundary (7.0); finish with a
minor-update to the latest stable 7.x per the pack freshness check —
`rules/` topics apply only to that release.

## Verification gates

HTTP-200 and server-side render checks cannot detect the failure
classes listed in the pack `SKILL.md` gotchas. The gate set below is
the executable definition of "done"; a gate that cannot run must say
so loudly and must never read as passed.

- **Anonymous homepage check** — necessary first check, never the
  definition of done. A walled-garden community renders almost nothing
  anonymously.
- **Render parity** — a route-render golden master (every registered
  GET route, crawled anonymously and authenticated, HTTP status per
  route) captured on the old version before each step and diffed
  forward after it. Any route that was 2xx/3xx and is now 5xx fails.
  Intended changes go in a reviewed whitelist so the gate stays
  meaningful. Baselines are versioned with the site code.
- **Write paths** — parity is GET-only and never reaches action/CRUD
  code, where the nastier breaks hide (insert/delete and
  relationship-signature drift, DBAL named-param keys, entity `save()`
  contract mismatches, null-subtype container checks). Log in and
  actually POST core journeys (create group, edit user settings) with
  DB-delta verification, and GET-sweep every create/edit form —
  form-build code is the same code the submit action runs.
- **Plugin composition** — the FIRST check when any feature is
  reported missing. Elgg boots each active plugin in a try/catch and
  auto-disables any plugin that throws, silently removing its entire
  feature surface with no 500 anywhere. Diff the active plugin set of
  the migrated site against production (lowercased — Elgg 4 lowercased
  plugin ids); triage each source-active/target-inactive plugin as a
  boot fatal (reproduce with `elgg-cli plugins:activate <id>`) or a
  restore gap (re-activate, then confirm it stays active after a cache
  clear and a fresh boot). A plugin absent from the target disk is
  usually a 2.x-era plugin absorbed into newer core — confirm feature
  coverage, then ignore.
- **Dead-upgrade backfill** — after every tier and at the end, query
  pending `elgg_upgrade` entities (subtype `elgg_upgrade`,
  `is_completed` 0 or null). Each hit is one of: never invoked (run
  `upgrade all`), dead class (a later release deleted the batch — do
  the work by hand, then delete the entity), or a camelCase twin from
  the 4.x plugin-id lowercasing (delete the camelCase entity). Two
  dead-class upgrades historically carried real content:
  `\Elgg\Pages\Upgrades\MigratePageTop` and
  `\Elgg\Discussions\Upgrades\MigrateDiscussionReply(+River)`.
- **utf8mb4 conversion** — core's `AlterDatabaseToMultiByteCharset`
  ALTERs `elgg_private_settings`, a table Elgg 4 removed, and dies
  before converting anything. Convert yourself with a runtime
  table-discovery script (skipping ARCHIVE/MEMORY tables), then mark
  the upgrade completed — and verify the core tables actually
  converted; marking complete is not enough.
- **Query-performance parity** — render parity is status-only; a route
  that scans 10x the rows still returns 200. Benchmark the real query
  shapes on both stacks (same seeded data, only the version differs)
  and diff; a material rise on metadata-hitting shapes with no
  intended schema change is a regression.
- **Simplecache CSS non-empty** — css-crush can silently emit an empty
  stylesheet on certain CSS patterns; the site loads but looks broken.
  Assert the rendered `elgg.css` is over 1 KB after a cache flush.
- **Permalink sweep** — for every object subtype present, assert
  `getURL()` is non-empty and the URL resolves. An entity whose
  subtype was never migrated has no class and no URL; nothing links
  to it and nothing 404s, so no route crawl can see it.
- **Browser smoke** — HTTP 200 hides client-side failures (unresolved
  module specifiers, orphaned CSS overrides). Assert an empty console
  per page type, anonymously and authenticated.

## Anonymized-seed footguns

Rehearse on anonymized production data, not raw PII. The anonymizer
must preserve login and boot, or the seed is an unsound oracle:

- A blanket metastring scrub clobbers login flags (`validated`,
  `admin`, `banned`) and blocks every user — exclude those names from
  the scrub.
- Reset `password_hash` to a known dev bcrypt so users can log in.
- Re-seed `__site_secret__`: Elgg 3.x+ hard-throws on an empty site
  secret, killing every tier past 2.x.
- `elgg_metastrings` is a deduplicated shared pool: a string used as a
  metadata NAME is the same row as some entity's free-text VALUE.
  Scrubbing by value alone renames metadata names and clobbers
  functional values (access modes, tool options). Assert zero
  clobbered names and functional values, not just login flags.
- Refuse inputs that already contain scrub placeholders —
  anonymization is destructive and cannot be re-applied to recover
  the originals.

The migration chain migrates the database only: deploy the production
dataroot directly (it is GUID-bucketed and version-stable across the
chain), and rewrite the site URL and dataroot datalists for the
preview.

## Production cutover

Deterministic checklist, in dependency order; the gates do not change
with the deployment model, only where each step runs:

1. Backup — database dump, dataroot tar, code tag; verify the backup
   is restorable on a separate server. A DB dump alone is not a
   complete rollback point: upgrades rewrite dataroot artifacts
   (icons, thumbnails).
2. Enable maintenance mode.
3. Update plugin code (tagged constraints), then core
   (`composer require elgg/elgg:~N.0`), in that order.
4. Run the upgrade synchronously, then `upgrade all -n -f`; assert
   zero pending upgrades.
5. Verify — homepage, admin panel, key journeys, logs, cron — with
   the render-parity and write-path gates, not just the homepage.
6. Flush caches.
7. Disable maintenance mode.
8. Monitor error logs for 24 hours; verify email and cron.

Rollback is DB + code + dataroot + cache flush — all four, in that
order. On any failed step, keep maintenance mode ON: a step that died
partway leaves a half-migrated schema, and lifting maintenance would
serve it to live traffic. Restoring is itself destructive (it
discards whatever the step accomplished), so it is never implied by
an unattended flag.
