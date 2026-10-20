# Coolify Deployment Guide

Deploy the production stack from the repository's `compose.yml` — the single
source of truth for the app, dbm, and cron services. The development-only
`pma` service lives in `compose.dev.yml`, so Coolify never parses it into
the production deployment.

Steps verified against Coolify v4.4.6; exact menu labels vary between
versions.

## Scope

This guide covers creating the production application in Coolify from the
committed `compose.yml`, starting the app, dbm, and cron services as one
stack, HTTPS entry routing to the app service (Step 4), and production
email over the real relay (Step 5). The remaining deployment concerns are
handled by their own steps in `docs/analysis/S1.6-story-steps.md`:
version-pin verification (Step 2), environment/secret injection (Step 3),
the deploy runbook (Step 6), the database migration (Step 7), and
re-deploy reproducibility (Step 8). The runbook itself — the repeatable
deploy sequence with the mandatory pre-deploy database backup — is
`docs/deployment/deploy_runbook.md`.

## Prerequisites

- A Coolify instance with a connected Git source for this repository
- The committed `compose.yml` at the project root
- The committed build recipes `docker/app/Dockerfile` and
  `docker/cron/Dockerfile` — both images are built from the repository
  root during deployment

## One-time workspace setup

Servers, sources, and projects all live inside a team.

1. **Team** — create or select your team.
2. **Server** — Infrastructure → Servers → + New Server. Choose `localhost`
   when Coolify itself runs on the target host (Docker Engine is already
   present); otherwise add a remote server over SSH. Coolify validates the
   connection.
3. **Source** — Infrastructure → Sources → GitHub App; install it on this
   repository so Coolify can read it.
4. **Project** — Workspace → Projects → + New Project.
5. **Environment** — open the project and create an environment (for
   example `production`). No variable prompt appears here by design:
   creating an environment goes straight to its resource list. Variables
   are added per application after the resource exists (below).

## Create the application

There is no standalone Docker Compose resource type in v4.4.6: create a
Git-repository application, then choose Docker Compose as its build
strategy.

1. In the environment, select **+ New Resource**.
2. On the "Choose a resource" page, select **Git Repository (with GitHub
   App)**.
3. Pick the GitHub App source, this repository, and the branch (e.g.
   `main`).
4. Under **General → Build pipeline**, set:
   - Build strategy: **Docker Compose**
   - Base Directory: `/`
   - Docker Compose Location: `compose.yml`
5. Save and review **Docker Compose Content**: Coolify parses the file and
   lists the services it defines — app, dbm, and cron. `pma` never appears
   (it lives in `compose.dev.yml`).
6. Fill the **Environment Variables** tab (next section), then select
   **Deploy**.

## Environment variables

Coolify extracts some variables from the Compose definition and creates
them automatically; others are invisible to it and must be added by hand.
The committed `.env.example` documents every variable. Enter all values
unquoted — Coolify substitutes them literally, so a quoted value would put
quote characters into the substituted path.

Auto-created by Coolify (fill in the values where noted):

| Variable | Value |
| :--- | :--- |
| `PHP_VERSION` | `7.4` |
| `APP_HOST` | production hostname, no scheme |
| `APP_CRON_MINUTE` … `APP_CRON_YEARLY` (9 tokens) | left empty — Elgg 2.3 does not consume cron tokens; fill with real random strings once the upgrade reaches the latest Elgg version |
| `MAIL_SMTP_HOST` / `MAIL_SMTP_PORT` | production relay values in Step 5 — see Production email below (empty until then) |

Add manually (mark the four `MYSQL_*` credentials as secrets):

