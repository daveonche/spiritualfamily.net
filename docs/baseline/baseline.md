# Elgg 2.3 Baseline

Ground-zero version reference for the Spiritual Family Network upgrade journey
(Story S1.1). Every later upgrade step cites these pinned values.

## Pinned Versions

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core | 5.1.12 | Story S1.11 Step 1 pin (Packagist p2 resolution, 2026-11-16); declared in `src/composer.json` (`"elgg/elgg": "5.1.12"`) and `src/composer.lock` (tracked — the baked-image pin) at Story S1.11 Step 2 |
| PHP runtime | 8.2.21 | elgg/elgg 5.1.12 declares `"php": ">=8.0"` (Packagist p2 metadata); its exact-pinned laminas/laminas-mime 2.11.0 requires `~8.0.0 \|\| ~8.1.0 \|\| ~8.2.0`, excluding 8.3 (Story S1.11 Step 2 discovery); upstream base `coldtrick/elgg_webserver:8.2` (see `docker/app/Dockerfile`); app image `sfn/elgg_webserver:8.2-composer2` built at Story S1.11 Step 3 — the rebuilt container reports PHP 8.2.21 (`php -r 'echo PHP_VERSION;'`, 2026-11-17) |
| Database server | MariaDB 10.11.18 | Compose stack `dbm` image `mariadb:10.11.18` (see `compose.yml`); confirmed via `SELECT VERSION()` → `10.11.18-MariaDB-ubu2204` |
| Cron service base | alpine:3.23.3 | Compose stack `cron` image `sfn/elgg_cron:3.23.3`, built locally from `alpine:3.23.3` (see `docker/cron/Dockerfile`); the image tag carries the pinned base version (Story S1.6 Step 2) |
| phpMyAdmin | 5.2.3 | Compose stack `pma` image `phpmyadmin/phpmyadmin:5.2.3` (see `compose.dev.yml`); confirmed via the image's `/var/www/html/package.json` |

This is the single pinned Elgg core version — the active pin, moved to 5.1.12
by Story S1.11 Step 1 (the 4.3.10 second-hop value is preserved as the
resolution record in the Upgrade-Stage Pin section below); it is the
reference value for all subsequent steps in the upgrade sequence
(REQ-FR-PU-5).

The pinned PHP runtime sits directly beneath the core version so future steps
can cite them as a pair. Constraint note: the pinned Elgg 5.1.12 core declares
`"php": ">=8.0"` (Packagist p2 metadata, recorded in the Upgrade-Stage Pin
section below); the 7.4.27 runtime does not satisfy it, so the runtime pin
moves — the first runtime move since the baseline (the sprint story's
Developer Note: "PHP version changes from here on"). Story S1.11 Step 1
initially pinned 8.3; Step 2's resolution proved that pair unresolvable —
elgg 5.1.12 exact-pins laminas/laminas-mime 2.11.0, whose
`~8.0.0 || ~8.1.0 || ~8.2.0` constraint excludes 8.3 — so the pin was
corrected to 8.2, the highest line the pinned tree supports. The PHP 8.3
runtime belongs to the journey's Elgg 7.1.0 target (Story S1.13).

The database server pin (Story S1.2 Step 5) targets production parity:
production reports `10.11.18-MariaDB-log` via phpMyAdmin server info and the
containerized server reports `10.11.18-MariaDB-ubu2204` — the `-log` and
`-ubu2204` suffixes are build variants, not part of the Docker tag. Bump the
`mariadb:` tag deliberately alongside the upgrade journey, never float.

Environment note (2026-09-19; base line updated to PHP 8.2 at Story S1.11
Step 3): the app image layers Composer 2.x (2.10.3 at
time of recording) onto the upstream PHP 8.2 webserver image via
`docker/app/Dockerfile`, confirmed via `docker compose exec app composer --version`,
so manifest and lock-file work runs inside the orchestration. The web SAPI's
`open_basedir` restriction is unchanged; the Composer CLI wrapper clears
`open_basedir` and `disable_functions` for the CLI only — the base image
disables `putenv()`, which Composer requires (recorded 2026-10-18 after a
container rebuild wiped the writable-layer patch that had masked it).

Environment template (Story S1.2 Step 6): every environment-specific setting
is injected via environment variables — Compose interpolation and
`env_file: .env` in `compose.yml`, `getenv()` in
`src/elgg-config/settings.php`. The committed template `.env.example`
documents every required variable with placeholder values and no real
secrets; copy it to `.env` and supply real values locally. `.env` is
git-ignored, so real secrets never enter the repository.

## Upgrade-Stage Pin (Story S1.11 Step 1)

