# Elgg 2.3 Baseline

Ground-zero version reference for the Spiritual Family Network upgrade journey
(Story S1.1). Every later upgrade step cites these pinned values.

## Pinned Versions

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core | 2.3.17 | `src/vendor/elgg/elgg/composer.json` (`"version": "2.3.17"`) |
| PHP runtime | 7.4.27 | Compose stack app image `sfn/elgg_webserver:7.4-composer2`, built locally from upstream `coldtrick/elgg_webserver:7.4` (see `docker/app/Dockerfile`); confirmed via `docker compose exec app php -v` |
| Database server | MariaDB 10.11.18 | Compose stack `dbm` image `mariadb:10.11.18` (see `compose.yml`); confirmed via `SELECT VERSION()` → `10.11.18-MariaDB-ubu2204` |

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
