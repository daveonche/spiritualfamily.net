# Core › Backup and Recovery › Instance Restore

Source: coollabsio/coolify-docs `content/docs/core/backup-and-recovery/instance-restore.mdx` @ main `3b7c416`.

## Scope

- Restoring Coolify replaces the instance database with a backup — projects, resources, settings, saved credentials, and deployment history. It does not restore data stored inside your applications, databases, services, or volumes.
- Self-hosted only; the Coolify team manages Coolify Cloud instance recovery.

## Before you restore

- A Coolify database backup file ending in `.dmp` (if stored in S3, download it from the provider first).
- The `APP_KEY` value saved when following the instance-backup rule.
- SSH access to the server where you will restore; the Coolify version shown in the dashboard when the backup was created, if saved.
- When moving to another machine: access to `/data/coolify/ssh/keys/` and `~/.ssh/authorized_keys` on the original machine, or a separate backup of those files.
- DANGER: the restore command removes the current Coolify database objects before loading the backup — do not continue unless the backup file and saved `APP_KEY` belong to the instance you want to recover.

## Prepare the restore target

- Same server: confirm `coolify-db` is running and place the backup somewhere on that machine, such as `/data/backups/coolify-db-backup.dmp`:

```sh
docker ps --filter name=coolify-db --format '{{.Names}}  {{.Status}}'
```

- Replacement server: install Coolify first, using the version that was installed when the backup was created (`curl -fsSL https://cdn.coollabs.io/coolify/install.sh | sudo bash -s <version>`; omit `-s <version>` for the latest stable if the previous version is unknown), confirm the fresh dashboard opens, then copy the database backup to the replacement server.

## Restore the instance

- Check the backup file:

```sh
export BACKUP_PATH=/data/backups/coolify-db-backup.dmp
test -s "$BACKUP_PATH" && ls -lh "$BACKUP_PATH"
```

- Stop the containers that can use the instance database, but keep `coolify-db` running — the dashboard is unavailable during the restore; deployed resources and Coolify Proxy keep running. Releases from before the Reverb migration also run `coolify-realtime`:

```sh
docker stop coolify coolify-redis
```

- Set the saved `APP_KEY`: open `/data/coolify/source/.env` and replace the `APP_KEY=` value with the saved one. On the original machine, leave the line unchanged if it already matches. On a replacement server, keep the rest of the new installation's `.env` — values such as `DB_PASSWORD` belong to the new installation and must continue to match its running containers.
- Restore while `coolify-db` is the only Coolify container using the database:

```sh
docker exec -i coolify-db pg_restore \
  --clean \
  --if-exists \
  --exit-on-error \
  --no-acl \
  --no-owner \
  --username=coolify \
  --dbname=coolify \
  < "$BACKUP_PATH"
```

- `--exit-on-error` stops the restore at the first error so a failed restore is not mistaken for success.
- SSH keys: same server with the original files still under `/data/coolify/ssh/keys/` → skip. Files removed → restore them from a separate backup before continuing. Replacement server: `rm -f /data/coolify/ssh/keys/*`, copy the original directory's contents into the same path, and make sure the corresponding public key from the original server is present in `~/.ssh/authorized_keys` on the replacement.
- Start Coolify by running the installer again — include the version installed before restoring (`bash -s <version>`), or omit it for the latest stable. The installer starts the Coolify containers and applies any required database migrations; wait for it to finish before opening the dashboard.

## Verify the restore

- The dashboard opens without an encryption error; projects, environments, and resources are present; **Servers > localhost** validates successfully; other managed servers connect; recent deployments and instance settings appear as expected.
- Application volume and database data are not part of an instance backup — restore those resources from their own backups when needed.

## Troubleshoot

- Encryption or `Invalid MAC` error → the active `APP_KEY` does not match the key used to encrypt the restored database. Stop `coolify`, confirm `/data/coolify/source/.env` contains the saved `APP_KEY` value, then run the installer again. Current Coolify reads `APP_KEY`; do not add `APP_PREVIOUS_KEYS`.
- `pg_restore` stops with an error → keep the control-plane containers stopped and read the first error in the output; confirm the file is a non-empty `.dmp` created by `pg_dump --format=custom`, then rerun after correcting the problem.
- Replacement server fails validation → confirm the original SSH key files are under `/data/coolify/ssh/keys/` and the matching public key from the original server is present in `~/.ssh/authorized_keys` on the replacement.
- Another managed server is unreachable → open that server's **Private Key** page, confirm the expected key is selected, use **Copy public key**, and make sure that public key still exists in the remote user's `~/.ssh/authorized_keys`.
- Application data is missing → an instance backup restores Coolify's own database only; restore each application's volumes and each database resource from their separate backups.
