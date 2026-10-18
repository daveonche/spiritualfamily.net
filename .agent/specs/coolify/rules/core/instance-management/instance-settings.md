# Core › Instance Management › Instance Settings

Source: coollabsio/coolify-docs `content/docs/core/instance-management/instance-settings.mdx` @ main `3b7c416`.

## Scope

- Instance settings control the Coolify control plane itself — not a project, application, database, service, or server.
- Coolify Cloud: the Coolify team manages the instance; Cloud users cannot access or edit these settings.
- Open **Settings** in the left sidebar (or visit `https://<dashboard-domain>/settings`). Options are grouped under **Configuration** (General, Advanced, Updates) and **Instance** (Backup, Email, Authentication); use **Save changes** to apply edits.
- Upstream examples use the placeholder domain `shadowarcanist.com` — substitute your own.

## General

### URL

- The address used to open the dashboard; Coolify also uses it to generate links back to the instance. For HTTPS the value must start with `https://`.
- Enter the full dashboard URL and **Save changes**. DNS for the domain must point to the server running Coolify first; with DNS validation enabled Coolify checks the domain, and Coolify warns when the instance URL is already used by another resource.
- Accepted: a full URL with protocol (e.g. `https://coolify.<domain>`); `https://` when the dashboard is served over HTTPS; a root domain or subdomain.
- Path-based URLs (`https://<domain>/coolify`) are NOT supported. Do not reuse an application or service domain for the dashboard — conflicting instance/resource domains make SSL certificates and routing unpredictable.

### Redirect HTTP to HTTPS

- Controls whether dashboard requests over HTTP are redirected to HTTPS; choose **Enabled** or **Disabled**, then **Save changes**.
- If a reverse proxy or Cloudflare Tunnel connects to Coolify over HTTP while users access the dashboard over HTTPS, enabling this can create a redirect loop — disable the redirect in Coolify when the proxy handles HTTPS redirection.
- Keep enabled when Cloudflare uses **Full** or **Full (Strict)** SSL.

### Name

- Adds the instance name in brackets before the browser-tab page title (e.g. `[Production] Settings | Coolify`); it does not change the sidebar name or logo.
- Any text up to 255 characters; empty keeps the default instance name.

### Instance timezone

- Used by instance-level schedules (update checks, automatic updates); does not change the timezone inside applications or databases.
- A valid timezone identifier from the dashboard list (e.g. `UTC`, `Europe/Berlin`, `America/New_York`).
- Database backup schedules can use the server timezone when the server has one configured; otherwise they fall back to the instance timezone.

### Instance public IPv4 / IPv6

- Coolify normally detects the server's public addresses; use these fields only when detection is wrong (typically servers with multiple public IPs).
- Leave empty to use the detected address, or enter a valid address (e.g. `203.0.113.10` / `2001:db8::1`).

## Advanced

### Access

- **Registration**: whether users can create accounts from the registration page — **Anyone can register** or **Registration disabled**. Keep registration disabled unless public self-registration is intentional.
- **Destructive action confirmation**: **Require two-step confirmation** (password + text confirmation) or **Skip two-step confirmation**. Skipping increases the chance of accidental deletions or unwanted changes.

### DNS

- **DNS validation**: validate custom domains before deployment — **Enabled** or **Disabled**.
- **Custom DNS servers**: resolvers used for domain validation — empty uses the system default, or a comma-separated list of IPv4/IPv6 addresses (e.g. `1.1.1.1,8.8.8.8`).

### API and MCP

- **API access**: whether authenticated requests to the Coolify REST API are allowed; for tokens see the api-tokens rule.
- **MCP server**: enables the MCP endpoint at `/mcp` for authenticated clients — use `<instance-url>/mcp` with a Sanctum bearer token created from the api-tokens rule.
- **Allowed API IPs**: restrict source IPs for the API — empty or `0.0.0.0` allows anywhere (not recommended for production), or single IPv4/IPv6 addresses and CIDR ranges (e.g. `10.0.0.0/8`, `203.0.113.0/24`).

### Outbound endpoints

- **Allowed internal targets**: internal destinations Coolify can reach for outbound requests — hostnames, IP addresses, or CIDR ranges required by integrations, separated by commas or new lines.
- **Localhost targets**: whether outbound requests can target localhost — **Allowed** or **Blocked**. Loopback targets must also be included in **Allowed internal targets**.

### Interface and telemetry

- **Navigation**: **SPA navigation** (links prefetch, pages transition without full reloads) or **Full page navigation**. Choose full page navigation if the dashboard has navigation issues after a browser or Livewire change.
- **Anonymous telemetry**: whether the instance sends an anonymous installation count to Coolify (contributes to the self-hosted install count on coolify.io); no IP addresses stored, no error reports. CAUTION: the upstream page's accepted-value descriptions are inverted relative to its own description — verify the effective behavior after changing.
- **Sponsorship reminders**: whether a sponsorship popup is shown each month — **Enabled** or **Disabled**.

### Image storage

- **Storage destination**: **Local storage** or a configured S3-compatible destination — add and test the S3 storage under **Storages** before selecting it here.
- **Image CDN URL**: optional public CDN URL for profile pictures and project icons stored on S3.

## Updates