The third sequential upgrade step (Elgg 4 → Elgg 5) resolved the sprint
story's exact-version pinning flags from the official release information:
the Packagist p2 metadata for elgg/elgg
(`repo.packagist.org/p2/elgg/elgg.json`, queried 2026-11-16) and the upstream
runtime image tag listing (Docker Hub `coldtrick/elgg_webserver`, queried
2026-11-16):

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core (upgrade target) | 5.1.12 | Newest stable 5.x release in the Packagist version list — the 5.x line ends at 5.1.12 (the 6.x line begins above it; no 5.2.x exists) |
| PHP runtime | 8.2.21 | elgg/elgg 5.1.12 declares `"php": ">=8.0"` (Packagist p2 metadata); the pinned 7.4.27 runtime does not satisfy it, so the runtime pin moves. Step 1 pinned 8.3; Step 2's resolution corrected it to 8.2 — elgg 5.1.12 exact-pins laminas/laminas-mime 2.11.0, whose `~8.0.0 \|\| ~8.1.0 \|\| ~8.2.0` constraint excludes 8.3, making (5.1.12, 8.3) unresolvable. 8.2 is the highest line the pinned tree supports and exists upstream (`coldtrick/elgg_webserver:8.2`); the Step 3 rebuild reports PHP 8.2.21 in the rebuilt container (2026-11-17); the PHP 8.3 runtime arrives with the Elgg 7.1.0 target (Story S1.13, per `docs/tech_stack.md`) |

With Step 1 complete, the Pinned Versions table above carries this pair as
the active pin — the core row moved from the 4.3.10 second-hop value, which
the S1.10 section below preserves as the resolution record. The MariaDB,
cron, and phpMyAdmin pins are untouched by this step. Step 2 declared the
pair in `src/composer.json`/`src/composer.lock` (platform php 8.2) and
produced the 8.3 correction recorded above; the runtime image rebuild lands
at Step 3, and the vendor-tree sync completes there too — Composer 2.10
refuses to parse the asset-packagist tar archives on the still-7.4 runtime
(`Refusing to parse a tar/phar archive on PHP < 8.0`), so the
install-from-lock verification moves behind the rebuild. The aborted update
also leaves the guard suite unrunnable until then, not merely mismatched:
the update removed `symfony/polyfill-php81` from the vendor tree while the
4.3.10-era autoload map still references it (`Failed opening required
.../symfony/polyfill-php81/bootstrap.php`), and the resolved 5.x tree
requires PHP 8.x at runtime (laminas-mail 2.25.1 `~8.1.0 || ~8.2.0 ||
~8.3.0`; laminas-servicemanager/stdlib/validator `~8.2.0 || ~8.3.0 ||
~8.4.0 || ~8.5.0`; doctrine/event-manager `^8.1`). Until Step 3 lands, the
pin-consistency guard tests (`BaselineRuntimeVersionTest`,
`CiPinnedRuntimeTest`, `ComposeImageBuildTest`) read the previous 7.4
surfaces — the expected transient mismatch, per the story's step order.
Dependency-change record: `docs/dependencies/S1.11-dependencies.md`
(transitive tree replacement, laminas-mime runtime-pin correction, vendor
sync deferred to the Step 3 image rebuild).

Step 3 rebuild record (2026-11-17): the app image rebuilt on
`coldtrick/elgg_webserver:8.2` (digest
`sha256:a98dbd585dcfb259bb8933f0de6dd13a07db872c1ba33e3a2426403b7dbc6350`,
Base-Image Digest Pins below); the rebuilt container reports PHP 8.2.21
with Composer 2.10.3. Rebuild gotcha for the runbook: a stale
`PHP_VERSION=7.4` export in the command environment outranked the `.env`
and compose-default interpolation, so the first OK rebuild silently
produced the 7.4 image again — an explicit `PHP_VERSION=8.2` assignment
on the rebuild command resolved it (the command environment beats `.env`
in Compose interpolation). The vendor-tree sync completed in the rebuilt
container the same day: `composer update --lock` refreshed the lock's
content-hash and `platform-overrides.php` to 8.2.21, and `composer install`
reported "Installing dependencies from lock file (including require-dev)" —
the asset-packagist tar dists extracted natively on the 8.2 runtime,
closing the Step 2 partial-install state; the vendored core reports 5.1.12.
The previous core version's caches were cleared (`$WWW_CACHE` emptied)
before the guard suite ran; the suite is green (138 tests, 1158
assertions) with the pin guards updated to the new pair. A second rebuild
verified the workaround-free Dockerfile (the legacy bullseye msmtp
workaround removed with the 7.4 base): the build installs msmtp from
bookworm main cleanly and the recreated container still reports PHP 8.2.21.

## Upgrade-Stage Pin (Story S1.10 Step 1)

Superseded as the active pin by Story S1.11 Step 1 (section above); preserved
as the resolution record for the second hop.

