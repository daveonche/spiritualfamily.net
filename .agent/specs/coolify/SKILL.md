# Coolify Conventions

Delta conventions for administering the self-hosted Coolify instance that
deploys this repository's projects: applications, services, networking,
notifications, security, instance management, MCP, and Cloudflare Tunnel
integrations. This pack records only repo-relevant rules and
easily-got-wrong details; general Coolify knowledge (dashboard navigation,
deployment concepts, provider basics) is assumed. Consult the Coolify
documentation when needed. Database resources are out of scope: the
upstream `databases/` section was not distilled.

**Source:** Coolify documentation — coollabsio/coolify-docs `content/docs`
@ main `3b7c416` (distilled alongside Coolify app v4.4.6), upstream docs at
[coolify.io/docs](https://coolify.io/docs).

**Scope:** Coolify administration tasks in this repository — instance
management, applications, services, networking (DNS, domains, proxy),
notifications, security (authentication, credentials), MCP, and
Cloudflare Tunnel integrations. Deployment context: single physical host,
Coolify servers on the same machine, shared umami/filebrowser/phpMyAdmin/
cron/email on the root server, MCP and Cloudflare Tunnels in scope,
`databases/` out.

**Convention Check Reminder:** Before creating or editing any file, check the Conventions Reference Routing table in `.agent/AGENTS.md` and load the matching reference via `/read-only` before proceeding.

## Role

You are a Coolify conventions reviewer for this repository. Priorities,
in order:

1. Operational safety: destructive actions (uninstall, restore, downgrade,
   password/2FA resets, key rotation) carry explicit warnings in the rules;
   never soften or skip them.
2. Least privilege and secret hygiene: tokens, keys, and webhook URLs are
   secrets — scope them to one team, prefer `read`/`deploy` over `root`,
   store them in secret managers, revoke on exposure.
3. Version accuracy: rules apply to the version recorded in the provenance
   manifest; run the freshness check before relying on any rule.
4. Delta-only guidance: do not add general Coolify practices, API
   references, or tutorials to this pack.

## Before advising

1. Locate the task's area: instance management, applications, services,
   networking, notifications, security, MCP, or integrations. Detection is
   read-only; it never modifies project files.
2. Freshness check: before using any `rules/` topic, compare the version
   in `references/source-listing-4.4.6.json` against the latest stable
   Coolify release; if a newer stable exists, prompt to fetch that
   release's source listing and refresh the affected
   `references/upgrade-notes/` and `rules/` files before relying on them.
3. Load the task-matched rule files from the index below before giving
   guidance.

## Rules index

- `rules/applications/builds/build-from-dockerfile.md` — build from a Dockerfile: Git-repo vs stored-Dockerfile paths, build context, ARGs, BuildKit secrets, healthcheck, Node.js multi-core scaling, domain troubleshooting.
- `rules/applications/builds/custom-docker-options.md` — **General > Custom Docker Options** adds container-runtime args only; image construction belongs in a Dockerfile.
- `rules/applications/builds/docker-compose.md` — Git repo + **Compose** build pack deploys as an Application (branch deploys, webhooks, previews).
- `rules/applications/builds/docker-registries.md` — Docker Image applications take a registry ref or digest (`@sha256:<digest>`); a digest pins content.
- `rules/applications/builds/railpack.md` — Railpack is Railway's Nixpacks successor; Git-based apps only; **Beta** — validate the generated image before production.
- `rules/applications/builds/static.md` — the **Static** build pack packages already-committed files into an Nginx image; it runs no build command.
- `rules/applications/configuration/advanced.md` — build cache modes (**Rebuild from scratch every time** vs **Use Docker build cache** default) and other advanced settings.
- `rules/applications/configuration/environment-variables.md` — environment-variable views — Normal view edits one variable at a time (Name, Value, scope, value settings).
- `rules/applications/configuration/general.md` — per-build-pack general settings: Nixpacks/Railpack build commands, repo paths, static-site controls, publish directory, domains, registry output.
- `rules/applications/configuration/health-checks.md` — HTTP or CMD healthchecks configured without image changes (Nixpacks, Railpack, Static, Dockerfile, Docker Image apps).
- `rules/applications/configuration/persistent-storage.md` — containers are replaceable — data written only to the container filesystem disappears on redeploy/removal; mount persistent storage.
- `rules/applications/configuration/resource-limits.md` — CPU/memory limits use `0` = no configured limit; start from measured usage, not the lowest possible value.
- `rules/applications/sources/bitbucket.md` — public repos clone over HTTPS; private repos use an SSH URL with a deploy key.
- `rules/applications/sources/cursor-origin.md` — deploy a private Cursor Origin repository over SSH with a private key stored in Coolify.
- `rules/applications/sources/deploy-keys.md` — one SSH key → one private repository, read-only (clone + fetch only); use when a Git App cannot be installed.
- `rules/applications/sources/gitea.md` — public repos clone over HTTPS; private repos use an SSH URL with a deploy key.
- `rules/applications/sources/github/actions.md` — GitHub Actions builds the image; Coolify deploys the published image via a **Docker Image** application.
- `rules/applications/sources/github/app.md` — GitHub App = repository selection + automatic deployments + PR events through one integration.
- `rules/applications/sources/github/auto-deploy.md` — auto-deploy defaults to **Deploy on push (webhooks)** with a GitHub App; manual-deployment behavior when changed.
- `rules/applications/sources/github/deploy-key.md` — read-only SSH access to ONE private repository without a personal access token or account-level SSH key.
- `rules/applications/sources/github/preview-deploy.md` — pull requests deploy separately from production; automatic previews are removed after the PR merges or closes.
- `rules/applications/sources/github/public-repository.md` — deploy any public GitHub repository without a GitHub App or deploy key.
- `rules/applications/sources/github/switch-apps.md` — switch an application's GitHub App when the repository moves to another account/org or the access policy changes.
- `rules/applications/sources/gitlab.md` — public → HTTPS URL; one private repo or OAuth unavailable → deploy key (SSH URL + repo deploy key).
- `rules/applications/sources/other-providers.md` — Forgejo, Gogs, self-hosted Git servers: **Public Repository** (unauthenticated HTTPS clone URL) or **Private Repository**.
- `rules/applications/sources/public-repositories.md` — **Public Repository** only for repos that clone over HTTPS without authentication — never an SSH URL.
- `rules/core/automation/cron-syntax.md` — five-field cron fields, operators, field-specific values, named schedules, timezone, and validation for all Coolify schedules.
- `rules/core/automation/deploy-webhooks.md` — authenticated deploy webhooks: deploy-only tokens, resource and tag URLs, request options, verification, and error handling.
- `rules/core/automation/scheduled-tasks/create-a-task.md` — create a scheduled task: command, frequency, timeout, container selection; verify with Execute now.
- `rules/core/automation/scheduled-tasks/execution-history.md` — read **Recent executions**: statuses, timing, output inspection and download, stuck-execution and no-output handling.
- `rules/core/automation/scheduled-tasks/manage-tasks.md` — update, enable/disable, run now, and permanently delete scheduled tasks.
- `rules/core/automation/tags/deploy-by-tag.md` — redeploy every tagged application and service as one group from the dashboard or the tag's deploy webhook.
- `rules/core/automation/tags/manage-tags.md` — create, assign, reuse, and remove team-scoped lowercase tags from a resource's **Tags** page.
- `rules/core/backup-and-recovery/change-root-email.md` — change the root login email from the server terminal (`root:change-email`) when the dashboard method is unavailable.
- `rules/core/backup-and-recovery/instance-backup.md` — instance backups cover Coolify's database only: schedule, retention, S3 copies, manual `pg_dump`, and saving `APP_KEY`.
- `rules/core/backup-and-recovery/instance-restore.md` — restore the instance database from a `.dmp` backup with the saved `APP_KEY`; SSH-key handling for replacement servers.
- `rules/core/backup-and-recovery/reset-2fa.md` — clear an account's authenticator secret and recovery codes via Tinker when no recovery code works.
- `rules/core/backup-and-recovery/reset-root-password.md` — reset the root password from the server terminal (`root:reset-password`); revokes root sessions and API tokens.
- `rules/core/instance-management/downgrade.md` — downgrade the self-hosted control plane via `upgrade.sh <version>` with automatic updates disabled; database-compatibility warnings.
- `rules/core/instance-management/environment-variables.md` — instance `.env` settings: PHP memory/workers, `SCHEDULED_JOBS_DISPATCH_MODE`, and how to recreate the container to apply them.
- `rules/core/instance-management/instance-settings.md` — instance-wide settings: URL, redirects, registration, DNS validation, API/MCP, telemetry, updates, backup, email, OAuth providers.
- `rules/core/instance-management/reverb-migration.md` — Soketi → Reverb: what changes, custom Compose overrides, ports 6001/6002, environment variables, Cloudflare configurations, verification.
- `rules/core/instance-management/uninstallation.md` — remove a self-hosted instance (containers, volumes, network, `/data/coolify`, images) or Coolify Cloud services from connected servers.
- `rules/core/instance-management/update-coolify-database.md` — migrate the internal PostgreSQL major version with `upgrade-postgres.sh`; rollback metadata and Compose override behavior.
- `rules/core/instance-management/update.md` — update Coolify: automatic updates, dashboard **Upgrade Now**, or `upgrade.sh` (never `install.sh` on an existing instance).
- `rules/core/networking/proxy/caddy/basic-auth.md` — HTTP basic auth requires a username + password before Caddy forwards the request; one shared credential.
- `rules/core/networking/proxy/caddy/custom-ssl-certs.md` — use custom certificates when your organization or another certificate authority issues them instead of Caddy's automatic certificates.
- `rules/core/networking/proxy/caddy/dns-challenge.md` — default HTTP challenge needs public port 80; use the DNS challenge for wildcard certificates or when port 80 is unavailable.
- `rules/core/networking/proxy/traefik/basic-auth.md` — HTTP basic auth requires a username + password before Traefik forwards a request; one shared credential.
- `rules/core/networking/proxy/traefik/custom-middlewares.md` — Traefik middlewares change requests before they reach a resource: rate limits, response headers, IP allow lists, redirects.
- `rules/core/networking/proxy/traefik/custom-ssl-certs.md` — custom certificates when a private CA or organization-issued certificate replaces the `letsencrypt` resolver.
- `rules/core/networking/proxy/traefik/dashboard.md` — new proxy configurations do not expose the dashboard; the upgrade removes the legacy dashboard router.
- `rules/core/networking/proxy/traefik/dns-challenge.md` — default config uses the ACME HTTP challenge (public port 80 required); switch to the DNS challenge for wildcard certificates.
- `rules/core/networking/proxy/traefik/dynamic-config.md` — Traefik separates startup configuration from dynamic routing configuration; Coolify stores file-based dynamic configuration.
- `rules/core/networking/proxy/traefik/load-balancing.md` — a Traefik load-balancer service distributes requests for one domain across multiple upstream URLs.
- `rules/core/networking/proxy/traefik/protect-services-with-authentik.md` — protect services with Traefik's `forwardAuth` middleware and an Authentik proxy provider.
- `rules/core/networking/proxy/traefik/tls-certificates.md` — **Servers > [server] > Proxy > TLS Certificates** views and manages certificates stored in Traefik's `acme.json`.
- `rules/core/networking/proxy/traefik/update.md` — respond to **Traefik patch update available** / **New Traefik minor version available** notices.
- `rules/core/networking/proxy/traefik/wildcard-certs.md` — `*.example.com` covers first-level subdomains only — not the apex `example.com` or deeper levels.
- `rules/core/notifications/channels/discord.md` — Discord delivery via a server incoming webhook; @here critical mentions; webhook-move and token-change troubleshooting.
- `rules/core/notifications/channels/email.md` — email provider paths (hosted, system-wide, SMTP, Resend) are mutually exclusive; configuration and delivery troubleshooting.
- `rules/core/notifications/channels/pushover.md` — Pushover delivery with the account user key plus an application API token; device registration required.
- `rules/core/notifications/channels/slack-mattermost.md` — Slack incoming webhooks; Mattermost's compatible format configures on the same Coolify **Slack** page.
- `rules/core/notifications/channels/telegram.md` — Telegram delivery via BotFather bot token, chat ID, and optional forum-topic routing with custom thread IDs.
- `rules/core/notifications/channels/webhook/setup.md` — webhook channel: endpoint requirements, blocked ranges, queued delivery behavior (5 attempts, 10s backoff), no signature header.
- `rules/core/notifications/events.md` — per-channel event selection: deployment, resource, backup, scheduled-task, and server event groups; rate limits.
- `rules/core/security/authentication/oauth/azure.md` — Microsoft Entra app registration for Azure OAuth sign-in.
- `rules/core/security/authentication/oauth/bitbucket.md` — Bitbucket Cloud OAuth consumer in the workspace owning the integration.
- `rules/core/security/authentication/oauth/discord.md` — Discord Developer Portal application for OAuth sign-in.
- `rules/core/security/authentication/oauth/github.md` — GitHub OAuth App registration.
- `rules/core/security/authentication/oauth/gitlab.md` — GitLab user application under **Edit profile > Access > Applications**.
- `rules/core/security/authentication/oauth/google.md` — Google Auth Platform OAuth client in the project owning the integration.
- `rules/core/security/authentication/oauth/infomaniak.md` — Infomaniak application in the user account's **Applications** page.
- `rules/core/security/authentication/sso/authentik.md` — configure Authentik as the SSO provider (Coolify v4.3.0+).
- `rules/core/security/authentication/sso/clerk.md` — configure Clerk as the SSO provider (Coolify v4.3.0+).
- `rules/core/security/authentication/sso/oidc.md` — generic OpenID Connect SSO: confidential web application at the identity provider, then Coolify configuration.
- `rules/core/security/authentication/sso/zitadel.md` — configure Zitadel as the SSO provider (Coolify v4.3.0+).
- `rules/core/security/credentials/api-tokens.md` — API tokens: permissions (`read`, `read:sensitive`, `write`, `deploy`, `root`), team scope, rotation, revocation triggers.
- `rules/core/security/credentials/cloud-init-scripts.md` — reusable cloud-init scripts for Hetzner/DigitalOcean/Vultr provisioning; edit and delete semantics.
- `rules/core/security/credentials/cloud-tokens.md` — provider tokens for Hetzner, DigitalOcean, Vultr, Hostinger: creation, scopes, validation limits, deletion guards.
- `rules/core/security/credentials/ssh-keys.md` — SSH private keys for server connections and deploy keys: creation, authorization, rotation, deletion guards.
- `rules/integrations/networking/cloudflare/tunnels/all-resource.md` — expose all resources through one tunnel when there is no public IP, port forwarding is unavailable, or the server IP must stay private.
- `rules/integrations/networking/cloudflare/tunnels/full-tls.md` — end-to-end TLS for apps that require HTTPS on Coolify itself (JWT issuance, callback URLs) while Cloudflare normally terminates TLS.
- `rules/integrations/networking/cloudflare/tunnels/server-ssh.md` — connect a remote server to Coolify over SSH through a tunnel — hides the server IP and allows closing SSH ports.
- `rules/integrations/networking/cloudflare/tunnels/single-resource.md` — expose one resource through a tunnel; the procedure starts by removing all domains from the **Domains** field.
- `rules/mcp/what-is-mcp.md` — what Coolify's MCP server provides (tools, resources, prompts, Streamable HTTP) and what it does not bypass.
- `rules/mcp/how-mcp-works.md` — connection flow, Bearer authentication and team scope, MCP primitives, and response boundaries.
- `rules/mcp/setup.md` — enable the MCP endpoint, create a scoped token, connect a client, and verify access.
- `rules/mcp/security.md` — least-privilege token selection, team scope, client configuration protection, and response boundaries.
- `rules/mcp/troubleshooting.md` — 404/403/401 fixes, connection requirements, permission and resource-scope diagnosis, log availability.
- `rules/mcp/clients/claude-code.md` — add Coolify via `claude mcp add --transport http` with a Bearer header; verify with `get_current_team`.
- `rules/mcp/clients/cursor.md` — add Coolify as a remote MCP server in Cursor's **Tools & Integrations**; verify with `get_current_team`.
- `rules/mcp/clients/other-clients.md` — required configuration (URL, Streamable HTTP transport, Bearer token) for any compatible MCP client.
- `rules/mcp/capabilities/teams.md` — `get_current_team` and `list_team_members`; MCP never switches teams during a request.
- `rules/mcp/capabilities/servers.md` — server and destination tools: list/inspect servers, domains, resources, and Docker destinations.
- `rules/mcp/capabilities/projects.md` — project, environment, and combined resource listing, fuzzy search, and tag tools.
- `rules/mcp/capabilities/applications.md` — list/inspect applications and pull-request previews; sensitive fields stay redacted.
- `rules/mcp/capabilities/databases.md` — list/inspect standalone databases, backup schedules, and backup executions.
- `rules/mcp/capabilities/services.md` — list/inspect services and their application and database components.
- `rules/mcp/capabilities/deployments.md` — list/inspect deployments; deploy, cancel, and start/stop/restart control (`stop` requires `confirm=true`).
- `rules/mcp/capabilities/logs.md` — `get_logs` (500-line cap) and deployment log summaries; redaction is best effort.
- `rules/mcp/capabilities/environment-and-storage.md` — env-key names, shared env keys, and storage mounts — key names only, never values.
- `rules/mcp/capabilities/integrations.md` — GitHub App listing, repository, and branch tools; call `list_github_apps` first.
- `rules/mcp/capabilities/automation.md` — scheduled tasks and executions, unhealthy-resource detection, and infrastructure overview.
- `rules/mcp/capabilities/prompts-and-resources.md` — `coolify://overview` and application resources; `troubleshoot_application` and `explain_failed_deploy` prompts.
- `rules/mcp/capabilities/pagination.md` — `page`/`per_page` arguments (default 50, max 100) and `_pagination.next.args` continuation.
- `rules/services/configuration/docker-compose.md` — every Service stores a Compose definition: one-click templates vs user-defined via **Docker Compose Editor**.
- `rules/services/configuration/environment-variables.md` — normal view, **Is Multiline?** for certificates/keys/config, **Is Literal?** value handling.
- `rules/services/configuration/general.md` — **Service Name** identifies the resource in project/environment — changing it does NOT rename Compose services.
- `rules/services/configuration/networking.md` — private per-Service network (name based on the Service UUID) that components join automatically.
- `rules/services/configuration/persistent-storage.md` — parsed storage is READ-ONLY on **Configuration > Persistent Storages** (grouped by component); add/change in Compose.

## References index

- `references/source-listing-4.4.6.json` — upstream source manifest for
  the distilled version; freshness-check input.
- `references/upgrade-notes/` — version-specific upgrade notes (empty at
  the v4.4.6 baseline).
- `references/webhook-payload-reference.md` — per-event webhook JSON
  payload reference, distilled from upstream
  `content/docs/core/notifications/channels/webhook/payload-reference.mdx`.

## Language pack

None — Coolify is a technology pack, not a framework pack.

## Gotchas

- Tags are team-scoped and stored lowercase; they group resources for
  navigation and bulk deploy but change no configuration, and deploy
  webhooks act only within the token's team.
- A notification requires both an enabled channel and selected events;
  **Send test** verifies credential delivery, not every selected event.
- Scheduled tasks, database backups, volume backups, and Docker cleanups
  each have their own per-minute scheduler; a busy type does not delay the
  others. Schedules run in the owning server's configured timezone, and
  fixed-time schedules run once when DST repeats an hour.
- Instance backups cover Coolify's database only — never application,
  database, or volume data — and `APP_KEY` must be saved separately or the
  backup cannot be restored.
- MCP returns only the token team's resources; environment values are
  never returned, and log redaction is best effort.
- `@every_minute` is not a supported named schedule — use `every_minute`.
- All MCP list tools return paginated data — read the pagination rule
  before iterating through large results.
- Editor tooling associates `Dockerfile*` filenames with Dockerfile
  syntax; the Dockerfile build rule is therefore named
  `build-from-dockerfile.md`.

## How to use

- Load only the rule files matching the task at hand; never load the pack
  "just in case".
- After editing anything in this pack, run
  `python3 -m pytest tests/test_s4_2_step1.py`.

<!-- sentinel: specs/coolify -->
