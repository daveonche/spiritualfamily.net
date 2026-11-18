# Deploy Runbook

Operational sequence for deploying the production stack — the Coolify
application built from the repository's `compose.yml` — with the mandatory
pre-deploy database backup. Story S1.6 Step 6; reused as the template for
every future upgrade point's backup-then-upgrade-then-verify sequence
(Stories S1.9 and later).

Platform setup (resource creation, environment variables, HTTPS entry
routing, production email) is documented in `docs/deployment/coolify.md`;
this runbook assumes that setup exists and covers the repeatable deploy
sequence only.

## Rules

- Never run a production data operation without a verified backup (Steps 1
  and 2 below).
- Database dumps stay in git-ignored paths — never committed. On the VPS,
  dumps go to `~/sfn-backups/` (outside the repository). Local dumps
  (old-production phpMyAdmin exports) go to the repo root's gitignored
  `tmp/sfn-backups/` (covered by the `/tmp/` gitignore rule) and are
  transferred to the VPS `~/sfn-backups/` before use. The production dump
  is on the order of 110 MB compressed.
- Credentials come from the containers' own environment (injected by the
  platform); no secret is typed on a command line or stored in the
  repository.
- Any `mariadb`/`mariadb-dump` invocation referencing `$MYSQL_ROOT_PASSWORD`
  must be wrapped in `docker exec … sh -c '…'` so the variable resolves
  inside the container — expanded by the host shell it is empty and
  authentication fails (`Access denied … using password: NO`).
- Elgg schema queries: never compose from general knowledge — derive
  tables and columns from the recorded schema delta (the upgrade records
  below) or the installed core source before writing the query. Known:
  since Elgg 3 the per-type entity tables are dropped (`sites_entity`,
  `groups_entity`, `objects_entity`, `users_entity` — each named in the
  schema-delta tables below); entity titles/names live in `*_metadata`
  (`name='title'` — a plugin's ID is the `title` metadata of its
  `subtype='plugin'` object entity, confirmed via `ElggPlugin::getID()`).
- Reading core source: the VPS host has no repository checkout (Coolify
  runs `up` without the cloned repository present), so host-side paths
  like `src/vendor/…` do not exist there. Read container files via
  `docker exec <app container>` using the in-container paths from log
  stack traces (`/var/www/html/vendor/elgg/elgg/…`), or the local dev
  checkout.

## Production topology during the upgrade journey

Two production databases exist until the user-facing cutover completes:

- **Old live production** — `spiritualfamily.net`, shared hosting (no SSH;
  dumps via phpMyAdmin only), Elgg 2.3.17 (pinned baseline, never
  upgraded). This is the **live site real users continue to use**, and its
  database is the **authoritative** data source: content created there is
  not in the VPS database. Until cutover, every "fresh dump of the
  production database" in any story step means this database.
- **VPS production** — `spiritualfamily.online`, the Coolify deployment
  this runbook operates. It is kept in version-sync at every upgrade point
  (REQ-DEPL-4; currently Elgg 4.3.10 since 2026-11-16) but is **not yet
  the live site**. Its data is a snapshot of the old live production (as
  of 2026-11-04) and goes stale between syncs; it is never the dump
  source while the old site remains live.

The cutover (old site retired; VPS becomes the single live production) is
its own future step. Until then, this note governs every dump-source
decision in the upgrade stories and the runbook.

## Pre-deploy checks

- CI is green on the commit being deployed.
- The commit is pushed, and Coolify's build config points at the intended
  branch (Base Directory `/`, Docker Compose Location `compose.yml`).
- Version pins unchanged: `compose.yml` references `mariadb:10.11.18`,
  `sfn/elgg_webserver:7.4-composer2`, and `sfn/elgg_cron:3.23.3`, and the
  `docker/app/Dockerfile` digest pins match `docs/baseline/baseline.md`.
  Any intentional bump is its own story step, never a routine deploy.
- Working tree clean: `git --no-pager status`.

## Step 1 — Pre-deploy database backup (mandatory)

**Find the dbm container name** — Coolify appends a
`-<uuid>-<build-timestamp>` suffix to container names:

```bash
DBM=$(docker ps --format '{{.Names}}' | grep '^dbm-' | head -n 1)
```

**Take the dump** — executed inside the dbm container, so the root
credentials come from the container's own `MYSQL_ROOT_PASSWORD` and
`MYSQL_DATABASE` environment variables and never appear on the host command
line. `--single-transaction` dumps a consistent InnoDB snapshot without
locking out the running site:

```bash
mkdir -p ~/sfn-backups && docker exec "$DBM" sh -c 'mariadb-dump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' | gzip > ~/sfn-backups/sfn-elgg-$(date +%Y%m%dT%H%M%S).sql.gz
```

The `mariadb-dump` "Using a password … insecure" warning on stderr is
expected and harmless.

**Verify the dump file** — non-zero size (expect on the order of 110 MB
compressed) and intact gzip:

```bash
ls -lh ~/sfn-backups/ && gzip -t ~/sfn-backups/sfn-elgg-*.sql.gz && echo OK
```