The second sequential upgrade step (Elgg 3 → Elgg 4) resolved the sprint
story's two "latest stable" pinning flags from Packagist, queried via
`composer show -a elgg/elgg` inside the app container (2026-11-07):

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core (upgrade target) | 4.3.10 | Latest stable 4.x release in the Packagist version list — the 4.x line ends at 4.3.10 (a `4.12.0` string in the listing is a bundled CKEditor dependency version, not an elgg/elgg release; `composer show -a elgg/elgg 4.3.10` confirms the release) |
| PHP runtime | 7.4.27 (unchanged) | elgg/elgg 4.3.10 declares `"php": ">=7.4"` (`composer show -a elgg/elgg 4.3.10`); the pinned 7.4.27 runtime satisfies it, so the runtime pin does not move |

With Step 1 complete, the Pinned Versions table above carries this pair as
the active pin — the core row moved from the 3.3.25 first-hop value, which
the S1.9 section below preserves as the resolution record. The MariaDB,
cron, and phpMyAdmin pins are untouched by this step. The target is declared
in `src/composer.json` (`"elgg/elgg": "4.3.10"`), so every environment
resolves the same pair; the runtime declarations (Dockerfile `PHP_VERSION`
default, Compose build arg, CI image) stay at 7.4 and need no edit.
Dependency-change record: `docs/dependencies/S1.10-dependencies.md`
(transitive tree replacement, security-advisory acceptances, inert
`elgg/login_as` replace removal).

## Upgrade-Stage Pin (Story S1.9 Step 1)

Superseded as the active pin by Story S1.10 Step 1 (section above); preserved
as the resolution record for the first hop.

The first sequential upgrade step (Elgg 2.3 → Elgg 3) resolved the sprint
story's two "latest stable" pinning flags from Packagist, queried via
`composer show -a elgg/elgg` inside the app container (2026-10-30):

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core (upgrade target) | 3.3.25 | Latest stable 3.x release in the Packagist version list; `3.3.x-dev` above it is a branch, not a release |
| PHP runtime | 7.4.27 (unchanged) | elgg/elgg 3.3.25 declares `"php": ">=7.2"` (`composer show -a elgg/elgg 3.3.25`); the pinned 7.4.27 runtime satisfies it, so the runtime pin does not move |

At Step 1 completion this pair was the active pin — the core row had moved
from the 2.3.17 ground-zero value, which this section preserves as the
resolution record; Story S1.10 Step 1 subsequently moved the active pin to
4.3.10 (section above). The MariaDB, cron, and phpMyAdmin pins are untouched
by this step. The target was declared in `src/composer.json`
(`"elgg/elgg": "3.3.25"`); the runtime declarations (Dockerfile
`PHP_VERSION` default, Compose build arg, CI image) stayed at 7.4 and needed
no edit.

## Base-Image Digest Pins

Digest pins for the app image build chain (`docker/app/Dockerfile`),
resolved 2026-10-14 (composer:2) and 2026-11-17 (runtime webserver base,
PHP 8.2 line) with `docker buildx imagetools inspect <name:tag>`:

| Base image | Digest (sha256) | Enforcement |
| --- | --- | --- |
| `composer:2` | `af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac` | Hard-pinned inline in the Dockerfile (`FROM composer:2@sha256:<digest>`); the stage only contributes the Composer phar and is version-agnostic, so a hard pin is safe |
| Runtime webserver base (tag per Pinned Versions above) | `a98dbd585dcfb259bb8933f0de6dd13a07db872c1ba33e3a2426403b7dbc6350` | The Dockerfile FROM stays tag-pinned so `PHP_VERSION` overrides keep working — in a `name:tag@digest` reference the digest wins over the tag — and the CI `image-build` job verifies the pulled tag against this digest, failing the pipeline on upstream tag movement |

Re-resolve both digests after an intentional base refresh and update the
Dockerfile pin and the CI workflow together.

## Cross-Check Notes

- Historical note (resolved in S1.1 Step 3): the repository-root
  `composer.json` had been overwritten in a previous session with Elgg 7
  requirements and was corrected back to the pinned baseline; it is now the
  authoritative pin declaration (`"elgg/elgg": "5.1.12"`, Story S1.11).
- Manual verification for this pin: confirm the `"version"` field in the
  installed core's `vendor/elgg/elgg/composer.json` reads `5.1.12` (inside
  the app container — `src/vendor/` is tracked, re-tracked in S1.9 Step 8
  as the baked-image pin; elgg 4 removed the `version.php` the 2.3/3.x
  cores carried), and cross-check against the version reported by the
  running site's administration area.

## Fresh-Checkout Verification

