# Coolify Deployment Guide

Deploy the production stack from the repository's `compose.yml` — the single
source of truth for the app, dbm, and cron services. The development-only
`pma` service lives in `compose.dev.yml`, so Coolify never parses it into
the production deployment.

Steps verified against Coolify v4.4.6; exact menu labels vary between
versions.

## Scope

This guide covers creating the production application in Coolify from the
committed `compose.yml` and starting the app, dbm, and cron services as one
stack. The remaining deployment concerns are handled by their own steps in
`docs/analysis/S1.6-story-steps.md`: version-pin verification (Step 2),
environment/secret injection (Step 3), HTTPS entry routing via Domains
(Step 4), production email (Step 5), the deploy runbook (Step 6), the
database migration (Step 7), and re-deploy reproducibility (Step 8).

## Prerequisites

- A Coolify instance with a connected Git source for this repository
- The committed `compose.yml` at the project root
- The committed build recipes `docker/app/Dockerfile` and
  `docker/cron/Dockerfile` — both images are built from the repository
  root during deployment

## One-time workspace setup

Servers, sources, and projects all live inside a team.

1. **Team** — create or select your team.
2. **Server** — Infrastructure → Servers → + New Server. Choose `localhost`
   when Coolify itself runs on the target host (Docker Engine is already
   present); otherwise add a remote server over SSH. Coolify validates the
   connection.
3. **Source** — Infrastructure → Sources → GitHub App; install it on this
   repository so Coolify can read it.
4. **Project** — Workspace → Projects → + New Project.
5. **Environment** — open the project and create an environment (for
   example `production`). No variable prompt appears here by design:
   creating an environment goes straight to its resource list. Variables
   are added per application after the resource exists (below).

## Create the application

There is no standalone Docker Compose resource type in v4.4.6: create a
Git-repository application, then choose Docker Compose as its build
strategy.

1. In the environment, select **+ New Resource**.
2. On the "Choose a resource" page, select **Git Repository (with GitHub
   App)**.
3. Pick the GitHub App source, this repository, and the branch (e.g.
   `main`).
4. Under **General → Build pipeline**, set:
   - Build strategy: **Docker Compose**
   - Base Directory: `/`
   - Docker Compose Location: `compose.yml`
5. Save and review **Docker Compose Content**: Coolify parses the file and
   lists the services it defines — app, dbm, and cron. `pma` never appears
   (it lives in `compose.dev.yml`).
6. Fill the **Environment Variables** tab (next section), then select
   **Deploy**.

## Environment variables

Coolify extracts some variables from the Compose definition and creates
them automatically; others are invisible to it and must be added by hand.
The committed `.env.example` documents every variable. Enter all values
unquoted — Coolify substitutes them literally, so a quoted value would put
quote characters into the substituted path.

Auto-created by Coolify (fill in the values):

| Variable | Value |
| :--- | :--- |
| `PHP_VERSION` | `7.4` |
| `APP_HOST` | production hostname, no scheme |
| `APP_CRON_MINUTE` … `APP_CRON_YEARLY` (9 tokens) | real random strings — they authenticate the cron URLs |
| `MAIL_SMTP_HOST` / `MAIL_SMTP_PORT` | leave empty until Step 5 (production relay) |

Add manually (mark the four `MYSQL_*` credentials as secrets):

| Variable | Value |
| :--- | :--- |
| `APP_HOST_PORT` | a free port on the server |
| `APP_CONTAINER_NAME` / `MYSQL_CONTAINER_NAME` / `CRON_CONTAINER_NAME` | unique names |
| `MYSQL_ROOT_PASSWORD` / `MYSQL_DATABASE` / `MYSQL_USER` / `MYSQL_PASSWORD` | real secrets |
| `MYSQL_HOST` | `dbm` |
| `ELGG_DB_PREFIX` | production table prefix |
| `ELGG_TIMEZONE` | `UTC` |
| `WWW_DATA` | `/var/www/dataroot` — must match the compose mount target |
| `WWW_CACHE` | `/var/www/cacheroot` — must match |

The full secrets-injection treatment is Step 3 of the story steps.

## Service inventory

| Service | Role | Image / build | In production |
| :--- | :--- | :--- | :--- |
| app | Elgg web server | Built from `docker/app/Dockerfile` (repository-root context) via the `PHP_VERSION` build arg; web root incl. tracked `vendor/` and PHP ini baked in | Yes |
| dbm | MariaDB database | Pinned `mariadb:10.11.18` | Yes |
| cron | Scheduled tasks | Built from `docker/cron/Dockerfile`; committed crontab baked in (`sfn/elgg_cron:3.23.3`) | Yes |

The three production services form one startable stack through the
`depends_on` chain in `compose.yml`: app starts only once dbm passes its
healthcheck, and cron starts after app. All three restart automatically
(`restart: unless-stopped`) on the shared `appnet` network.

## Development-only services

`pma` (phpMyAdmin) is a development-only database administration UI. It is
defined in `compose.dev.yml` rather than `compose.yml`, so it never appears
in the parsed service list and is never deployed to production. Developers
run it by merging the override file:

```bash
docker compose -f compose.yml -f compose.dev.yml up -d
```

## What the deployment does

1. Coolify clones the repository and builds **both** images from the
   repository root: the app image (`docker/app/Dockerfile`, `PHP_VERSION`
   build arg) with the web root — `src/` including the tracked `vendor/` —
   and the PHP ini overrides baked in; the cron image
   (`docker/cron/Dockerfile`) with the committed crontab baked in.
2. The production definition carries **no repository bind mounts**:
   v4.4.6 runs `docker compose up` without the cloned repository present,
   so repo-relative bind sources would resolve to empty directories.
   Everything the containers need is baked into the images — which also
   makes the "Preserve repository during deployment" option unnecessary.
3. Services start in dependency order: dbm first (its compose healthcheck
   must pass), then app, then cron.

The application's "Healthcheck: Not configured" badge is expected:
Git-based Docker Compose applications do not use the application-level
healthcheck page — healthchecks come from the Compose definition itself
(dbm's gates startup).

## Gotchas

- **MariaDB initializes once.** The `MYSQL_*` accounts are created only
  when the `fs-dbmdata` volume is empty — on the first start ever.
  Changing the variables in Coolify afterwards does not update the
  database; the app then fails with `Access denied … (using password:
  YES)`. While the install is still empty, fix it by stopping the stack,
  deleting the `fs-dbmdata` volume, and redeploying. Once production data
  is restored (Step 7), never wipe the volume.
- **Do not run the Elgg web installer in production.** The production
  schema arrives with the Step 7 dump restore; a web-installer schema
  would be a parallel one the restore would have to remove.
- **No `composer install` in the container.** The tracked vendor tree is
  baked into the image (the S1.1 baseline design: a fresh clone runs
  without `composer install`); the container's writable layer is
  ephemeral, and the base image disables `putenv()`, which Composer
  requires.

## Verify

- [ ] The application in Coolify lists exactly the app, dbm, and cron
  services as one stack.
- [ ] `pma` appears nowhere in the production deployment — it is defined
  only in `compose.dev.yml`.
- [ ] The services started in dependency order (app after dbm healthy, cron
  after app).
