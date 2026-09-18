# Fresh-Checkout Baseline Procedure

Take any clean checkout of this repository to a running, logged-in Elgg 2.3.17
baseline using the committed Compose stack. Version pins live in
`docs/baseline/baseline.md` (Elgg 2.3.17 / PHP 7.4.27). This procedure is the
deliverable of Story S1.1 Step 5; execute it verbatim when verifying.

Notes:

- `src/vendor` (pinned Elgg 2.3.17) and `src/composer.lock` are tracked in git,
  so a fresh clone runs without `composer install`; the Composer step below is
  a consistency check only.
- The committed `src/elgg-config/settings.php` reads every credential from
  environment variables (`MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`,
  `MYSQL_HOST`, `ELGG_DB_PREFIX`, `ELGG_TIMEZONE`), so no settings file editing
  is required at any point.

## Prerequisites

- Docker Engine and Docker Compose v2
- Git and a Web browser
- Free host ports `3010` (app) and `3011` (phpMyAdmin)
- An OS hosts entry mapping `v2.sfnapp.local` to `127.0.0.1` (see the note at
  the bottom of `.env.example`)
- Several GB of free disk for images and named volumes

## Isolating the Run (recommended when a dev stack already exists)

Copy `.env.example` to `.env`, then override:

| Variable | Default | Isolated example |
| :--- | :--- | :--- |
| `COMPOSE_PROJECT_NAME` | commented out | `sfnverify` |
| `APP_CONTAINER_NAME` | `spiritualfamily_app` | `spiritualfamily_app_verify` |
| `MYSQL_CONTAINER_NAME` | `spiritualfamily_dbm` | `spiritualfamily_dbm_verify` |
| `PMA_CONTAINER_NAME` | `spiritualfamily_pma` | `spiritualfamily_pma_verify` |
| `CRON_CONTAINER_NAME` | `spiritualfamily_cron` | `spiritualfamily_cron_verify` |
| `APP_HOST_PORT` | `3010` | `3020` |
| `PMA_HOST_PORT` | `3011` | `3021` |
| `ELGG_WWWROOT` | `http://v2.sfnapp.local:3010/` | `http://v2.sfnapp.local:3020/` |

`ELGG_WWWROOT` must always match `APP_HOST` plus `APP_HOST_PORT`; it becomes the
installed site URL. Named volumes are prefixed by `COMPOSE_PROJECT_NAME`, so an
isolated project automatically gets its own fresh volumes.

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

`.env` is git-ignored; keep the placeholder values, never real secrets. Apply
the isolation overrides above if a dev stack is already running.

### 3. Start the stack

```bash
docker compose up -d
docker compose ps
```

Expected: `app` (`coldtrick/elgg_webserver:7.4`), `dbm` (`mysql:9`), `pma`, and
`cron` all `Up`. The first start pulls images and initializes the empty
`fs-dbmdata` volume; wait for MySQL to settle before the install step.

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

Browse the site URL (default `http://v2.sfnapp.local:3010/`); the Elgg 2.3
installer launches:

1. Requirements check: all green.
2. Database: host `dbm`, user `user`, password `pass`, database `mysqldb`,
   table prefix `sfn_` (the `.env` defaults; settings already read them from
   the environment). Never point a verification install at production.
3. Site settings: site URL prefilled from `ELGG_WWWROOT`; data directory
   `/var/www/dataroot`; create the admin account (record credentials locally,
   never commit them).

Troubleshooting:

- Non-writable dataroot:

  ```bash
  docker compose exec app chown -R www-data:www-data /var/www/dataroot
  ```

  Then retry.
- Database connection or charset errors: record as a finding; the MySQL server
  version is explicitly flagged for pinning in Story S1.2.

### 7. Verify the baseline

- Logged out, the site front page loads at the site URL.
- Log in as the admin created in step 6.
- The administration area (`/admin`) is reachable and its dashboard renders
  without errors.
- Optional: phpMyAdmin on port `3011` (server `dbm`, user `user`, password
  `pass`) lists the `sfn_` tables.

### 8. Tear down

```bash
docker compose down
```

Add `-v` to also remove this verification run's volumes.

## Manual Steps Not Yet Automated

- The Elgg 2.3 web installer itself (no committed CLI installer in this repo)
- The OS hosts entry for `v2.sfnapp.local`
- The host-side Composer consistency check (optional)
- Admin account creation and local credential recording