## Step 2 — Backup restorability check

Restore the Step 1 dump into a scratch database on the same dbm service —
the same procedure as the migration-readiness check recorded in
`docs/baseline/baseline.md` (Production Dump Restore Verification). The
live database is not touched.

Before the Step 7 migration the production database is empty, so its dump
restores 0 tables — expected, and not a failure. The pre-migration
restorability gate is the S1.3 production-dump restore check (recorded in
`docs/baseline/baseline.md`); from the first post-migration deploy onward,
this check must show the full schema — table count equal to the dump's
`CREATE TABLE` count, derived per the check below.

Native `mariadb-dump` output contains no `CREATE DATABASE`/`USE` redirect
lines, but the phpMyAdmin-exported production dump does (baseline finding):
strip them unconditionally so the procedure is safe for either dump source,
then import:

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE backup_verify"'
```

```bash
gunzip -c "$(ls -t ~/sfn-backups/sfn-elgg-*.sql.gz | head -n 1)" | grep -vE '^(CREATE DATABASE|USE )' | docker exec -i "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" backup_verify'
```

Confirm the scratch database holds the full schema — the table count must
equal the dump's own `CREATE TABLE` statement count. Derive the expected
figure from the dump instead of assuming a fixed number: it grows as the
live site's monthly system_log archive rotations add tables (38 as of the
2026-11-04 pre-upgrade dump, up from 36 in the October records) —
`gunzip -c <dump>.sql.gz | grep -c '^CREATE TABLE'`:

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = \"backup_verify\""'
```

Drop the scratch database:

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE backup_verify"'
```

If the import errors or the table count is wrong, **stop** — do not deploy.
Fix the backup first.

## Step 3 — Deploy

1. In Coolify, open the application and select **Deploy**. This rebuilds
   both images from the committed `compose.yml` and Dockerfiles at the
   pinned commit and restarts the stack.
2. Services start in dependency order: dbm first (its compose healthcheck
   must pass), then app, then cron.
3. Confirm the new containers are up:

```bash
docker ps --format 'table {{.Names}}\t{{.Status}}'
```

## Step 4 — Production database migration (first deployment only)

Restore the production dump into the deployed dbm service's production
database — the same strip-and-import procedure as Step 2, targeting the
production database name instead of the scratch database. The dump source
is the phpMyAdmin-exported production dump (exported locally into the
repo root's gitignored `tmp/sfn-backups/`, then transferred to the VPS
`~/sfn-backups/`; see `docs/baseline/baseline.md`, Production Dump
Restore Verification).

```bash
gunzip -c <production dump>.sql.gz | grep -vE '^(CREATE DATABASE|USE )' | docker exec -i "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

Confirm the production schema holds the full dump — table count equal to
the dump's `CREATE TABLE` count (derived per Step 2):

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = \"$MYSQL_DATABASE\""'
```

Then run the baseline-sync comparison queries and record the figures in
`docs/baseline/baseline.md` (Production Database Migration section): the
datalists `version`/`installed` values, the site row, and the entity-type
counts, compared against the baseline inventory:

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = \"$MYSQL_DATABASE\"; SELECT name, value FROM elggjr_datalists WHERE name IN (\"version\",\"installed\"); SELECT guid, name, url FROM elggjr_sites_entity; SELECT type, COUNT(*) AS entity_count FROM elggjr_entities GROUP BY type ORDER BY type;"'
```

If any exact-match check fails, **stop** — do not proceed to post-deploy
verification; restore the Step 1 backup per Rollback and investigate.

## Step 5 — Post-deploy verification

- [ ] All three containers running (app, dbm, cron)
- [ ] Pinned versions match the baseline: `php -v` → 7.4.27 in the app
  container, `mariadbd --version` → 10.11.18-MariaDB in dbm,
  `/etc/alpine-release` → 3.23.3 in cron
- [ ] `https://<production domain>` loads with a valid certificate and no
  warnings
- [ ] Login works and the administration area is reachable
- [ ] Site content present — the migrated production data is intact
- [ ] Cron log shows scheduled execution (`docker logs <cron container>`)
- [ ] A triggered email is delivered through the production relay (the
  `mail()` trigger in `docs/deployment/coolify.md`, Production email)

## Rollback

- Application-only problem (no data changed): redeploy the prior build in
  Coolify (redeploy the previous commit).
- Database needs reverting: take a fresh backup of the current state first
  (Step 1), then restore the Step 1 backup into the production database —
  strip the `CREATE DATABASE`/`USE` lines as in Step 2 and import into the
  production database name. This replaces live data; only do it when the
  current state is already captured in the fresh backup:

```bash
gunzip -c ~/sfn-backups/sfn-elgg-<timestamp>.sql.gz | grep -vE '^(CREATE DATABASE|USE )' | docker exec -i "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

## Step 6 — Clean re-deploy (reproducibility verification)

One-time verification for Story S1.6 Step 8: the stack is torn down and
re-deployed from the repository alone, then compared against the prior
deployment. Reused whenever reproducibility needs re-proving.

**Tear down** — delete the Coolify application resource entirely (full
re-creation, the strongest "repository alone" proof). The persistent
volumes (`fs-dbmdata`, `fs-dataroot`, `fs-cacheroot`) are instance-level
resources and survive the deletion — the migrated production data is not
touched; decline any "delete volumes" option the delete confirmation
offers. Record the current platform settings first (environment variables
per `docs/deployment/coolify.md`, Domains entry) so the re-created
application can be configured identically, and record the host volume
names — Compose prefixes named volumes with the application's UUID (the
Compose project name), so the names carry the old application's UUID:

```bash
docker volume ls --format '{{.Name}}' | grep -E 'fs-(dbmdata|dataroot|cacheroot)'
```

**Re-create and deploy** — follow `docs/deployment/coolify.md` from
"Create the application": Git-repository application, Docker Compose build
strategy (Base Directory `/`, Docker Compose Location `compose.yml`), the
recorded environment variables (secrets from the platform settings, never
re-typed from anywhere else), and the Domains entry. Before deploying,
re-attach the recorded volumes: the re-created application has a new UUID,
so Compose expects `<new-uuid>_fs-dbmdata` and siblings. Docker has no
volume rename — recreate each volume under the new prefix and copy the
data across with a helper container while the stack is stopped (`cp -a`
preserves ownership; each command prints both sides' file counts and byte
totals, which must match; the originals stay untouched until post-deploy
verification passes):

```bash
docker volume create <new-uuid>_fs-dbmdata
docker volume create <new-uuid>_fs-dataroot
docker volume create <new-uuid>_fs-cacheroot
docker run --rm -v <old-prefix>_fs-dbmdata:/from -v <new-uuid>_fs-dbmdata:/to alpine:3.23.3 sh -c 'cp -a /from/. /to/ && echo "from: $(find /from -type f | wc -l) files, $(du -sb /from | cut -f1) bytes" && echo "to: $(find /to -type f | wc -l) files, $(du -sb /to | cut -f1) bytes"'
docker run --rm -v <old-prefix>_fs-dataroot:/from -v <new-uuid>_fs-dataroot:/to alpine:3.23.3 sh -c 'cp -a /from/. /to/ && echo "from: $(find /from -type f | wc -l) files, $(du -sb /from | cut -f1) bytes" && echo "to: $(find /to -type f | wc -l) files, $(du -sb /to | cut -f1) bytes"'
docker run --rm -v <old-prefix>_fs-cacheroot:/from -v <new-uuid>_fs-cacheroot:/to alpine:3.23.3 sh -c 'cp -a /from/. /to/ && echo "from: $(find /from -type f | wc -l) files, $(du -sb /from | cut -f1) bytes" && echo "to: $(find /to -type f | wc -l) files, $(du -sb /to | cut -f1) bytes"'
```

The copy must happen before the first start: if the new stack starts on an
empty `fs-dbmdata`, MariaDB initializes a fresh database there and the
migrated data is orphaned. If the new stack was already started once
(premature deploy), its fresh volumes exist and `fs-dbmdata` was
initialized — stop the stack, remove its containers, delete the three
`<new-uuid>_fs-…` volumes (verify the prefix against the volume listing
before deleting), recreate them empty, and copy as above. Then deploy.

**Update the site URL in the database** — the site entity's stored URL is
the production domain; it was changed from `https://spiritualfamily.net/`
to `https://spiritualfamily.online/` at this re-deploy (deliberate change,
recorded in `docs/baseline/baseline.md`). `$CONFIG->wwwroot` is
overwritten from the database on every boot, so this single update covers
it:

```bash
docker exec "$DBM" sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "UPDATE elggjr_sites_entity SET url = \"https://spiritualfamily.online/\" WHERE guid = 1"'
```

**Compare against the prior deployment** — everything must be identical
except the recorded site-URL change:

- Versions: `php -v` → 7.4.27, `mariadbd --version` →
  `10.11.18-MariaDB-ubu2204`, `/etc/alpine-release` → 3.23.3; image tags
  `sfn/elgg_webserver:7.4-composer2`, `mariadb:10.11.18`,
  `sfn/elgg_cron:3.23.3`; no floating tags.
- Configuration: the environment-variable walk matches the Step 3 record
  in `docs/baseline/baseline.md`; build config Base Directory `/`,
  Docker Compose Location `compose.yml`.
- Data intact: 36 tables, datalists `version`/`installed` unchanged, site
  row GUID 1 (URL now `https://spiritualfamily.online/`), entity counts
  unchanged.
- Site loads at `https://spiritualfamily.online/` with a valid certificate.

Record the result in `docs/baseline/baseline.md` (Production Re-Deploy
Reproducibility section) and add a Run log row below.

## Elgg major-version upgrade sequence (S1.9 record)

Story S1.9 Step 5 executed the first sequential hop (Elgg 2.3.17 →
3.3.25) locally against the restored production copy. Recorded here as
the reusable template for every future upgrade point; the production
deploy at the upgrade point additionally follows Steps 1–5 above
(backup, restorability, deploy, data migration, verification).

