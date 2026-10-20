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
- Database dumps stay in a git-ignored local path outside the repository
  (this runbook uses `~/sfn-backups/`) — never committed. The production
  dump is on the order of 110 MB compressed.
- Credentials come from the containers' own environment (injected by the
  platform); no secret is typed on a command line or stored in the
  repository.

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

Native `mariadb-dump` output contains no `CREATE DATABASE`/`USE` redirect
lines, but the phpMyAdmin-exported production dump does (baseline finding):
strip them unconditionally so the procedure is safe for either dump source,
then import:

```bash
docker exec "$DBM" mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e 'CREATE DATABASE backup_verify'
gunzip -c "$(ls -t ~/sfn-backups/sfn-elgg-*.sql.gz | head -n 1)" | grep -vE '^(CREATE DATABASE|USE )' | docker exec -i "$DBM" mariadb -u root -p"$MYSQL_ROOT_PASSWORD" backup_verify
```

Confirm the scratch database holds the full schema — 36 tables, matching
the production dump's 36 `CREATE TABLE` statements:

```bash
docker exec "$DBM" mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = 'backup_verify'"
```

Drop the scratch database:

```bash
docker exec "$DBM" mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e 'DROP DATABASE backup_verify'
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

## Step 4 — Post-deploy verification

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

## Run log

One row per production deploy:

| Date | Commit | Backup file | Restorability check | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 2026-10-20 | initial S1.6 deployment (Steps 1–5) | n/a — pre-runbook | n/a | baseline deployed; production email verified |