| Variable | Value |
| :--- | :--- |
| `APP_HOST_PORT` | a free port on the server — feeds the `ports` host mapping in `compose.yml` (direct-HTTP fallback; it bypasses domain routing — see HTTPS entry routing below) |
| `APP_CONTAINER_NAME` / `MYSQL_CONTAINER_NAME` / `CRON_CONTAINER_NAME` | unique names |
| `MYSQL_ROOT_PASSWORD` / `MYSQL_DATABASE` / `MYSQL_USER` / `MYSQL_PASSWORD` | real secrets |
| `MYSQL_HOST` | `dbm` |
| `ELGG_DB_PREFIX` | production table prefix |
| `ELGG_TIMEZONE` | `UTC` |
| `WWW_DATA` | `/var/www/dataroot` — must match the compose mount target |
| `WWW_CACHE` | `/var/www/cacheroot` — must match |
| `MAIL_SMTP_AUTH` / `MAIL_SMTP_USER` / `MAIL_SMTP_PASSWORD` | Step 5 relay authentication — `on` plus the relay mailbox credentials; mark the password as a secret (see Production email below) |

The full secrets-injection treatment is Step 3 of the story steps.

## Service inventory

| Service | Role | Image / build | In production |
| :--- | :--- | :--- | :--- |
| app | Elgg web server | Built from `docker/app/Dockerfile` (repository-root context) via the `PHP_VERSION` build arg; web root incl. tracked `vendor/` and PHP ini baked in | Yes |
| dbm | MariaDB database | Pinned `mariadb:10.11.18` | Yes |
| cron | Scheduled tasks | Built from `docker/cron/Dockerfile`; committed crontab baked in (`sfn/elgg_cron:3.23.3`) | Yes |

The three production services form one startable stack through the
`depends_on` chain in `compose.yml`: app starts only once dbm passes its
healthcheck, and cron starts after app. All three restart automatically
(`restart: unless-stopped`) on the shared `appnet` network.

## Development-only services

`pma` (phpMyAdmin) is a development-only database administration UI. It is
defined in `compose.dev.yml` rather than `compose.yml`, so it never appears
in the parsed service list and is never deployed to production. Developers
run it by merging the override file:

```bash
docker compose -f compose.yml -f compose.dev.yml up -d
```

## What the deployment does

1. Coolify clones the repository and builds **both** images from the
   repository root: the app image (`docker/app/Dockerfile`, `PHP_VERSION`
   build arg) with the web root — `src/` including the tracked `vendor/` —
   and the PHP ini overrides baked in; the cron image
   (`docker/cron/Dockerfile`) with the committed crontab baked in.
2. The production definition carries **no repository bind mounts**:
   v4.4.6 runs `docker compose up` without the cloned repository present,
   so repo-relative bind sources would resolve to empty directories.
   Everything the containers need is baked into the images — which also
   makes the "Preserve repository during deployment" option unnecessary.
3. Services start in dependency order: dbm first (its compose healthcheck
   must pass), then app, then cron.

The application's "Healthcheck: Not configured" badge is expected:
Git-based Docker Compose applications do not use the application-level
healthcheck page — healthchecks come from the Compose definition itself
(dbm's gates startup).

## HTTPS entry routing (Step 4)

Inbound HTTPS terminates at Coolify's proxy in front of the app container;
the proxy forwards the request to the app service over the internal Docker
network. TLS is never terminated inside the app container.

1. Open the application → **Domains** page — domains are managed there,
   not as a comma-separated list under General.
2. Add the production domain:
   - Protocol: `https`
   - Domain: the production hostname, no scheme (matches `APP_HOST`)
   - Port: `80` — a domain port selects the app container's internal
     port; it does not publish a host port
   - Service: `app` — Compose applications attach domains per service
3. **Save changes**, then redeploy the application so the proxy picks up
   the new domain.
4. The proxy obtains the Let's Encrypt certificate via the default ACME
   HTTP challenge, which requires reachable public port 80 — satisfied by
   this deployment's topology (DNS → server IP → Coolify's proxy). No
   DNS-challenge proxy configuration is needed for a single non-wildcard
   domain.

The `${APP_HOST_PORT}:80` mapping in `compose.yml` is a direct-HTTP
fallback that bypasses domain routing; it does not participate in HTTPS
entry routing. Note a fixed host port prevents rolling updates — if those
become needed, move the mapping to `compose.dev.yml` (development-only).

