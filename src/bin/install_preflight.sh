#!/usr/bin/env bash
# Pre-flight checks for installing Elgg 2.3.17 inside the Compose app
# container (Story S1.3 Step 3).
#
# Elgg 2.3 provides no CLI installer, so the web installer documented in
# docs/baseline/fresh_checkout.md remains the install procedure. This
# script verifies everything that procedure depends on before it runs:
# required environment variables, database reachability and freshness,
# data/cache directory writability, and the pinned Elgg core version.
#
# Globals: none (reads the container environment; see REQUIRED_ENV_VARS)
# Arguments: optional --debug flag
# Outputs: progress and summary to STDOUT, errors to STDERR
# Returns: 0 when every check passes, non-zero on the first failure

set -euo pipefail

# Required environment variables (documented in .env.example; consumed
# by src/elgg-config/settings.php and the Compose stack)
readonly REQUIRED_ENV_VARS=(
  MYSQL_USER
  MYSQL_PASSWORD
  MYSQL_DATABASE
  MYSQL_HOST
  ELGG_DB_PREFIX
  ELGG_TIMEZONE
  WWW_DATA
  WWW_CACHE
)

# Pinned Elgg core version (docs/baseline/baseline.md)
readonly PINNED_ELGG_VERSION='2.3.17'

# Web root inside the app container (the ./src mount target)
readonly WWW_ROOT_DIR="${WWW_ROOT:-/var/www/html}"

# Log an informational message to STDOUT.
# Arguments: Message text
# Outputs: Message to STDOUT
log_info() {
  printf '%s\n' "$*"
}

# Log an error message to STDERR.
# Arguments: Message text
# Outputs: Message to STDERR
log_error() {
  printf '%s\n' "ERROR: $*" >&2
}

# Print usage information with an example.
# Outputs: Usage text to STDOUT
usage() {
  cat <<'EOF'
Usage: install_preflight.sh [--debug]

Verifies the app container is ready for the Elgg 2.3 web installer:
required environment variables, database reachability and freshness,
data/cache directory writability, and the pinned Elgg core version.

Example (from the repository root):

  docker compose exec app /var/www/html/bin/install_preflight.sh
EOF
}

# Verify every required environment variable is set and non-empty.
# Globals: REQUIRED_ENV_VARS (read)
# Outputs: Error message to STDERR on failure
# Returns: Non-zero on the first missing variable
check_env_vars() {
  local var
  for var in "${REQUIRED_ENV_VARS[@]}"; do
    if [[ -z "${!var:-}" ]]; then
      log_error "Environment variable not set: ${var}"
      return 1
    fi
  done
}

# Verify the database accepts connections with the configured
# credentials, using PHP PDO (the same driver Elgg itself uses).
# Globals: MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD (read)
# Outputs: Error message to STDERR on failure
# Returns: Non-zero when the connection fails
check_database_connection() {
  if ! php -r '
        try {
          new PDO(
            "mysql:host=" . getenv("MYSQL_HOST")
            . ";dbname=" . getenv("MYSQL_DATABASE"),
            getenv("MYSQL_USER"),
            getenv("MYSQL_PASSWORD")
          );
        } catch (PDOException $e) {
          fwrite(STDERR, "Database connection failed: "
            . $e->getMessage() . "\n");
          exit(1);
        }
      '; then
    log_error "Cannot reach the database server ${MYSQL_HOST}"
    return 1
  fi
}