Story S1.1 Step 5 executed `docs/baseline/fresh_checkout.md` from a clean
checkout and confirmed the baseline builds and runs (acceptance criterion:
baseline builds and runs from a fresh checkout).

| Item | Result |
| :--- | :--- |
| Verification date | 2026-09-18 |
| Commit verified | `951ef51` (the commit adding `docs/baseline/fresh_checkout.md`) |
| Site front page | Loaded at the site URL |
| Admin login and `/admin` | Logged in; administration area reachable and functional |
| `php -v` | PHP 7.4.27 (cli), built Jan 26 2022 — matches the pinned PHP runtime |
| Deviations / findings | None |

Remaining manual steps are listed at the bottom of
`docs/baseline/fresh_checkout.md` (web installer, OS hosts entry, optional
host-side Composer check, admin credential recording).

## Installation and Configuration (Story S1.3)

The pinned pair (Elgg 2.3.17 / PHP 7.4.27, see Pinned Versions above) is
installed and configured inside the Compose stack;
`docs/baseline/fresh_checkout.md` reproduces the procedure end to end. The
Step 5 migration-readiness dump-restore check is recorded in the following
section.

| Item | Result |
| :--- | :--- |
| Verification date | 2026-09-26 |
| `settings.php` | `src/elgg-config/settings.php` injects every environment-specific setting via `getenv()` — `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_HOST`, `ELGG_DB_PREFIX`, `WWW_DATA`, `WWW_CACHE`, `ELGG_TIMEZONE` — with no hard-coded environment-specific values; the committed `.env.example` documents every variable with placeholders only |
| Data directory | `WWW_DATA` (default `/var/www/dataroot`) on the persistent `fs-dataroot` named volume, pre-created in the app image with `www-data` ownership; files survive stack restarts and rebuilds |
| Cache directory | `WWW_CACHE` (default `/var/www/cacheroot`) on the persistent `fs-cacheroot` named volume |
| Install method | Committed pre-flight checks (`src/bin/install_preflight.sh`), then the Elgg 2.3 web installer (Elgg 2.3 has no CLI installer) against the `dbm` service (MariaDB 10.11.18) |
| Schema | Installs cleanly from a fresh database and empty data directory; no schema errors |
| Admin login | Site front page loads; the admin account logs in and reaches the `/admin` dashboard |
| Site URL | Stored on the site entity in the database at install time (from `APP_HOST`/`APP_HOST_PORT`); `$CONFIG->wwwroot` is overwritten from the database on every boot, so it is not a `settings.php` setting |

## Production Dump Restore Verification

Story S1.3 Step 5 executed the migration-readiness check: the production
database dump (exported via phpMyAdmin, gzip-compressed) restored into a
dedicated `elgg_restore` database on the containerized MariaDB service
(`dbm`, pinned version per Pinned Versions above). The fresh Elgg install
database was not touched.

| Item | Result |
| :--- | :--- |
| Verification date | 2026-09-25 |
| Dump source | `spirihf6_elgg114` via phpMyAdmin export, gzip-compressed (~110 MB); kept in a git-ignored local path, never committed |
| Restore target | `elgg_restore` database on the `dbm` service |
| Import method | dump piped into `mariadb elgg_restore` with its `CREATE DATABASE`/`USE` redirect lines stripped |
| Client exit | Clean, no errors |

### Verification Figures vs. S1.1 Inventory

| Check | Inventory (2026-09-18) | Restored dump (2026-09-25) | Result |
| :--- | ---: | ---: | :--- |
| Table count | not baselined | 36 | matches the dump's 36 `CREATE TABLE` statements |
| Schema size | 1121.1 MB | 1255.9 MB | drift, see note below |
| Datalists `version` | 2016092300 | 2016092300 | exact match |
| Datalists `installed` | 1483761119 | 1483761119 | exact match |
| Site row | GUID 1, SpiritualFamily.Net, `https://spiritualfamily.net/` | identical | exact match |
| object entities | 87152 | 87217 | +65, expected live drift |
| user entities | 1136 | 1136 | exact match |
| group entities | 289 | 289 | exact match |
| site entities | 1 | 1 | exact match |

Drift note: the inventory was captured 2026-09-18 and the dump exported
2026-09-25; live production grew in between (+65 objects, +134.8 MB reported
size). Every exact-match check passes and content counts sit at or above the
inventory, as expected for a live site.

### Findings

- The phpMyAdmin dump embeds `CREATE DATABASE IF NOT EXISTS spirihf6_elgg114`
  and `USE spirihf6_elgg114`; a naive import self-targets that database
  instead of the chosen restore target. The first import attempt therefore
  landed complete (all 36 tables) in `spirihf6_elgg114`; that database was
  dropped and the dump re-imported with those lines stripped. Restore
  procedures must always strip or override these lines.