## Production email (Step 5)

Outgoing mail flows PHP `mail()` → the image's `msmtp-sendmail` wrapper →
msmtp → the SMTP relay named by the `MAIL_SMTP_*` variables. The relay is
the NamesPro shared hosting's mail cluster (StackCP, the 20i hosting
platform — hostnames under `stackmail.com`) hosting the
`spiritualfamily.online` mailboxes — a split setup: the domain is
registered at Namecheap with authoritative DNS there, the apex A record
stays on the VPS (website via Coolify), and only the mail records (MX,
SPF, optional DKIM) are added at Namecheap pointing at the stackmail
cluster.

Prerequisites (one-time):

1. Add `spiritualfamily.online` as a domain in the NamesPro hosting
   control panel (StackCP). This auto-creates the platform's default
   zone — A/AAAA at the hosting web servers, `MX mx.stackmail.com`, SPF
   `v=spf1 include:spf.stackmail.com a mx -all`, and `imap`/`mail`/
   `pop3`/`smtp` CNAMEs into `stackmail.com`. Those records are not live
   while Namecheap's DNS is authoritative (confirm the domain uses
   Namecheap BasicDNS under Domain List → Manage → Nameservers); the
   mail-relevant values are replicated at Namecheap in step 3.
2. Create the mailbox in StackCP → Mail, including
   `noreply@spiritualfamily.online` — the site's sender identity
   (From/envelope-from for Elgg mail). Store the mailbox password in the
   secret manager; it becomes `MAIL_SMTP_PASSWORD` and is never
   committed.
3. Add the mail records at Namecheap (Domain List → Manage → Advanced
   DNS), leaving the existing records untouched — the apex A record
   (→ VPS) is the website path:

| Type | Host | Value | Purpose |
| :--- | :--- | :--- | :--- |
| MX | `@` | `mx.stackmail.com` | inbound mail to the mailboxes |
| TXT | `@` | `v=spf1 include:spf.stackmail.com a mx -all` | SPF — authorizes the stackmail relay for the domain |
| TXT | `<selector>._domainkey` | the DKIM record StackCP publishes for the domain, if any | DKIM signing |

Set the MX priority to the value StackCP shows for the domain (typically
`10`). Do not enable Namecheap's Email Forwarding (it would point MX at
Namecheap's own servers, away from the cluster) and do not copy the
StackCP A/AAAA records. The zone's `imap`/`mail`/`pop3`/`smtp` CNAMEs
are client-config conveniences and are not replicated — the relay
connects to `smtp.stackmail.com` directly (see the settings table
below). If StackCP publishes no DKIM record, SPF alone is acceptable to
start — the trigger test below shows whether more is needed (spam
placement).

Coolify settings (Environment Variables tab; redeploy afterwards so the
running containers receive them):

| Variable | Value |
| :--- | :--- |
| `MAIL_SMTP_HOST` | `smtp.stackmail.com` — the cluster's submission hostname; connect to it directly rather than via the `smtp.spiritualfamily.online` CNAME, because the cluster's TLS certificate is issued for the stackmail hostname and msmtp verifies the certificate against the host it connects to |
| `MAIL_SMTP_PORT` | `587` — STARTTLS submission, matching the wrapper's `--tls=on` |
| `MAIL_SMTP_AUTH` | `on` |
| `MAIL_SMTP_USER` | `noreply@spiritualfamily.online` |
| `MAIL_SMTP_PASSWORD` | that mailbox's password — mark as secret |

The wrapper adds authentication and STARTTLS only when `MAIL_SMTP_AUTH=on`
and both credential variables are non-empty; without them it keeps the
plaintext development behaviour (MailHog), so development needs no changes.

Confirm the injected values inside the running app container — they must
be the production values, not the dev defaults:

```bash
docker exec <app container> printenv MAIL_SMTP_HOST MAIL_SMTP_PORT MAIL_SMTP_AUTH
```

End-to-end trigger — the identical transport Elgg uses (`mail()` →
`sendmail_path` → wrapper → msmtp → relay), including the `-f`
envelope-from Elgg passes via `mail()` additional parameters:

```bash
docker exec <app container> php -r 'mail("you@example.com", "S1.6 Step 5 relay check", "Delivered via the production relay.", "From: noreply@spiritualfamily.online\r\n", "-f noreply@spiritualfamily.online");'
```

Confirm arrival in a real inbox. A real Elgg-generated email (e.g. user
validation) is re-checked after Step 7 restores the users.

Troubleshooting:

- **Certificate mismatch** — msmtp verifies the relay certificate; the
  cluster's certificate is issued for the stackmail hostnames, so
  connect to `smtp.stackmail.com` directly (see the settings table).
  Pre-check from the VPS before redeploying:
  `openssl s_client -connect smtp.stackmail.com:587 -starttls smtp -brief`.
- **Authentication failures** — the username is the full mailbox address;
  re-check the password against a StackCP webmail login.
- **Accepted but not delivered** — confirm the MX/SPF records were added
  at Namecheap and have propagated before testing, check the StackCP
  mail logs/webmail, and look in the recipient's spam folder. If mail
  lands in spam, add the DKIM record StackCP publishes for the domain
  (prerequisite step 3) and retest.
- **`mail()` returns true but nothing is relayed** — confirm the wrapper
  is wired in the running container:
  `docker exec <app container> php -r 'var_dump(ini_get("sendmail_path"));'`
  must print `/usr/local/bin/msmtp-sendmail -t`. The base image ships
  `conf.d/sendmail.ini`, whose `sendmail_path` shadows the repo's
  `mail.ini` (PHP loads conf.d alphabetically and the last value wins);
  the app image removes that file (see `docker/app/Dockerfile`). A stale
  image built before that fix silently hands `mail()` to the stock
  sendmail's local queue — redeploy to rebuild (observed in production
  2026-10-20).
- **Direct mail() tests need `-f`** — msmtp exits 78 ("envelope-from
  address is missing") when `mail()` is called without the `-f`
  additional parameter. Elgg always supplies it (see the trigger above);
  direct `php -r` checks must pass it too.

## Gotchas

- **MariaDB initializes once.** The `MYSQL_*` accounts are created only
  when the `fs-dbmdata` volume is empty — on the first start ever.
  Changing the variables in Coolify afterwards does not update the
  database; the app then fails with `Access denied … (using password:
  YES)`. While the install is still empty, fix it by stopping the stack,
  deleting the `fs-dbmdata` volume, and redeploying. Once production data
  is restored (Step 7), never wipe the volume.
- **Do not run the Elgg web installer in production.** The production
  schema arrives with the Step 7 dump restore; a web-installer schema
  would be a parallel one the restore would have to remove.
- **No `composer install` in the container.** The tracked vendor tree is
  baked into the image (the S1.1 baseline design: a fresh clone runs
  without `composer install`); the container's writable layer is
  ephemeral, and the base image disables `putenv()`, which Composer
  requires.
- **Empty mail variables fall back to the dev defaults.** With
  `MAIL_SMTP_HOST`/`MAIL_SMTP_PORT` empty (or absent), the Compose
  interpolation defaults apply in the app container
  (`${MAIL_SMTP_HOST:-host.docker.internal}`, port `1025`) — the
  development capture endpoint. While they stay empty — before the
  production relay is set (Production email (Step 5) above) — outgoing
  mail fails; that is expected and harmless while the database is still
  empty (nothing triggers email before the Step 7 restore).

## Verify

- [ ] The application in Coolify lists exactly the app, dbm, and cron
  services as one stack.
- [ ] `pma` appears nowhere in the production deployment — it is defined
  only in `compose.dev.yml`.
- [ ] The services started in dependency order (app after dbm healthy, cron
  after app).
- [ ] `https://<production domain>` loads in a browser with a valid
  certificate and no warnings (Step 4).
- [ ] The production domain resolves to the deployed stack (Step 4).
- [ ] A triggered email is delivered through the real relay (Step 5).
