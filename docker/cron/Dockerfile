# syntax=docker/dockerfile:1
# Cron image (Story S1.6): the production definition carries no repo bind
# mounts (Coolify v4.4.6 runs `up` without the cloned repository present,
# so repo-relative bind sources resolve to empty directories), so the
# committed crontab is baked into the image at the entrypoint's spool
# source path (/tmp/app-cron in compose.yml). Base pin matches the cron
# service pin (Story S1.2 Step 3): alpine:latest resolved to 3.23.3 at
# pin time; bump deliberately, never float.
FROM alpine:3.23.3

COPY docker/cron/crontab /tmp/app-cron
# Log-rotation script (Story S1.7 Step 5): baked alongside the crontab —
# the production definition carries no repo bind mounts
COPY docker/cron/rotate-cron-log.sh /usr/local/bin/rotate-cron-log.sh
RUN chmod 755 /usr/local/bin/rotate-cron-log.sh
# Application-log rotation script (Story S1.8 Step 6): baked alongside
# the crontab — same no-bind-mounts rationale as the S1.7 script
COPY docker/cron/rotate-elgg-log.sh /usr/local/bin/rotate-elgg-log.sh
RUN chmod 755 /usr/local/bin/rotate-elgg-log.sh
