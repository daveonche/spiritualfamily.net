# Core › Instance Management › Environment Variables

Source: coollabsio/coolify-docs `content/docs/core/instance-management/environment-variables.mdx` @ main `3b7c416`.

## Scope

- Self-hosted Coolify reads its instance environment from `/data/coolify/source/.env` — use it for settings such as PHP memory, workers, and scheduled job dispatch that are not exposed in the dashboard.
- These settings affect the Coolify instance. To configure a deployed application, use that application's environment variables and image configuration instead.

## PHP memory and worker settings

- The standard production Compose configuration supplies these defaults when the corresponding variable is unset:

| Variable | Coolify default | Purpose |
| :--- | :--- | :--- |
| `PHP_MEMORY_LIMIT` | `256M` | PHP memory limit for an individual script. |
| `PHP_FPM_PM_CONTROL` | `dynamic` | PHP-FPM process-manager mode. The image supports `dynamic`, `ondemand`, and `static`. |
| `PHP_FPM_PM_START_SERVERS` | `1` | Number of workers started when using `dynamic` mode. |
| `PHP_FPM_PM_MIN_SPARE_SERVERS` | `1` | Minimum idle workers maintained in `dynamic` mode. |
| `PHP_FPM_PM_MAX_SPARE_SERVERS` | `10` | Maximum idle workers maintained in `dynamic` mode. |

- `PHP_MEMORY_LIMIT` is separate from Docker's container memory limit — raising it does not allocate more RAM to the container or server; PHP workers and other processes still share the available memory.
- Keep worker settings consistent with the selected process-manager mode and the server's resources. Additional image-supported settings are listed in the Server Side Up PHP environment reference (`https://serversideup.net/open-source/docker-php/docs/reference/environment-variable-specification`).
- Coolify's own configuration can override image defaults — verify the effective value after making changes.

## Scheduled job dispatch

- `SCHEDULED_JOBS_DISPATCH_MODE` controls how the scheduler checks every minute which database backups, tasks, volume backups, and Docker cleanups are due:

| Value | Behavior |
| :--- | :--- |
| `sequential` | One process checks all four job types, one after another. Least CPU and memory; a slow job type can delay the other types. |
| `concurrent` | One process for each job type, all at the same time. A slow job type cannot delay the others, but each process loads Coolify — higher CPU and memory usage every minute. |

- When the variable is not set or has another value, self-hosted instances use `sequential`. Use `concurrent` when many backups or tasks are due at the same minute and one slow job type delays the others.

## Change a setting

- Connect to the server where the Coolify instance runs; back up the existing environment file, then edit it:

```bash
cp /data/coolify/source/.env /data/coolify/source/.env.backup
nano /data/coolify/source/.env
```

- Example — raise the PHP memory limit to `512M`:

```dotenv
PHP_MEMORY_LIMIT=512M
```

- Update the existing entry rather than adding duplicate keys; keep the instance's other values, including `APP_KEY`, unchanged. Store the backup securely — it contains instance credentials.
- Do NOT edit the generated `docker-compose.prod.yml` to change one of these defaults — installer and update scripts can replace that file.

## Apply the changes

- Changing `.env` does not update an existing container's environment — recreate the Coolify container using your installation's Compose configuration; a plain `docker restart coolify` does not reload these variables.
- For a standard self-hosted installation, run these commands in Bash from the source folder:

```bash
cd /data/coolify/source || exit 1
coolify_image=$(docker inspect --format '{{.Config.Image}}' coolify) || exit 1
compose_files=(-f docker-compose.yml -f docker-compose.prod.yml)
if [ -f docker-compose.custom.yml ]; then
  compose_files+=(-f docker-compose.custom.yml)
fi

docker compose "${compose_files[@]}" -f - up -d --no-deps --force-recreate coolify <<EOF
services:
  coolify:
    image: "$coolify_image"
EOF
```

- The final inline override keeps the running Coolify image instead of falling back to the Compose file's default image tag; the command also includes `docker-compose.custom.yml` if present (see Compose overrides: `/start-with-self-hosted#compose-overrides`).
- You do not need to run `docker compose down` first — `--force-recreate` replaces the Coolify container while `--no-deps` leaves PostgreSQL and Redis running. The dashboard is briefly unavailable while Coolify restarts.

## Verify the configuration

- Check the PHP memory limit inside the recreated container — for the example above the result should be `512M`:

```bash
docker exec coolify php -r 'echo ini_get("memory_limit"), PHP_EOL;'
```

- To inspect the effective PHP-FPM pool configuration:

```bash
docker exec coolify php-fpm -tt
```

- Confirm the dashboard still loads and review container logs if startup fails:

```bash
docker logs --tail 100 coolify
```

## When to use a Compose override

- Use `.env` for parameters supported by the instance configuration or its container image; use `/data/coolify/source/docker-compose.custom.yml` for Compose attributes such as `mem_limit`, CPU limits, port bindings, and additional file mounts.
- An arbitrary environment variable does not automatically become a PHP setting — if a parameter has no environment-variable support, it needs the image's supported configuration-file mechanism. Coolify also provides its own PHP configuration, so check the loaded configuration before assuming an image setting took effect.
- Compose override examples: `/start-with-self-hosted#examples`.