- The `dbm` data volume had been initialized with a root password differing
  from the current `.env` value (the image applies `MYSQL_ROOT_PASSWORD` only
  on first-volume initialization). Root was reset to the `.env` value via a
  temporary `--skip-grant-tables` container; authentication then verified
  against `MYSQL_ROOT_PASSWORD`.

## Production Environment Injection (Story S1.6 Step 3)

Story S1.6 Step 3 supplies every production secret exclusively through the
deployment platform's environment variable settings. The committed
`.env.example` remains the authoritative variable inventory: the platform
settings must account for every variable it documents, while the repository
keeps only placeholder values and documented non-secret defaults. The table
below records the per-variable treatment for the production settings; the
`pma` variables are development-only and stay out of the production stack
(Step 1).

| Variable(s) | Treatment in the platform settings |
| :--- | :--- |
| `PHP_VERSION` | Plain setting — pinned to `8.2` (build arg and image tag; see Pinned Versions; moved 7.4 → 8.2 at Story S1.11 Step 9) |
| `APP_CONTAINER_NAME`, `MYSQL_CONTAINER_NAME`, `CRON_CONTAINER_NAME` | Plain settings — operator-chosen container names |
| `APP_HOST` | Plain setting — the production domain; also feeds the cron service's `HOST` and, in Step 4, entry routing |
| `APP_HOST_PORT` | Plain setting — as the platform's entry routing requires (finalized in Step 4) |
| `WWW_DATA`, `WWW_CACHE` | Plain settings — must equal the volume mount targets `/var/www/dataroot` and `/var/www/cacheroot` |
| `MYSQL_HOST` | Plain setting — `dbm` |
| `ELGG_TIMEZONE` | Plain setting — `UTC` |
| `MYSQL_DATABASE`, `ELGG_DB_PREFIX` | Platform settings — credential material supplied only via the platform (the production database name already appears in the dump-restore record above) |
| `MYSQL_ROOT_PASSWORD`, `MYSQL_USER`, `MYSQL_PASSWORD` | Secrets — platform settings only, never committed |
| `APP_CRON_MINUTE` … `APP_CRON_YEARLY` (9 tokens) | Secrets — platform settings only, never committed; left empty for now — Elgg 2.3 does not consume cron tokens, so the empty values are inert; fill with real random strings once the upgrade reaches the latest Elgg version (S1.7 verifies the cron intervals end-to-end) |
| `MAIL_SMTP_HOST`, `MAIL_SMTP_PORT` | Platform settings — accounted for now, left empty; the production relay override is Step 5. With them empty the Compose interpolation defaults apply in the app container (`${MAIL_SMTP_HOST:-host.docker.internal}`, port `1025` — the development capture endpoint), so outgoing mail fails until Step 5; expected and harmless while the database is still empty |
| `ELGG_WWWROOT` | Platform setting — `https://spiritualfamily.online/` (Story S1.9 Step 8): pins the site URL; Elgg 3.3 otherwise sniffs it from the request, which behind the TLS-terminating proxy yields `http://` and breaks the upgrade path's RewriteTest self-check and every generated absolute URL. Local dev sets the hosts-mapped hostname with the published port (e.g. `http://v2.sfnapp.local:3010/`); empty = request-sniffing fallback |
| `PMA_HOST`, `PMA_HOST_PORT`, `PMA_CONTAINER_NAME` | Development-only — not set in production (`pma` excluded per Step 1) |
| `COMPOSE_PROJECT_NAME` | Optional |

Guard test: `src/tests/ProductionSecretHygieneTest.php` asserts that `.env`
stays matched by a `.gitignore` pattern (and is never re-included), that every
credential-material variable is documented in `.env.example` with its
`changeme` placeholder, and that no template value deviates from the
placeholder except the documented non-secret defaults — so real secret values
can never enter the repository.

### Step 3 Manual Verification (2026-10-18)

