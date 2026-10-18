# Core › Backup and Recovery › Instance Backup

Source: coollabsio/coolify-docs `content/docs/core/backup-and-recovery/instance-backup.mdx` @ main `3b7c416`.

## Scope

- An instance backup saves Coolify's own data — projects, resources, settings, deployment history. It does NOT back up data stored inside your applications, databases, or volumes.
- Coolify Cloud: the Coolify team manages instance backups; you remain responsible for backing up the applications and databases deployed to your connected servers.
- Self-hosted: open **Settings** in the left sidebar, then select **Backup**.

## First-time setup

- If Coolify says instance backups are disabled, validate the server that runs Coolify: **Open server settings** > **Validate connection** > open **Settings > Backup** again.
- The first time you open **Backup**, select **Configure backup** — Coolify creates the backup configuration and turns on a daily backup schedule.

## Before you back up

- Choose where you will keep a copy of the backup, such as another server or your computer — do not keep the only copy on the Coolify server.
- Save the instance `APP_KEY`: Coolify uses it to encrypt secrets stored in its database, and a database backup does not include this file.

## Manual backup — dashboard

- Under **Backup schedule**, select **Back up now**; a new entry appears under **Executions** and moves from **In Progress** to **Success** (or **Failed**, with a message).
- Select **Download** while **Local Storage** is available to save a copy to your computer.

## Manual backup — terminal

- Run on the server that hosts Coolify to create the backup file:

```sh
mkdir -p /data/backups
docker exec coolify-db \
  pg_dump --format=custom --no-acl --no-owner \
  --username=coolify coolify \
  > /data/backups/coolify-db-manual-backup.dmp
```

- Confirm the file exists and is not empty, then copy it to another computer or server so it is not lost if the Coolify server fails:

```sh
test -s /data/backups/coolify-db-manual-backup.dmp \
  && ls -lh /data/backups/coolify-db-manual-backup.dmp
```

## Save the instance encryption key

- On the server that hosts Coolify, display the key:

```sh
grep '^APP_KEY=' /data/coolify/source/.env
```

- Copy the complete `APP_KEY=...` line into a password manager or another secure place outside the Coolify server; treat it like a password and do not publish or share it.
- S3 instance backups include only the database backup file — save this key separately.

## Recurring schedule

- The default **Frequency** is `0 0 * * *` — one backup each day at midnight in the instance timezone.
- **Enable backup** / **Disable backup** turns scheduled runs on or off (saves immediately); **Back up now** starts a backup without waiting for the next scheduled run — the database must be running.
- **Frequency**: a valid cron expression or a preset (`every_minute`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`).
- **Timezone**: read-only — the instance timezone.
- **Timeout**: maximum backup runtime in seconds; accepted range `60`–`36000`, default `3600`. Exceeding it marks that execution failed.

## Retention

- **Retention > Local backups** — and, when S3 is enabled, **Retention > S3 backups** — each accept three independent limits: **Backups to keep** (`0` or a positive integer), **Days to keep** (`0` or a positive integer), **Maximum storage (GB)** (`0` or a positive number, decimals accepted).
- A value of `0` makes that limit unlimited. When any configured limit is reached, Coolify removes the oldest successful backups.

## S3 copies

- Under **S3 storage**, select a validated **S3 storage** target (add and validate one under **Storages** first) > **Enable S3** > **Save changes**.
- **Local copy**: **Keep local backup** retains both copies; **Delete after S3 upload** makes S3 the sole backup location — Coolify deletes the local file after a successful upload.
- If an S3 upload fails, Coolify keeps the local file and marks the execution **Success (S3 Warning)**.
- The dashboard cannot download a remote-only file — download the backup from your S3 storage provider instead.

## Verify and manage

- **Executions** shows status, database name, size, location, and storage availability for each backup; a usable backup shows **Success** with at least one storage location available.
- Local instance backups are stored under `/data/coolify/backups/coolify/coolify-db-<server-ip>/` — use the exact **Location** shown in the execution to find a specific file.
- Actions: **Download** (saves the local backup to your computer), **Delete** (removes the local backup and can also remove its S3 copy when one exists), **Clean failed backups** (removes failed execution records), **Clean deleted entries** (removes records already marked deleted — it does not delete backup files).
- Periodically confirm the off-server copies are accessible and the saved `APP_KEY` is still available; to test or perform recovery, follow the instance-restore rule.
