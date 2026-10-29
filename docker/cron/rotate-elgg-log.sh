#!/bin/sh
# Rotate the Elgg application log (Story S1.8 Step 6).
#
# Copy-truncates the application log file (ELGG_LOG_FILE, Step 1) into
# a date-stamped gzip archive and prunes archives older than the
# retention window — the application-log half of the routed-log
# rotation duty, complementing the scheduled-task rotation
# (rotate-cron-log.sh, S1.7 Step 5) with the same retention window.
# Runs daily from the committed crontab, staggered after that script's
# entry.
#
# BusyBox-compatible by design: the cron image is alpine and ships no
# bash and no logrotate, so this script uses only POSIX sh + cp + gzip
# + find. (Same deviation from the repo's bash-script conventions as
# rotate-cron-log.sh: #!/bin/sh shebang and set -eu without pipefail —
# the script runs no pipelines, so pipefail has no effect here.)

set -eu

# Debug mode: -d traces execution (set -x)
debug=0
for arg in "$@"; do
  case "$arg" in
    -d) debug=1 ;;
    *) echo "usage: rotate-elgg-log.sh [-d]" >&2; exit 2 ;;
  esac
done
if [ "$debug" -eq 1 ]; then
  set -x
fi

# Log path: ELGG_LOG_FILE (the same variable the app's settings.php
# reads, passed through compose.yml); falls back to the baked default
# on the fs-dataroot volume when unset or empty (.env.example's empty
# default = SAPI log destination, which is not a file to rotate)
LOG_FILE="${ELGG_LOG_FILE:-/var/www/dataroot/elgg-log.txt}"
LOG_DIR="$(dirname "$LOG_FILE")"
RETENTION_DAYS=7

# Retention first: archives beyond the window are pruned on every run,
# even when there is nothing new to rotate
find "$LOG_DIR" -name "$(basename "$LOG_FILE").*.gz" -mtime +"$RETENTION_DAYS" -delete

# Nothing written yet — nothing to rotate
if [ ! -s "$LOG_FILE" ]; then
  exit 0
fi

ARCHIVE="$LOG_FILE.$(date +%Y%m%d-%H%M%S)"

# Copy-truncate: the app opens the log in append mode, so truncating
# after the copy never loses its subsequent output
cp "$LOG_FILE" "$ARCHIVE"
: > "$LOG_FILE"

gzip -f "$ARCHIVE"
echo "rotated $LOG_FILE -> $ARCHIVE.gz"