Production parity confirmation (S1.9 Step 2): before any upgrade work,
production was confirmed installed and serving the working pinned Elgg
2.3.17 baseline — front page over HTTPS, admin login working, core
pages rendering — with the stack matching the pinned versions (the
2026-10-20 deployment version cross-check and the 2026-10-22 clean
re-deploy verification, both recorded in `docs/baseline/baseline.md`).

Pre-upgrade backup for this upgrade point (S1.9 Step 7, taken
2026-11-04): the authoritative data still lives on the old production
site (live, shared hosting, no SSH), so the backup was exported there via
phpMyAdmin — `sdb-74_hosting_stackcp_net.sql.gz`, 120,873,040 bytes,
gzip-integrity verified — into the repo's gitignored `tmp/sfn-backups/`,
then transferred to the VPS `~/sfn-backups/`. Restorability verified on
the dbm scratch database `backup_verify`: 38 tables, count and names
matching the dump's 38 `CREATE TABLE` statements (the two beyond the
recorded 36 are the system_log archive tables of 2026-09-30 and
2026-11-01). This dump is the Step 8 rollback point and data-sync source;
retain it until the production upgrade is confirmed successful. The Run
log row is added at the production deploy.

1. **Pin the target** — declare the exact `elgg/elgg` version in
   `src/composer.json` and regenerate the lock (per
   `docs/dependencies/S1.9-dependencies.md`), then rebuild the local
   stack at the pinned pair. The PHP pin does not move for a 2.3 → 3.x
   hop (verify the constraint via `composer show -a elgg/elgg <version>`).
2. **Run the official routine in the browser** — `upgrade.php` on the
   local vhost. The CLI alternative (`elgg-cli upgrade async`) was ruled
   out for this stack: the CLI image has no mysql transport, so the web
   path is the reproducible one.
