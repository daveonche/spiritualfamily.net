# Cron image (Story S1.6): the production definition carries no repo bind
# mounts (Coolify v4.4.6 runs `up` without the cloned repository present,
# so repo-relative bind sources resolve to empty directories), so the
# committed crontab is baked into the image at the entrypoint's spool
# source path (/tmp/app-cron in compose.yml). Base pin matches the cron
# service pin (Story S1.2 Step 3): alpine:latest resolved to 3.23.3 at
# pin time; bump deliberately, never float.
FROM alpine:3.23.3

COPY crontab /tmp/app-cron
