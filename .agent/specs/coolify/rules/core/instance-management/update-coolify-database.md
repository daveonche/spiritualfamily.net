# Core › Instance Management › Update Coolify Database

Source: coollabsio/coolify-docs `content/docs/core/instance-management/update-coolify-database.mdx` @ main `3b7c416`.

## Scope

- Coolify stores its projects, resources, settings, and deployment history in an internal PostgreSQL database. The database runs in the `coolify-db` container.
- WARNING: self-hosted instances only — Coolify Cloud users cannot access the internal Coolify database; contact the Coolify team for `500` status code errors on Coolify Cloud.
- A normal Coolify update (see the update rule) does not migrate this database to a newer PostgreSQL major version — major-version changes require the migration script described here.
- This procedure does not apply to PostgreSQL databases deployed as project resources — back up and upgrade those separately.

## Before you update

- Create an instance backup (see the instance-backup rule), copy the backup outside the Coolify server, and confirm that `/data/coolify/source/.env` is saved outside the server — it contains the instance `APP_KEY`.
- Let active deployments finish before starting the migration.
- Choose a PostgreSQL major version newer than the version currently used by `coolify-db`.
- The migration script keeps the previous PostgreSQL Docker volume and creates an on-server database dump — rollback safeguards, but neither replaces the external instance backup.

## Update the database

### Check the migration script

- On the server that runs Coolify, confirm the script exists and is executable:

```sh
test -x /data/coolify/source/upgrade-postgres.sh \
  && echo "PostgreSQL upgrade script is ready"
```

- If the command prints nothing, update Coolify (see the update rule) and check again — the installer and updater download the current migration script but do not run it automatically.

### Check the current PostgreSQL version

- Confirm `coolify-db` is running and print its active PostgreSQL version:

```sh
docker ps --filter name=coolify-db --format '{{.Names}}  {{.Status}}'

docker exec coolify-db sh -lc \
  'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Atc "SHOW server_version;"'
```

- The first command should show `coolify-db` as running; the second prints the current PostgreSQL version.
- The target must be a numeric PostgreSQL major version of `10` or newer and higher than the major version reported for the running `coolify-db` container — the script does not support downgrading by passing an older version number.

### Run the database update

- WARNING: the Coolify dashboard will be unavailable — the script stops the `coolify` application container before creating the database dump and keeps it stopped during the migration. Applications, databases, services, and Coolify Proxy managed by the instance continue running.
- Run the script with the target PostgreSQL major version — this example upgrades to PostgreSQL 18; replace `18` with your target and do not close the shell while the script is running:

```sh
/data/coolify/source/upgrade-postgres.sh 18
```

- The script performs these actions in order:
  1. Detects the active PostgreSQL version, image, Docker volume, and mount path.
  2. Stops the `coolify` application container to prevent writes.
  3. Creates a compressed `pg_dumpall` backup under `/data/coolify/backups/internal-postgres/`.
  4. Creates a new Docker volume and starts a temporary container with the target PostgreSQL version.
  5. Restores the dump into the new volume and checks that the Coolify database responds.
  6. Saves rollback metadata and writes a Docker Compose override for the new image and volume.
  7. Replaces the `coolify-db` container and starts the Coolify stack with the upgraded database.
- If something goes wrong, the script stops as soon as it encounters an error — keep the terminal output and review `/data/coolify/source/postgres-upgrade-<timestamp>.log` for the migration progress and recorded command output.

### Verify the database update

- Run the version check again and confirm the output starts with the target major version:

```sh
docker exec coolify-db sh -lc \
  'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Atc "SHOW server_version;"'
```

- Open the Coolify dashboard and check that: the dashboard loads without a database error; your projects and resources are present; **Servers > localhost** validates successfully; other managed servers still connect.

## Roll back the database update

- If the updated database does not work, run the rollback command:

```sh
/data/coolify/source/upgrade-postgres.sh rollback
```

- Rollback reads `/data/coolify/source/postgres-upgrade-rollback.env`, switches the Coolify stack back to the previously active PostgreSQL image, Docker volume, and mount path, then starts the stack.
- WARNING: rollback restores the version used before the latest update — it requires the metadata file and previous Docker volume to remain on the server, and each successful database update replaces the rollback metadata:

| Database update history | Version restored by rollback |
| --- | --- |
| PostgreSQL 15 to PostgreSQL 18 | PostgreSQL 15 |
| PostgreSQL 15 to PostgreSQL 18 to PostgreSQL 19 | PostgreSQL 18 |

- The rollback does not delete the updated Docker volume — keep it until you have confirmed that the previous database version and Coolify dashboard work; remove unused volumes only after identifying them and confirming they are no longer needed.
- Verify the rollback: run the version check again and confirm the output starts with the version shown in the table, then repeat the dashboard checks (dashboard loads, projects and resources present, **Servers > localhost** validates, other managed servers connect).

## How the update changes Docker Compose

- The migration script does not change `/data/coolify/source/docker-compose.yml` or `/data/coolify/source/docker-compose.prod.yml` — it creates this override instead: `/data/coolify/source/docker-compose.postgres-upgrade.yml`.
- For an upgrade to PostgreSQL 18 using the default volume name, the file contains configuration similar to this:

```yaml
services:
  postgres:
    image: "postgres:18-alpine"
    volumes:
      - coolify-db:/var/lib/postgresql
volumes:
  coolify-db:
    name: "coolify-db-pg18"
    external: true
```

- PostgreSQL 18 and newer use `/var/lib/postgresql` as the container mount path; earlier versions use `/var/lib/postgresql/data` — the script selects the correct path for the target version.
- Future Coolify updates include `docker-compose.postgres-upgrade.yml` when the file exists, so the upgraded PostgreSQL image and volume remain active. If `/data/coolify/source/docker-compose.custom.yml` also exists, the PostgreSQL upgrade override is loaded after it and takes precedence for conflicting `postgres` service settings.
- To inspect the complete Compose configuration in the same order Coolify loads it, run from a Bash shell on the Coolify server:

```bash
cd /data/coolify/source

compose_files=(-f docker-compose.yml -f docker-compose.prod.yml)
[[ -f docker-compose.custom.yml ]] \
  && compose_files+=(-f docker-compose.custom.yml)
[[ -f docker-compose.postgres-upgrade.yml ]] \
  && compose_files+=(-f docker-compose.postgres-upgrade.yml)

docker compose --env-file .env "${compose_files[@]}" config
```

## Files created by the update

| File | Purpose |
| --- | --- |
| `/data/coolify/source/docker-compose.postgres-upgrade.yml` | Keeps the upgraded PostgreSQL image, Docker volume, and mount path active during normal Coolify updates. |
| `/data/coolify/source/postgres-upgrade-rollback.env` | Records the previously active database image, Docker volume, mount path, and override state for rollback. |
| `/data/coolify/source/postgres-upgrade-<timestamp>.log` | Records the migration or rollback actions and errors. |
| `/data/coolify/backups/internal-postgres/postgres-upgrade-<timestamp>.sql.gz` | Contains the compressed `pg_dumpall` backup created before the database is migrated. |