3. **Remediate boot blockers one at a time**, re-running `upgrade.php`
   after each fix, in the order observed here:
   - `extension=pdo_mysql` missing — the repo `docker/app/php/extensions.ini`
     overwrites the base image's conf.d copy, which carried the
     extension lines; Elgg 3 talks to MySQL only through PDO, so without
     it the site cannot connect. Commit `d309071`.
   - `.htaccess` rewrite `index.php?__elgg_uri=$1` → `index.php`
     (Elgg 3 rewrite requirement, official upgrade notes). Commit
     `a902e1f`.
   - Remove stale Elgg 2.3 plugin copies from `src/mod/` — every plugin
     bundled with the new core ships under `vendor/` instead, and a
     stale local copy calling a removed API fatals every boot (observed:
     `blog` → `Call to undefined function elgg_register_library()`).
     Verify the vendor bundle with
     `ls src/vendor/elgg/elgg/mod | sort` before deleting. Third-party
     plugins in `mod/` go with them, with their data migration recorded
     as follow-up work (here: `amap_coverphoto` → Elgg 3 native `cover`
     icon type, pending).
   - `extension=gd` missing — Elgg 3 renders entity icons through
     Imagine's Gd driver (`Gd not installed` fatal while `gd.so` sat
     unloaded in `extension_dir`); same overwriting-ini cause as
     pdo_mysql. Verify loaded after rebuild with
     `… exec -T app php -m | grep -i gd`. Commit `b4f7cb7`.
   - Keep the `src/mod/` directory itself (recreated empty in commit
     `7953d27` after the cleanup above had removed it with its
     contents): it is the only scan root Elgg resolves plugin entities
     against — `plugins_path` defaults to `Paths::project() . 'mod/'`
     (`ServiceProvider.php`), and `ElggPlugin::getPath()` computes
     `plugins_path . <id>` at runtime; nothing is stored per entity —
     and it is the destination for manually installed plugins
     (Story S1.14). Elgg 3.3 discovers plugins only under `plugins_path`;
     the vendor bundle (`src/vendor/elgg/elgg/mod/`) is never scanned in
     the elgg-as-composer-dependency layout, so bridge every bundled
     plugin into the scan root with a relative symlink
     (`mod/<id> -> ../vendor/elgg/elgg/mod/<id>`; relative so one link
     resolves both inside the container and in the host checkout, and
     composer regeneration of `vendor/` never breaks it). Deleting
     `mod/` leaves every plugin entity enabled-but-invalid ("Missing
     ElggPluginPackage", cannot activate): `generateEntities()`
     early-returns on an empty/missing scan dir before its disable
     loop, so the system can neither regenerate nor even disable the
     orphans.
   - Delete legacy scan-artifact plugin entities once the scan root is
     populated: plugin entities whose ID is an Elgg package
     subdirectory, not a plugin (`actions`, `classes`, `install`,
     `languages`, `lib`, `screenshots`, `vendor`, `views` — one batch
     created 2021-03-14 by a scan run against an unpacked Elgg source
     tree on the pre-container host — plus `disabled`, 2023-09-20).
     SQL-delete each GUID across `*_metadata`, `*_private_settings`,
     `*_annotations`, and `*_entity_relationships`, then `*_entities`
     (children first). Dropped third-party plugin entities are NOT
     deleted: with the scan root populated, `generateEntities()`'s
     disable loop disables them while preserving their settings (the
     core's own rule: "don't delete the entities because they hold
     settings"), keeping the recorded data migrations (e.g.
     `amap_coverphoto` → `cover`) possible. The `config` table's `path`
     row still holds the pre-container host path; it is inert at
     runtime (only `ElggInstaller` reads it) and is left in place.
4. **If an async upgrade fails on MariaDB ≥ 10.3** ("Failure to set
   'innodb_large_prefix'"): the variable was removed in MariaDB 10.3 and
   is always on in 10.11. Elgg's `AlterDatabaseToMultiByteCharset`
   upgrade probes it with `SHOW GLOBAL VARIABLES`, misreads the empty
   result as OFF, runs `SET GLOBAL innodb_large_prefix = 'ON'` which
   throws, and returns before any ALTER executes — so the conversion
   never happened. Reproduce the class's SQL manually as root on the dbm
   container (`ALTER DATABASE … utf8mb4` plus per-table
   `ROW_FORMAT=DYNAMIC` / `CONVERT TO CHARACTER SET utf8mb4` with the
   class's index drop/re-add for its 16 target tables; scratch scripts
   commit `8a5e563`, files removed from tracking after use). Check
   completeness with
   `SELECT table_name, table_collation FROM information_schema.tables
   WHERE table_schema = DATABASE()` — all 16 must read
   `utf8mb4_general_ci`; the vendor upgrade then resolves itself via its
   own `shouldBeSkipped()` and disappears from the pending list.
5. **Complete all remaining pending upgrades** in the admin area
   (asynchronous data migrations) and confirm the pending list is empty
   and error-free before declaring the step verified.
6. **Record** verification figures and follow-up work (dropped-plugin
   data migrations) in the step report and `docs/baseline/baseline.md`
   as applicable; the Run log row below is added at the production
   deploy, not the local upgrade run. The production post-upgrade
   verification (S1.9 Step 9, 2026-11-06) followed the deploy: admin
   login + dashboard confirmed; representative core pages — front
   page, activity, blog listing + post, group, profile, file, page,
   discussion — all rendering; the app log captured to
   `.log/production-app.log` with 0 fatal-marker matches (container
   `app-z7fqpfbtv8s4uu8rbbvlt98v-20261105T224651`) — mirroring the
   local Step 6 verification with no divergence.

## Elgg major-version upgrade sequence (S1.10 record)

Story S1.10 Step 5 executed the second sequential hop (Elgg 3.3.25 →
4.3.10) locally against the restored production copy — as a **two-hop
run**, because the restored copy is 2.3-era data (datalists
`version=2016092300`) and Elgg upgrades one major version at a time. The
pattern is reusable at every future upgrade point whose restored copy lags
the pinned target by more than one major version; the production deploy at
the upgrade point additionally follows Steps 1–5 above, and the Run log
row is added at the production deploy, not the local upgrade run.

**Hop A — temporary rebuild at the previous pin.** Swap the pin surfaces
(`src/composer.json`, `src/composer.lock`, `src/vendor/`, `src/mod/`) to
the previous pin's commit, run `upgrade.php`, then restore HEAD for Hop B;
HEAD never moves. Locate the source commit with
`git --no-pager log --oneline -- src/composer.json` and pick the one
holding both the composer pin and a complete tracked vendor tree (here
`b84b6e1` — `src/vendor/` was untracked during the S1.9 composer work, so
the pin commit alone is not enough). The swap's three failure traps and
verification gates are recorded in `AGENTS.md` (Working-tree version
swaps): container-root removal of root-owned vendor files before any
host-side `rm -rf`, `git reset <commit> -- <paths>` to clear the stale
index entries a `git checkout <commit> -- <path>` leaves behind, and the
empty-`status` / matching-file-counts / container-probe gate. Clear the
cache volume after the swap and pause cron across both hops; never touch
the dataroot — the filestore lives there. Two deviations from the S1.9
shape, both deliberate: no app image rebuild (the dev bind mounts serve
the swapped tree live and the PHP pin is identical across both hops), and
no site-URL alignment (the local `ELGG_WWWROOT` pin governs URL
generation, so the copy's `.net` site URL stays inert).

**Expected schema delta, 2.3 → 3.x — verify against a recorded
expectation, never re-derive it.** The dump's 38 tables become 30: Elgg
3's schema consolidation drops 8 tables, each named in the core's own
migration list (`src/vendor/elgg/elgg/engine/schema/migrations/`):

| Dropped table | Migrating migration |
| :--- | :--- |
| `datalists` | `20170728020000_migrate_datalists_to_config.php` — values into `config`, then the table is dropped |
| `metastrings` | `20170728030000_denormalize_metastrings.php` |
| `geocode_cache` | `20170808084728_drop_geocode_cache.php` |
| `sites_entity` | `20171006111953_drop_sites_entity_table.php` |
| `groups_entity` | `20171006131622_drop_groups_entity_table.php` |
| `objects_entity` | `20171009115032_drop_objects_entity_table.php` |
| `users_entity` | `20171010095648_drop_users_entity_table.php` |
| `entity_subtypes` | `20171021111059_denormalize_entity_subtypes.php` |

Data-survival checks after the hop: entity count preserved (88330 → 88343
here — growth from the run, zero loss); `config` holds the migrated values
(`installed`, `processed_upgrades`, `simplecache_enabled`, serialized);
`version` is absent from `config` **by design** — the migration excludes
it, and Elgg 3 tracks schema state in the `migrations` table instead (44
rows, one per shipped migration file).

**Standard verification — diff the dump's table list against the
post-upgrade list.** Derive both lists and compare, so every dropped or
added table is classified against the core's migration list before any
remediation is attempted:

```bash
mkdir -p .log && gunzip -c tmp/sfn-backups/<dump>.sql.gz | grep -oE '^CREATE TABLE `[A-Za-z0-9_]+`' | sort | tee .log/dump-tables.txt
docker compose -f compose.yml -f compose.dev.yml exec -T dbm sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name"'
```

**Blockers.** The MariaDB ≥ 10.3 `innodb_large_prefix` charset-conversion
failure recurred on this fresh 2.3-era copy exactly as recorded (S1.9
record item 4). The scratch scripts were recovered from their commit
(`git --no-pager show 8a5e563:.mb4-check.sql` and
`git --no-pager show 8a5e563:.mb4-convert.sql` — committed, then removed
from tracking after use) and piped to root mariadb on the dbm container:
all 16 target tables → `utf8mb4_general_ci` in 4m55.7s, after which the
vendor upgrade resolved via its own `shouldBeSkipped()` and left the
pending list. Duration-capture gotcha: the command runner is dash, where
the bash builtin `time` does not exist — a bare `time` fails with
`/bin/sh: 1: time: not found` and the pipeline never executes; wrap the
command in `bash -c` (`AGENTS.md`, Duration capture).

**Hop B — restore the HEAD pin and run the target hop.** Restore the pin
surfaces from HEAD (with the `git reset` index cleanup), clear the cache
volume, and run `upgrade.php` again. Observed on this copy: the routine
ran to completion including all pending upgrades, with no new blockers.
Record the 3.x → 4.x schema delta with the same dump-vs-post-upgrade diff
so the next upgrade point (S1.11) verifies against a recorded expectation.

**Production deploy (S1.10 Steps 8–10, 2026-11-16).** The deploy followed
Steps 1–5 above (Run log row below). Recorded here in execution order:

- **Step 2 parity baseline.** Production parity for this upgrade point is
  the 2026-11-06 S1.9 Step 9 post-upgrade verification (S1.9 record above;
  `docs/baseline/baseline.md`, Production Upgrade): production serving the
  pinned 3.3.25 — admin login + dashboard, representative core pages, test
  email through the production relay, pins unchanged, 0 fatals. That
  verified state is the parity confirmation; no separate S1.10 walk was
  performed.
- **Ghost-plugin storm classification** (deploy-day log capture,
  `.log/production-app.log`): 4981 ELGG.ERROR lines, all
  `Cannot include elgg-plugin.php` — 2490 for `diagnostics` (guid 9) +
  2491 for `notifications` (guid 25), zero non-storm errors. Bounded
  12:36:07–13:26:57 UTC: the boot path (`activateEntities`) fired until
  the 13:15:34 disable remediation; the upgrade run's `Upgrade\Locator`
  path re-fired it once per ghost plugin at 13:26:57 (the Locator scans
  disabled entities too — `getStaticConfig()` on each located plugin).
  Nothing after 13:26:57. The pre-deploy "verified 0 errors in a 5m
  window" was a browsing-only window, which never touches the Locator
  path — consistent with this record, not contradicted by it.
- **Ghost-plugin auto-removal.** Post-upgrade, both ghost entities are
  absent (guid 9/25 not in `elggjr_entities`); the admin dashboard showed
  a removal notification after the `/upgrade.php` run; nothing was
  deleted manually — system-initiated. The installed core's
  `generateEntities()` deletes unloadable plugin entities ("if the ID is
  wrong, delete the plugin because we can never load it" →
  `$plugin->delete()`); the exact code path for guid 9/25 is unestablished,
  the end state is verified: entities absent, the 30-plugin enabled
  inventory intact (incl. `activity`/`friends`/`friends_collections`/
  `system_log` at g97002–97005), disabled legacy plugins preserved with
  their settings.
- **Step 10 post-upgrade verification (2026-11-16).** Admin login +
  dashboard confirmed; representative core pages — front page, Activity,
  Blog listing + post, group, profile, File, Page, discussion — all
  rendering; log capture 0 PHP Fatal/Parse and 0 ELGG.CRITICAL —
  mirroring the S1.9 record's verification shape.
- **For the next upgrade point (S1.11).** Expect the Locator-path storm
  re-fire at the upgrade run while disabled/ghost plugin entities remain —
  do not chase it; classify it (per-plugin counts + last timestamps) and
  verify the dropped/ghost entities' end state (absent vs disabled) after
  the run. Compose Elgg schema queries only from the recorded schema
  delta or the installed core source (Rules above).

## Elgg major-version upgrade sequence (S1.11 record)

Story S1.11 Step 9 executed the third sequential hop (Elgg 4.3.10 → 5.1.12)
on production on PHP 8.2.21 — the first runtime move since the baseline
(Coolify `PHP_VERSION` 7.4 → 8.2 set before the deploy; a stale value
rebuilds the 7.4 image, the same override-beats-`.env` gotcha caught at the
Step 3 local rebuild). The production deploy followed Steps 1–5 above; the
Run log row is added at the deploy. Recorded in execution order:

- **Move Files blocker (the hop's defining issue).** The file plugin's
  async `MoveFiles` upgrade (3403 items) generates a master icon per image
  via Imagine/Gd; the base image's 128M limit fataled every batch
  (`Allowed memory size … Gd/Image.php`), leaving the counter at 0/3403
  with Elgg's generic AJAX-error message. Remediated with a repo ini
  override (`docker/app/php/limits.ini`, commits `ae919fe`/`ddcd7bbe`/
  `87b34fe9`): `memory_limit` 128M → 512M → **1024M** (one ~50–64 MP image
  holds two ~192 MB buffers during the icon-size sequence),
  `max_execution_time` 30s → 120s (0 timeout fatals observed), with the
  composer CLI wrapper pinning `max_execution_time=0` back for composer
  runs only. Permanent production headroom — the S1.13 fresh-restore hop
  re-runs this migration chain against 2.3-era image data. Host context:
  kernel `free -m` 15835 MB total / ~8 GB available (provider panel reports
  12 GB — discrepancy unresolved, hosting-side); swap 17 MB.
- **Embed ghost-plugin storm — classified, not chased** (per the S1.10
  guidance): 696 ELGG.ERROR lines, all `embed` (guid 11 — dropped in S1.4,
  entity still enabled), boot path (`activateEntities`, from 20:09:09) then
  the Locator path (`UpgradeController → generateEntities → deactivate`,
  last 20:17:41), nothing after. End state verified absent on the redeploy
  boot (0 lines) — the upgrade run retired the ghost, the S1.10 pattern.
- **Schema delta, 4 → 5 — recorded expectation for S1.12:** 32 → 31 tables;
  `elggjr_private_settings` dropped by the core's own
  `20220825064811_merge_private_settings_in_metadata.php` (rows merged into
  `*_metadata`); `elggjr_migrations` 52 → 54 (= shipped migration files).
  The dump-vs-post-upgrade table diff (runbook pattern) classified one
  collation artifact (access_collections sort-order swap) before the real
  delta.
- **Icon remediation.** The interrupted 128M/512M-era icon runs left 10
  zero-byte `icons/icon/master.jpg` files; MoveFiles sets its
  `_elgg_file_migrated` marker after icon generation, so re-runs took the
  old-file-missing branch and marked entities migrated without
  regenerating — and the on-demand icon handler does not rebuild a missing
  master from the source. Fix: delete the 10 zero-byte masters, then
  regenerate via a scratch script (`deleteIcon()` +
  `saveIconFromElggFile()`, the upload action's own calls) run as www-data
  with ignore access (5 of the 10 are access-restricted and invisible to a
  guest-context CLI). Verified: all 10 masters non-zero, thumbnails render.
- **Site email stale (mail broken until fixed).** Elgg 5's mail path sends
  with the site email as envelope-from; the setting still carried
  `site@spiritualfamily.net` (old-domain data), and the StackCP relay
  rejects it (550 "Please send as noreply@spiritualfamily.online") —
  surfacing as `Unable to send mail` ELGG.CRITICAL on bot-triggered
  validation emails. Fixed by updating the site entity's `email` metadata
  (guid 1) to `noreply@spiritualfamily.online`; Elgg-native send verified
  via a password-reset email. The manual `-f` trigger of 2026-10-20 had
  masked this: it passed the envelope-from explicitly, Elgg's native path
  was never exercised until now.
- **Known orphans (data-level, deferred to S1.13):** the VPS dataroot was
  transferred from the old host ~2026-10-21 and never re-synced — old-site
  uploads after that date exist as DB rows without file data (observed:
  guid 96926, uploaded 2026-10-24). **The S1.13 fresh restore must include
  a fresh dataroot transfer from the old host**, not only the database
  dump. CKEditor-embedded `/photos/...` URLs 404 on the VPS until tidypics
  arrives (S1.14 manual install); absolute `.net` embed URLs keep serving
  from the old site until cutover, then need a content rewrite.
- **Step 10 post-upgrade verification (2026-11-18).** Admin login + core
  pages render; 0 PHP Fatal/Parse and 0 new ELGG.CRITICAL after the mail
  fix (the 4 CRITICAL lines are the classified bot-triggered relay
  rejections, pre-fix); file thumbnails + download button verified; pins
  unchanged (MariaDB 10.11.18 / alpine 3.23.3; PHP now 8.2.21).

## Run log

One row per production deploy:

| Date | Commit | Backup file | Restorability check | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 2026-10-20 | initial S1.6 deployment (Steps 1–5) | n/a — pre-runbook | n/a | baseline deployed; production email verified |
| 2026-10-21 | n/a — data migration only (runbook Steps 1–2, 4; no code deploy) | `sfn-elgg-20261020T191947.sql.gz` (pre-migration, empty DB) + `spirihf6_elgg114.sql.gz` (source dump) | 0-table empty-dump check; gate = S1.3 restore check | Step 7: production dump restored into dbm; baseline-sync exact match |
| 2026-10-22 | `8593c88` | `sfn-elgg-20261022T175255.sql.gz` | passed — 36 tables | clean re-deploy (S1.6 Step 8); premature deploy recovered via volume re-attach; domain → `spiritualfamily.online` |
| 2026-11-05 | `b84b6e1` | `sdb-74_hosting_stackcp_net.sql.gz` (Step 7 pre-upgrade, restorability verified 2026-11-04) + `sfn-elgg-20261104T162613.sql.gz` (fresh pre-restore) + `sfn-elgg-broken-20261105T230009.sql.gz` (intermediate-state forensic) | passed — 38 tables | S1.9 Step 8: production upgraded Elgg 2.3.17 → 3.3.25. Converged over four commits: `09ed6dec` (image baked without vendor/ — untracked since the S1.9 composer-install pivot the Dockerfile never implemented), `afe8b45d` (vendor re-tracked), `1cf33e85` (`!src/vendor/**` — the `**/cache/` pattern stripped the psr/cache package, fataling every full boot), `b84b6e1` (`ELGG_WWWROOT` pin — the request sniff yields http:// behind the TLS proxy, failing the upgrade path's RewriteTest self-check). The first upgrade pass completed all migrations but died at the psr fatal before the completion phase; recovered via clean Step 7-dump restore + full re-run. Charset async upgrade remediated with the recorded manual SQL (16 tables → utf8mb4_general_ci); pending list empty; post-upgrade bot registration deleted |
| 2026-11-16 | `d2feb7a5` | `sfn-elgg-20261116T100100.sql.gz` (pre-deploy, 3.3.25 state) + `sdb-74_hosting_stackcp_net-20261114.sql.gz` (S1.10 Step 8 authoritative-data backup, retained) | passed — 31 tables (31 = 31 `CREATE TABLE`) | S1.10 Step 9: production upgraded Elgg 3.3.25 → 4.3.10 in place (data = 2026-11-04 old-live snapshot; fresh live-site sync deferred to S1.13). Pin commit `1eca8db2` (2026-11-14). Blocker remediated pre-upgrade: two 2.3-era bundled plugins dropped from the Elgg 4 bundle (`diagnostics` guid 9, `notifications` guid 25) with entities still enabled → `Cannot include elgg-plugin.php` ELGG.ERROR on every boot (2436× each in 3h); both entities disabled + cache cleared, verified 0 errors in a 5m browsing-only window — the storm re-fired via the upgrade run's Locator path (deploy-day capture: 2490 + 2491 lines, bounded 12:36:07–13:26:57, none after; see the S1.10 record); both entities subsequently auto-removed by the upgrade run (admin notification; not manual), end state verified absent. Schema delta: +`elggjr_delayed_email_queue` (migration `20210412110921`); `elggjr_migrations` 44 → 52 (= shipped migration files). Cron paused across the upgrade window, restarted after. 4.1 commentable deprecation warnings (`hjwall`/`campaign`/`album`) noted for S1.11 |
| 2026-11-18 | `87b34fe9` | `sfn-elgg-20261117T194304.sql.gz` (pre-deploy, 4.3.10 state, restorability verified 2026-11-17: 32 = 32 tables) + `sfn-elgg-20261117T183058.sql.gz` (S1.11 Step 8 pre-upgrade backup, retained) | passed — 32 tables (32 = 32 `CREATE TABLE`) | S1.11 Step 9: production upgraded Elgg 4.3.10 → 5.1.12 on PHP 8.2.21 (Coolify `PHP_VERSION` 7.4 → 8.2 before deploy). Deployed in three stages: `0e068977` (upgrade + first Move Files run), `ddcd7bbe` (limits.ini 512M + composer CLI exemption), `87b34fe9` (memory_limit 1024M) — the Move Files async upgrade (3403 items) was blocked by GD memory fatals until 1024M. Embed ghost-plugin storm classified (696 ELGG.ERROR lines, all `embed` guid 11, boot + Locator paths 20:09–20:17 UTC 2026-11-17, end state verified absent on the redeploy boot). Schema delta: `elggjr_private_settings` dropped (migration `20220825064811`), 32 → 31 tables; `elggjr_migrations` 52 → 54 (= shipped files). Icon remediation: 10 zero-byte masters (interrupted icon generation; the re-run marked entities migrated without regenerating) deleted and regenerated via a scratch script with ignore access. Site email stale (`site@spiritualfamily.net` → `noreply@spiritualfamily.online`, elggjr_metadata guid 1) — relay 550 rejections fixed, Elgg-native reset email verified. Cron paused across the upgrade window, restarted after. Post-upgrade: core pages render, 0 fatals/criticals, file thumbnails + download button verified. Mailbox password rotation pending (debug trace exposed it) |
