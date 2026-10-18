#!/usr/bin/env bash
# msmtp-sendmail: sendmail-compatible wrapper around msmtp for the app
# image's PHP mail() path — php/mail.ini points sendmail_path here.
#
# The msmtp invocation is assembled from the container environment:
# - MAIL_SMTP_HOST / MAIL_SMTP_PORT — relay endpoint (required; compose.yml
#   supplies the development defaults, production overrides via platform
#   settings)
# - MAIL_SMTP_AUTH=on — authenticated submission: adds --auth, --user and
#   --passwordeval (the password is read from the environment at send
#   time — never baked into the image and never placed on a command
#   line) plus --tls=on (STARTTLS, submission port 587)
# - With auth off the wrapper matches the previous direct msmtp line:
#   plaintext relay, e.g. the development MailHog capture endpoint
#
# All sendmail-style arguments (sendmail_path's -t, Elgg's -f
# envelope-from) are forwarded to msmtp untouched.
#
# Debug mode: set MSMTP_SENDMAIL_DEBUG=1 to enable shell tracing (set -x).
# Manual check inside the app container (reads the message on STDIN):
#   MAIL_SMTP_HOST=mail.example.tld MAIL_SMTP_PORT=587 \
#     MSMTP_SENDMAIL_DEBUG=1 msmtp-sendmail -t < message.eml

set -euo pipefail

if [[ "${MSMTP_SENDMAIL_DEBUG:-0}" == "1" ]]; then
  set -x
fi

# main: validate the environment, assemble the msmtp arguments, and hand
# over via exec so msmtp's exit status becomes the wrapper's.
# Globals: MAIL_SMTP_HOST, MAIL_SMTP_PORT, MAIL_SMTP_AUTH,
#          MAIL_SMTP_USER, MAIL_SMTP_PASSWORD
# Arguments: sendmail-style arguments, forwarded to msmtp
# Outputs: msmtp's STDOUT/STDERR; validation errors on STDERR
# Returns: does not return (exec); exits 1 on validation failure
main() {
  if [[ -z "${MAIL_SMTP_HOST:-}" || -z "${MAIL_SMTP_PORT:-}" ]]; then
    printf '%s\n' \
      'msmtp-sendmail: MAIL_SMTP_HOST and MAIL_SMTP_PORT must be set' >&2
    exit 1
  fi
  local -a args=(--host "${MAIL_SMTP_HOST}" --port "${MAIL_SMTP_PORT}")
  if [[ "${MAIL_SMTP_AUTH:-}" == "on" ]]; then
    if [[ -z "${MAIL_SMTP_USER:-}" || -z "${MAIL_SMTP_PASSWORD:-}" ]]; then
      local msg='msmtp-sendmail: MAIL_SMTP_AUTH=on requires'
      msg+=' MAIL_SMTP_USER and MAIL_SMTP_PASSWORD'
      printf '%s\n' "${msg}" >&2
      exit 1
    fi
    args+=(--auth --tls=on --user "${MAIL_SMTP_USER}")
    args+=(--passwordeval 'printf %s "$MAIL_SMTP_PASSWORD"')
  fi
  exec msmtp "${args[@]}" "$@"
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
  main "$@"
fi