| Check | Result |
| :--- | :--- |
| Coolify environment-variable walk | Passed — every `.env.example` variable accounted for in the platform settings; the four `MYSQL_*` credentials marked as secrets; all values entered unquoted; no `PMA_*` variables present; build config unchanged (Base Directory `/`, Docker Compose Location `compose.yml` only) |
| Deferred values | `APP_CRON_*` tokens and `MAIL_SMTP_HOST`/`MAIL_SMTP_PORT` present but intentionally empty (recorded in the table above) |
| Guard test | Full suite green, including `ProductionSecretHygieneTest` |
| Repository value scan | No production secret committed. The scan flagged `MYSQL_USER`, `MYSQL_PASSWORD`, and `ELGG_DB_PREFIX`; classified as false positives — the local `.env` holds generic dev values for these variables, and the hits are substring matches inside tracked prose/vendor text (`.agent/` tooling docs for the first two; Elgg core's default `elgg_` prefix throughout `src/vendor/` for the third). `MYSQL_ROOT_PASSWORD` scanned clean. Production values exist only in the Coolify settings and were never typed into a tracked file |

## Production Deployment Version Cross-Check (Story S1.6 Step 2)

The Coolify-deployed production stack was cross-checked against the pins
above (manual verification: inspect each service's version reference in the
platform, then confirm the running versions).

| Check | Result |
| :--- | :--- |
| Verification date | 2026-10-20 |
| app image | `sfn/elgg_webserver:7.4-composer2`, built by Coolify from `PHP_VERSION=7.4` — matches the pin |
| app runtime | `php -v` → 7.4.27 — matches the pinned PHP runtime |
| dbm image | `mariadb:10.11.18` — matches the pin |
| dbm runtime | `mariadbd --version` → `10.11.18-MariaDB-ubu2204` — the suffix is a build variant, not part of the pin (see Pinned Versions) |
| cron image | `sfn/elgg_cron:3.23.3` — matches the pin |
| cron base | `/etc/alpine-release` → 3.23.3 — matches the pin |
| Floating tags | None — the deployed definition's three image references are the pinned tags; no `latest`-style or major-only tags |

Coolify container names carry a `-<uuid>-<build-timestamp>` suffix (e.g.
`app-obil4cynrvnh20c89kwszntz-20261020T165514`); the other containers on the
host (coolify-*, phpmyadmin, umami, filebrowser) are separate instance-level
resources, not part of this application's deployed definition.

## Production Database Migration (Story S1.6 Step 7)

The production dump was restored into the deployed dbm service's production
database following `docs/deployment/deploy_runbook.md` (Step 4), after the
Step 1 backup and Step 2 restorability check. Figures compared against the
baseline inventory (2026-09-18) and the dump-restore record (2026-09-25).

| Check | Baseline | Migrated dbm | Result |
| :--- | ---: | ---: | :--- |
| Table count | 36 | 36 | match |
| Datalists `version` | 2016092300 | 2016092300 | exact match |
| Datalists `installed` | 1483761119 | 1483761119 | exact match |
| Site row | GUID 1, SpiritualFamily.Net, `https://spiritualfamily.net/` | identical | exact match |
| object entities | 87217 (2026-09-25 dump) | 87217 | exact match |
| user entities | 1136 | 1136 | exact match |
| group entities | 289 | 289 | exact match |
| site entities | 1 | 1 | exact match |

Production table prefix: `elggjr_` (the `ELGG_DB_PREFIX` platform setting);
the entity counts come from the `elggjr_entities` base table. The dump's
`system_log` archive tables (`elggjr_system_log_<timestamp>`) are part of
the 36-table count and restore as-is.

## Production Email (Story S1.6 Step 5)

Outgoing production mail is delivered through the NamesPro shared hosting's
mail cluster (StackCP, the 20i platform — hostnames under `stackmail.com`),
submitted authenticated from the app container. The domain is registered at
Namecheap with authoritative DNS there; the apex A record stays on the VPS
(website via Coolify) and only the mail records point at the cluster. Full
procedure: `docs/deployment/coolify.md` → Production email.

| Item | Result |
| :--- | :--- |
| Verification date | 2026-10-20 |
| Relay | `smtp.stackmail.com:587`, STARTTLS, authenticated as `noreply@spiritualfamily.online` (mailbox password only in the Coolify secret settings) |
| DNS at Namecheap | MX `10 mx.stackmail.com.`; SPF TXT `v=spf1 include:spf.stackmail.com a mx -all`; DKIM TXT `s1._domainkey` (selector `s1`, service type `*`, non-strict) — all verified via `dig` |
| Coolify settings | `MAIL_SMTP_HOST=smtp.stackmail.com`, `MAIL_SMTP_PORT=587`, `MAIL_SMTP_AUTH=on`, `MAIL_SMTP_USER=noreply@spiritualfamily.online`, `MAIL_SMTP_PASSWORD` (secret) — confirmed inside the running container via `printenv` |
| TLS pre-check | `openssl s_client -connect smtp.stackmail.com:587 -starttls smtp -brief` → `Verification: OK` (`CN=*.stackmail.com`) |
| End-to-end trigger | `mail()` with the `-f` envelope-from delivered to a Gmail inbox (2026-10-20); no NDRs |
| Dev regression | Local MailHog capture received the same trigger through the dev defaults after the wiring fix |

### Silent-loss incident and fix (2026-10-20)

The first triggers (2026-10-19/20) returned `bool(true)` but never arrived —
two stacked causes, both repo-side:

1. Production ran a stale app image built before the Step 5 wrapper commits
   (`sendmail_path` still the stock binary, wrapper absent).
2. After the redeploy, the base image's `conf.d/sendmail.ini` shadowed the
   repo's `mail.ini`: PHP loads conf.d alphabetically and the last value
   wins (`m` < `s`), so `mail()` kept handing messages to the stock
   sendmail's local queue, from which nothing drains.

Fix: the app image removes `conf.d/sendmail.ini` after the ini COPY
(`docker/app/Dockerfile`, commit `3cd8189`). Verified: `sendmail_path` →
`/usr/local/bin/msmtp-sendmail -t` in the rebuilt production container, and
delivery confirmed end-to-end. Follow-up: publish the DMARC record via the
StackCP wizard (`_dmarc` TXT, `v=DMARC1; p=none;`) — monitor-only, it
completes the SPF/DKIM/DMARC triad.

## Production Re-Deploy Reproducibility (Story S1.6 Step 8)

One clean re-deploy performed per `docs/deployment/deploy_runbook.md`
(Step 6): the Coolify application resource was deleted and re-created
purely from the repository's `compose.yml` and the recorded platform
settings, then deployed. The re-created application's first start happened
before the volume re-attach (premature deploy): it initialized fresh empty
volumes and was stopped, and the migrated data was re-attached per the
runbook's premature-deploy recovery — the fresh volumes deleted, the
originals copied into new-prefix volumes with a helper container (file
counts and byte totals verified identical on all three). The old-prefix
volumes stayed in place as the fallback until this verification passed.
The migrated production data was never touched.

**Intentional change (not drift):** the production domain moved from
`spiritualfamily.net` to `spiritualfamily.online` at this re-deploy —
`APP_HOST` and the Coolify Domains entry now carry
`spiritualfamily.online`, and the site entity's stored URL was updated to
`https://spiritualfamily.online/` (applied ahead of the tear-down; the
volume copy carried it into the re-deployed stack). Future baseline
comparisons must expect the new URL, not the `https://spiritualfamily.net/`
value recorded in the earlier tables above.

| Check | Prior deployment | Re-deployed stack | Result |
| :--- | :--- | :--- | :--- |
| Verification date | 2026-10-20 | 2026-10-22 | match |
| app image / runtime | `sfn/elgg_webserver:7.4-composer2`, PHP 7.4.27 | `sfn/elgg_webserver:7.4-composer2` (rebuilt by Coolify), `php -v` → 7.4.27 | match |
| dbm image / runtime | `mariadb:10.11.18`, `10.11.18-MariaDB-ubu2204` | `mariadb:10.11.18`, `mariadbd --version` → `10.11.18-MariaDB-ubu2204` | match |
| cron image / base | `sfn/elgg_cron:3.23.3`, alpine 3.23.3 | `sfn/elgg_cron:3.23.3`, `/etc/alpine-release` → 3.23.3 | match |
| Floating tags | None | None — the same three pinned image references | match |
| Environment settings | per Step 3 walk | re-created from the recorded settings; `MYSQL_*` credentials proven by the successful boot (app connects to dbm, migrated data served) | match |
| Table count / datalists | 36 / `2016092300`, `1483761119` | 36 / `2016092300`, `1483761119` | exact match |
| Entity counts (object/user/group/site) | 87217 / 1136 / 289 / 1 | 87217 / 1136 / 289 / 1 | exact match |
| Site row | GUID 1, URL `https://spiritualfamily.online/` (updated) | GUID 1, SpiritualFamily.Net, `https://spiritualfamily.online/` | exact match |
| HTTPS | valid certificate, no warnings | valid certificate at `https://spiritualfamily.online/`, no warnings | match |

## Production Upgrade (Story S1.9 Step 8)

The first sequential hop (Elgg 2.3.17 → 3.3.25) executed on production per
`docs/deployment/deploy_runbook.md` (S1.9 record): backup → deploy → data
sync → upgrade → verify. The Step 7 pre-upgrade dump (restorability verified
2026-11-04) is the rollback point.

| Check | Result |
| :--- | :--- |
| Verification date | 2026-11-06 (upgrade completed 2026-11-05 ~23:30 UTC; deploys 2026-11-04/05) |
| Deployed commit | `b84b6e1` — converged over `09ed6dec` → `afe8b45d` → `1cf33e85` → `b84b6e1` (per-commit causes in the runbook Run log) |
| Site URL pin | `ELGG_WWWROOT=https://spiritualfamily.online/` (Coolify env) — required behind the TLS-terminating proxy: unset, Elgg 3.3 sniffs the URL from the request (`http://`), failing the upgrade path's RewriteTest self-check and every generated absolute URL |
| Interrupted first pass | the 2026-11-04 image baked without `vendor/` (untracked since the S1.9 composer-install pivot the Dockerfile never implemented); the migration phase completed on the reduced boot, then the psr/cache fatal killed the completion phase. Recovered via clean Step 7-dump restore + full re-run; the intermediate state was forensically dumped first (`sfn-elgg-broken-20261105T230009.sql.gz`) |
| Charset remediation | `AlterDatabaseToMultiByteCharset` failed on MariaDB 10.11 (`Unknown system variable 'innodb_large_prefix'`), as in the local run; the recorded manual SQL (scratch scripts of commit `8a5e563`, extracted from git history, deleted after use) converted the 16 target tables — post-check: all 16 `utf8mb4_general_ci`; the vendor upgrade then resolved via `shouldBeSkipped()` |
| Table count | 31 (38 restored − 8 dropped by the migration chain + `elggjr_migrations`) |
| Config | `installed` = `1483761119` (unchanged); `lastcache` present — the completion-phase marker absent in the interrupted state |
| Entity counts (object/user/group/site) | 86990 / 1142 / 288 / 1 (post bot-cleanup; local completed copy: 86981 / 1142 / 288 / 1) |
| Delta accounting vs local | +7 `elgg_upgrade` bookkeeping entities (production's completion session registered 14 records vs local's 7; the 8 titled 2.x-era records are identical in both); +2 dump-vintage content objects (`file` 96985, `hjwall` 96986 — created on the old live site 2026-11-03 22:51 UTC, after local's earlier Step 4 dump export, before the Step 7 dump); async conversions applied (61 `discussion_reply` → `comment`, 450 `page_top` → `page` — net-zero); a bot registration (user `djbi` + 8 widgets, 23:47 UTC in the open window) deleted via the admin UI, cascade verified |
| Async upgrades | pending list empty, no errors; plugin entities 102, widget entities 12604, `elgg_upgrade` records 22 (8 dump-carried + 14 session-registered) |
| Version readout | admin area reports v3.3.25 |
| Post-upgrade verification | admin login + dashboard ✓; representative core pages render ✓; test email delivered through the production relay ✓; pins unchanged (PHP 7.4.27 / MariaDB 10.11.18 / alpine 3.23.3) ✓; no CRITICAL or PHP-fatal lines in the app log across the upgrade + browsing window ✓ |

## Production Upgrade (Story S1.11 Step 9)

The third sequential hop (Elgg 4.3.10 → 5.1.12) executed on production per
`docs/deployment/deploy_runbook.md` (S1.11 record) on 2026-11-17/18 — the
first runtime move: PHP 7.4.27 → 8.2.21 (Coolify `PHP_VERSION` 8.2). The
Step 8 pre-upgrade dump (restorability verified 2026-11-17) is the rollback
point. Full narrative, blocker remediations, and the S1.12 expectations live
in the runbook's S1.11 record; the headline figures:

| Check | Result |
| :--- | :--- |
| Verification date | 2026-11-18 (deploy 2026-11-17 20:06 UTC; Move Files completed 2026-11-18 ~01:40; mail fix + final verification 2026-11-18) |
| Deployed commit | `87b34fe9` — converged over `0e068977` → `ddcd7bbe` → `87b34fe9` (Move Files memory fix; per-commit causes in the runbook Run log) |
| Runtime | PHP 8.2.21 in the app container (`php -r 'echo PHP_VERSION;'`); app image `sfn/elgg_webserver:8.2-composer2` |
| Move Files | 3403/3403 after the `limits.ini` raise (128M → 1024M; 0 timeout fatals at 120s); 10 zero-byte masters from the interrupted runs deleted + regenerated via scratch script (ignore access) |
| Schema | 31 tables (32 − `elggjr_private_settings`, migration `20220825064811`); `elggjr_migrations` 54 = 54 shipped files |
| Entity counts (object/user/group/site) | 87476 / 1202 / 288 / 1 — drift vs the S1.9-era figures classified as expected (stale 2026-11-04 snapshot + public reachability; S1.13 fresh restore replaces all of it) |
| Site email | `site@spiritualfamily.net` → `noreply@spiritualfamily.online` (site entity metadata, guid 1) — deliberate change, same class as the S1.6 site-URL change; relay 550 rejections resolved, Elgg-native reset email verified |
| Embed storm | 696 ELGG.ERROR lines, all `embed` guid 11, bounded 20:09–20:17 UTC 2026-11-17; end state absent on the redeploy boot |
| Post-upgrade verification | admin login + core pages render ✓; file thumbnails + download button ✓; 0 fatals/criticals after the mail fix ✓; pins unchanged (MariaDB 10.11.18 / alpine 3.23.3) ✓ |
| Known orphans | old-site uploads after ~2026-10-21 lack file data on the VPS (e.g. guid 96926) — **S1.13 must include a fresh dataroot transfer from the old host** |