- **Update Coolify**: click **Upgrade Now** when an update is available — see the update rule.
- **Update checks**: **Check frequency** sets how often Coolify checks the versions file (`https://cdn.coollabs.io/coolify/versions.json`) — `every_minute`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`, or a valid cron expression; default `0 * * * *` (hourly). **Check Now** checks immediately.
- **Automatic updates**: **Enabled**/**Disabled**, saved immediately; when enabled, set **Update frequency** and **Save changes**. If the control is disabled, `AUTOUPDATE` in `/data/coolify/source/.env` controls automatic updates instead.
- **Update frequency**: how often updates install when automatic updates are enabled — same accepted values; default `0 0 * * *` (daily at 00:00). Managed by `AUTOUPDATE` in `.env` when that variable is set.
- **Image registry**: **Docker Hub** or **GitHub Container Registry** — switch if the current source is rate limited or unreachable.

## Backup

- Manages backups of Coolify's internal database; it does not back up application volumes or external databases — see the instance-backup rule.
- **First-time setup**: when not configured the page shows **Backup is not configured** — click **Configure backup** to create the internal `coolify-db` resource and its scheduled backup. The localhost server must be functional; if not, open server settings and validate the localhost connection first.
- **Instance database**: **Name**, **UUID**, **User**, and **Password** are read-only; **Description** is editable — **Save changes** after editing.
- **Backup schedule**:
  - **Enable backup** / **Disable backup** turns the scheduled job on or off (saves immediately); **Back up now** starts a backup without waiting for the next scheduled run — the database must be running.
  - **Frequency**: `hourly`, `daily`, `weekly`, `monthly`, `yearly`, `every_minute`, or a valid cron expression (e.g. `0 0 * * *` daily at midnight).
  - **Timezone**: read-only — the timezone set for Coolify's localhost server, or the instance timezone if no server timezone is set.
  - **Timeout**: maximum backup runtime in seconds (`60`–`36000`); exceeding it marks that execution failed.
  - **Missing backup alert after**: days without a backup execution before an alert is sent through backup-failure notification channels — `0` disables, otherwise `1`–`365`.
- **S3 storage**: keeps a remote copy of each instance backup — set up and validate an S3 storage destination first. Click **Enable S3**, select the **S3 storage** destination, choose whether **Local copy** should **Keep local backup** or **Delete after S3 upload**, and **Save changes**. The storage selector is unavailable until a validated destination exists.
- **Retention**: separate limits for **Local backups** and **S3 backups** — **Backups to keep**, **Days to keep**, **Maximum storage (GB)** (decimals accepted). The first limit reached removes the oldest backup; `0` means unlimited for that limit.
- **Executions**: shows status, database, file path, finish time, duration, size, and local/S3 availability. Delete (trash icon) removes the local backup file if still present and the execution from the list — whether a password confirmation is asked depends on **Destructive action confirmation**. Download icon on a successful run downloads the backup. **Clean failed backups** removes rows for unsuccessful runs; **Clean deleted entries** removes rows whose local files are already deleted (it does not delete files).

## Email

- Configures the sender identity and chooses SMTP or Resend for Coolify's transactional email.
- **Sender** (shared by SMTP and Resend): **From name** (required text) and **From address** (required email address).
- **SMTP server**: enter the **Host**, **Port**, and **Encryption** supplied by your provider; fill the remaining fields as needed.
  - **SMTP delivery**: **Enabled** sends through SMTP, **Disabled** turns SMTP off — saves immediately; enabling SMTP turns off Resend.
  - **Host**: the SMTP hostname from the provider (e.g. `smtp.mailgun.org`).
  - **Port**: `1`–`65535`; common values `587`, `465`, `25`.
  - **Encryption**: **StartTLS**, **TLS / SSL**, or **None**.
  - **Username and password**: leave empty if the provider does not require authentication.
  - **Timeout**: maximum delivery time in seconds; empty uses the default mailer behavior.
  - **EHLO domain**: optional fully qualified domain sent in the SMTP EHLO command; empty uses the system default.
  - After editing connection settings, click **Save changes**.
- **Resend**: create an API key in Resend and enter it in **API key** (required when Resend is enabled); **Enabled** under **Resend delivery** uses Resend, **Disabled** turns it off — saves immediately, and enabling Resend turns off SMTP. **Save changes** saves the API key and sender fields.

## Authentication

- Lists the supported OAuth providers: Authentik, Bitbucket, Clerk, Discord, GitHub, GitLab, Google, Infomaniak, Microsoft, OpenID Connect (OIDC), Zitadel. Each provider section has an **Enable** action, credentials, a redirect URI, and provider-specific fields.
- The page also controls password registration while OAuth is enabled; each provider can auto-join newly created users to the Root team as members. For OIDC user creation and verification, see the oidc rule.
- **Enabled providers**: whether the provider appears as a sign-in option on the login page — fill the required fields and click **Enable**; **Disable** removes it from the login page.
- **Redirect URI**: the callback URL the identity provider redirects back to after authorization — copy the URI shown by Coolify into the OAuth application at the provider, and save the same value in Coolify if overridden; usually `https://<dashboard-domain>/auth/<provider>/callback`.
- **Client ID**: create the OAuth application with the provider, copy its client ID, paste it into **Client ID**, then **Save changes**.
- **Client Secret**: copy the provider client secret, paste it into **Client Secret**, then **Save changes**.
- **Base URL**: the provider base URL for self-hosted or issuer-based OAuth providers — a full URL from the provider (e.g. `https://auth.<domain>`).
- **Tenant**: tenant information for providers that support or require tenant scoping — Azure requires a tenant value; Google uses a **Hosted domain** field restricting sign-in to the configured Workspace domain (`*` allows any Workspace account).

## Instance environment settings

- `HORIZON_ALLOWED_EMAILS` in the instance environment grants additional users access to Laravel Horizon (see the upstream contribute guide for the Horizon URL).
- `SSH_COMMAND_TIMEOUT` defaults to `3600` seconds; `0` or an invalid value uses that default rather than disabling the timeout.
- `PROXY_CONNECT_NETWORKS_INTERVAL_SECONDS` and `NIGHTWATCH_ENABLED` are removed: proxy networks are connected during destination creation, deployment, and proxy startup/restart, and Laravel Nightwatch no longer runs inside the Coolify container.