# Verify the database holds no Elgg tables yet; the installer requires
# a fresh database for a reproducible schema install.
# Globals: MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD,
# ELGG_DB_PREFIX (read)
# Outputs: Error message to STDERR on failure
# Returns: Non-zero when prefixed tables already exist
check_database_fresh() {
  local count
  if ! count=$(php -r '
        try {
          $pdo = new PDO(
            "mysql:host=" . getenv("MYSQL_HOST")
            . ";dbname=" . getenv("MYSQL_DATABASE"),
            getenv("MYSQL_USER"),
            getenv("MYSQL_PASSWORD")
          );
          $stmt = $pdo->query(
            "SHOW TABLES LIKE "
            . $pdo->quote(getenv("ELGG_DB_PREFIX") . "%")
          );
          printf("%d\n", $stmt->rowCount());
        } catch (PDOException $e) {
          fwrite(STDERR, $e->getMessage() . "\n");
          exit(1);
        }
      '); then
    log_error \
      "Could not inspect tables in database ${MYSQL_DATABASE}"
    return 1
  fi
  if [[ "${count}" -ne 0 ]]; then
    log_error \
      "Database ${MYSQL_DATABASE} already holds ${count}" \
      "Elgg table(s) with prefix ${ELGG_DB_PREFIX};" \
      "the installer needs a fresh database"
    return 1
  fi
}

# Verify a directory exists and is writable. When running as root, the
# direct write probe is meaningless (root writes anywhere), so the
# ownership by www-data — the web SAPI user — is verified instead.
# Arguments: Directory path
# Outputs: Error message to STDERR on failure
# Returns: Non-zero when the directory is missing or not writable
check_writable_dir() {
  local dir="$1"
  if [[ ! -d "${dir}" ]]; then
    log_error "Directory does not exist: ${dir}"
    return 1
  fi
  local probe="${dir}/.install_preflight_probe.$$"
  if ! touch "${probe}" 2>/dev/null; then
    log_error "Directory not writable: ${dir}"
    return 1
  fi
  rm -f "${probe}"
  if [[ "$(id -u)" -eq 0 ]]; then
    local owner
    owner=$(stat -c '%U' "${dir}")
    if [[ "${owner}" != 'www-data' ]]; then
      log_error \
        "Directory not owned by www-data (web SAPI user):" \
        "${dir} (owned by ${owner})"
      return 1
    fi
  fi
}

# Verify the vendored Elgg core matches the pinned version.
# Globals: PINNED_ELGG_VERSION, WWW_ROOT_DIR (read)
# Outputs: Error message to STDERR on failure
# Returns: Non-zero on version mismatch
check_pinned_core() {
  local version_file="${WWW_ROOT_DIR}/vendor/elgg/elgg/version.php"
  if [[ ! -f "${version_file}" ]]; then
    log_error "Elgg core version file not found: ${version_file}"
    return 1
  fi
  local release_line release
  if ! release_line=$(grep -F '$release' "${version_file}" | head -n 1); then
    log_error "No \$release line found in ${version_file}"
    return 1
  fi
  release=$(printf '%s' "${release_line}" | sed "s/.*'\([^']*\)'.*/\1/")
  if [[ "${release}" != "${PINNED_ELGG_VERSION}" ]]; then
    log_error \
      "Elgg core version mismatch: expected" \
      "${PINNED_ELGG_VERSION}, found '${release:-none}'" \
      "in ${version_file}"
    return 1
  fi
}

# Run every pre-flight check in order and report the result.
# Globals: WWW_DATA, WWW_CACHE (read)
# Outputs: Progress and summary to STDOUT; errors to STDERR
# Returns: Non-zero on the first failed check
main() {
  local debug=false
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --debug)
        debug=true
        shift
        ;;
      -h|--help)
        usage
        return 0
        ;;
      *)
        log_error "Unknown option: $1"
        usage >&2
        return 1
        ;;
    esac
  done
  if [[ "${debug}" == true ]]; then
    set -x
  fi

  check_env_vars
  log_info 'Environment variables: OK'
  check_database_connection
  log_info 'Database connection: OK'
  check_database_fresh
  log_info 'Database freshness: OK'
  check_writable_dir "${WWW_DATA}"
  log_info "Data directory writable: OK (${WWW_DATA})"
  check_writable_dir "${WWW_CACHE}"
  log_info "Cache directory writable: OK (${WWW_CACHE})"
  check_pinned_core
  log_info "Pinned Elgg core ${PINNED_ELGG_VERSION}: OK"

  cat <<'EOF'
All pre-flight checks passed.
Continue with the Elgg 2.3 web installer steps in
docs/baseline/fresh_checkout.md (Step 6).
EOF
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
  main "$@"
fi
