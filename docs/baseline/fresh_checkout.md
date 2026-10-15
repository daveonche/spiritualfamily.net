# Fresh-Checkout Baseline Procedure

Take any clean checkout of this repository to a running, logged-in Elgg 2.3.17
baseline using the committed Compose stack. Version pins live in
`docs/baseline/baseline.md` (Elgg 2.3.17 / PHP 7.4.27). This procedure is the
deliverable of Story S1.1 Step 5; execute it verbatim when verifying.

Notes:

- `src/vendor` (pinned Elgg 2.3.17) and `src/composer.lock` are tracked in git,
  so a fresh clone runs without `composer install`; the Composer step below is
  a consistency check only.
- The committed `src/elgg-config/settings.php` reads every environment-specific
  setting from environment variables (`MYSQL_USER`, `MYSQL_PASSWORD`,
  `MYSQL_DATABASE`, `MYSQL_HOST`, `ELGG_DB_PREFIX`, `WWW_DATA`, `WWW_CACHE`,
  `ELGG_TIMEZONE`), so no settings file editing is required at any point.
- The Elgg data directory (`WWW_DATA`, default `/var/www/dataroot`) is
  provisioned on the persistent `fs-dataroot` named volume and pre-created in
  the app image with `www-data` ownership, so files written there survive
  stack restarts and rebuilds.
- The committed pre-flight script `src/bin/install_preflight.sh` verifies
  everything the installer depends on (required environment variables,
  database reachability and freshness, data/cache directory writability,
  pinned core version) before the install step runs.

## Prerequisites

- Docker Engine and Docker Compose v2
- Git and a Web browser
- Free host port `3000` (app); port `3001` is needed only if you also run
  the dev-only phpMyAdmin (see step 7)
- An OS hosts entry mapping your `APP_HOST` hostname (e.g. `v2.sfnapp.local`)
  to `127.0.0.1` (see the note at the bottom of `.env.example`)
- Several GB of free disk for images and named volumes

## Isolating the Run (recommended when a dev stack already exists)

Copy `.env.example` to `.env`, then override:

| Variable | Default | Isolated example |
| :--- | :--- | :--- |
| `COMPOSE_PROJECT_NAME` | commented out | `sfnverify` |
| `APP_CONTAINER_NAME` | `changeme_app` | `changeme_app_verify` |
| `MYSQL_CONTAINER_NAME` | `changeme_dbm` | `changeme_dbm_verify` |
| `PMA_CONTAINER_NAME` | `changeme_pma` | `changeme_pma_verify` |
| `CRON_CONTAINER_NAME` | `changeme_cron` | `changeme_cron_verify` |
| `APP_HOST` | `changeme.example.test` | `v2.sfnapp.local` |
| `APP_HOST_PORT` | `3000` | `3020` |
| `PMA_HOST_PORT` | `3001` | `3021` |

Set `APP_HOST` to a local hostname of your choice and map it to `127.0.0.1` in
your OS hosts file. The site URL entered during installation (Step 6) must
match `APP_HOST` plus `APP_HOST_PORT`; it becomes the installed site URL. Named
volumes are prefixed by `COMPOSE_PROJECT_NAME`, so an isolated project
automatically gets its own fresh volumes.

## Procedure

### 1. Clean checkout

```bash
git clone <repository-url> sfn-baseline-verify
cd sfn-baseline-verify
git --no-pager rev-parse --short HEAD
```

Record the printed commit hash with the verification result (see
`docs/baseline/baseline.md`).

### 2. Environment file

```bash
cp .env.example .env
```

`.env` is git-ignored. Replace every `changeme` placeholder with local values
(dev-only credentials are fine; never real secrets), set `APP_HOST` per the
isolation note above, and apply the isolation overrides if a dev stack is
already running.

### 3. Start the stack

```bash
docker compose up -d
docker compose ps
```

Expected: `app` (`sfn/elgg_webserver:7.4-composer2`), `dbm` (`mariadb:10.11.18`),
and `cron` all `Up` — the dev-only `pma` service lives in `compose.dev.yml`
and is not part of this stack. The first start builds the app image locally
from `docker/app/Dockerfile` and initializes the empty `fs-dbmdata` volume;
wait for the database to settle before the install step.

### 4. Confirm the pinned runtime pair

```bash
docker compose exec app php -v
```

Expected: PHP 7.4.27 (pinned pair: Elgg 2.3.17 / PHP 7.4.27). Optional
cross-check: `$release` in `src/vendor/elgg/elgg/version.php` reads `2.3.17`.

### 5. Composer consistency check (optional)

From `src/` on the host, if Composer is installed:

```bash
cd src && composer validate && cd ..
```

Nothing is downloaded; a failure here is a manifest/lock inconsistency to
record, not a blocker for the run verification.

### 6. Install Elgg (fresh database only)

First run the committed pre-flight checks from the repository root:

```bash
docker compose exec app /var/www/html/bin/install_preflight.sh
```

Expected: every check passes — required environment variables, database
reachability and freshness (no Elgg tables yet), data and cache directory
writability, and the pinned Elgg 2.3.17 core — and the script points back to
this step. It exits non-zero on the first failed check; fix the reported
issue and re-run before continuing.

Then browse the site URL (default `http://v2.sfnapp.local:3000/`); the Elgg 2.3
installer launches:

1. Requirements check: all green.
2. Database: host `dbm`, plus the `MYSQL_USER`, `MYSQL_PASSWORD`,
   `MYSQL_DATABASE`, and `ELGG_DB_PREFIX` values you set in `.env` (settings
   already read them from the environment). Never point a verification install
   at production.
3. Site settings: site URL — enter the URL you are browsing (e.g.
   `http://v2.sfnapp.local:3000/`); data directory `/var/www/dataroot`; create
   the admin account (record credentials locally, never commit them).

Troubleshooting:

- Non-writable dataroot:

  ```bash
  docker compose exec app chown -R www-data:www-data /var/www/dataroot
  ```

  Then retry.
- Database connection or charset errors: record as a finding; the database
  server is pinned to MariaDB 10.11.18 (see `docs/baseline/baseline.md`).

### 7. Verify the baseline

- Logged out, the site front page loads at the site URL.
- Log in as the admin created in step 6.
- The administration area (`/admin`) is reachable and its dashboard renders
  without errors.
- Optional: the dev-only phpMyAdmin (defined in `compose.dev.yml`, not
  started by this procedure's `docker compose up -d`) — start it with
  `docker compose -f compose.yml -f compose.dev.yml up -d pma`, then browse
  port `3001` (server `dbm`, user `MYSQL_USER` and password `MYSQL_PASSWORD`
  from `.env`) to list the site's tables (prefix per `ELGG_DB_PREFIX`).

### 8. Tear down

```bash
docker compose down
```

Add `-v` to also remove this verification run's volumes.

## Manual Steps Not Yet Automated

- The Elgg 2.3 web installer itself — Elgg 2.3 has no CLI installer
  (`elgg-cli` arrives with Elgg 3), so the browser steps above remain manual;
  the committed `src/bin/install_preflight.sh` automates the pre-flight
  portion
- The OS hosts entry for `v2.sfnapp.local`
- The host-side Composer consistency check (optional)
- Admin account creation and local credential recording
