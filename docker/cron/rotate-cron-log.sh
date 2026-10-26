#!/bin/sh
# Rotate the scheduled-task output log (Story S1.7 Step 5).
#
# Copy-truncates the cron jobs' active output log into a date-stamped
# gzip archive and prunes archives older than the retention window —
# the duty of the dropped logrotate plugin (REQ-FR-PU-7). Runs daily
# from the committed crontab, after the midnight Elgg jobs.
#
# BusyBox-compatible by design: the cron image is alpine and ships no
# bash and no logrotate, so this script uses only POSIX sh + cp + gzip
# + find. (Deviation from the repo's bash-script conventions, which
# assume bash: #!/bin/sh shebang and set -eu without pipefail — the
# script runs no pipelines, so pipefail has no effect here.)

set -eu

LOG_DIR="/var/log/cron"
ACTIVE_LOG="$LOG_DIR/app-cron.log"
RETENTION_DAYS=7

# Debug mode: -d traces execution (set -x)
debug=0
for arg in "$@"; do
  case "$arg" in
    -d) debug=1 ;;
    *) echo "usage: rotate-cron-log.sh [-d]" >&2; exit 2 ;;
  esac
done
if [ "$debug" -eq 1 ]; then
  set -x
fi

# Nothing written yet — nothing to rotate
if [ ! -s "$ACTIVE_LOG" ]; then
  exit 0
fi

ARCHIVE="$ACTIVE_LOG.$(date +%Y%m%d-%H%M%S)"

# Copy-truncate: cron jobs open the active log in append mode, so
# truncating after the copy never loses their subsequent output
cp "$ACTIVE_LOG" "$ARCHIVE"
: > "$ACTIVE_LOG"

gzip -f "$ARCHIVE"
echo "rotated $ACTIVE_LOG -> $ARCHIVE.gz"

# Retention: remove archives older than the window
find "$LOG_DIR" -name 'app-cron.log.*.gz' -mtime +"$RETENTION_DAYS" -delete
