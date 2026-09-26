# Elgg 2.3 Baseline

Ground-zero version reference for the Spiritual Family Network upgrade journey
(Story S1.1). Every later upgrade step cites these pinned values.

## Pinned Versions

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core | 2.3.17 | `src/vendor/elgg/elgg/composer.json` (`"version": "2.3.17"`) |
| PHP runtime | 7.4.27 | Compose stack app image `sfn/elgg_webserver:7.4-composer2`, built locally from upstream `coldtrick/elgg_webserver:7.4` (see `docker/app/Dockerfile`); confirmed via `docker compose exec app php -v` |
| Database server | MariaDB 10.11.18 | Compose stack `dbm` image `mariadb:10.11.18` (see `compose.yml`); confirmed via `SELECT VERSION()` → `10.11.18-MariaDB-ubu2204` |
| phpMyAdmin | 5.2.3 | Compose stack `pma` image `phpmyadmin/phpmyadmin:5.2.3` (see `compose.yml`); confirmed via the image's `/var/www/html/package.json` |

This is the single pinned Elgg 2.3 core version; it is the reference value for
all subsequent steps in the upgrade sequence (REQ-FR-PU-5).

The pinned PHP runtime sits directly beneath the core version so future steps
can cite them as a pair. Constraint note: the pinned Elgg 2.3.17 core declares
`"php": ">=5.6"` (see `src/vendor/elgg/elgg/composer.json`); 7.4.27 satisfies
that constraint.

The database server pin (Story S1.2 Step 5) targets production parity:
production reports `10.11.18-MariaDB-log` via phpMyAdmin server info and the
containerized server reports `10.11.18-MariaDB-ubu2204` — the `-log` and
`-ubu2204` suffixes are build variants, not part of the Docker tag. Bump the
`mariadb:` tag deliberately alongside the upgrade journey, never float.

Environment note (2026-09-19): the app image layers Composer 2.x (2.10.3 at
time of recording) onto the upstream PHP 7.4 webserver image via
`docker/app/Dockerfile`, confirmed via `docker compose exec app composer --version`,
so manifest and lock-file work runs inside the orchestration. The web SAPI's
`open_basedir` restriction is unchanged; the Composer CLI wrapper disables it
for the CLI only.

Environment template (Story S1.2 Step 6): every environment-specific setting
is injected via environment variables — Compose interpolation and
`env_file: .env` in `compose.yml`, `getenv()` in
`src/elgg-config/settings.php`. The committed template `.env.example`
documents every required variable with placeholder values and no real
secrets; copy it to `.env` and supply real values locally. `.env` is
git-ignored, so real secrets never enter the repository.

## Cross-Check Notes

- The repository-root `composer.json` was overwritten in a previous session and
  currently declares Elgg 7 requirements; it must be corrected back to this
  pinned baseline in Step 3 of Story S1.1 and is therefore not usable as a
  version source.
- Manual verification for this pin: confirm `$release` in
  `src/vendor/elgg/elgg/version.php` (the vendored Elgg 2.3.17 core root)
  reads `2.3.17`, and cross-check against the version reported by the running
  legacy site's administration area.

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
