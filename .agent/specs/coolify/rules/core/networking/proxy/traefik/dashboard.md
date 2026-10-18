# Core › Networking › Proxy › Traefik › Dashboard

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/dashboard.mdx` @ main `3b7c416`.

## Prerequisites

- New proxy configurations do not expose the dashboard; the upgrade removes the legacy dashboard router — restart the proxy to apply. Create a dynamic router for HTTPS + basic auth.
- Use a DEDICATED hostname (e.g. `traefik.example.com`): Traefik's dashboard uses `/api`, which conflicts with Coolify's API routes — never share the Coolify dashboard hostname.

## Credentials

- `htpasswd -nbB <username> '<password>'`; copy the complete `<username>:<hash>`. Dynamic YAML needs no Docker Compose dollar-sign escaping.

## Enable the dashboard API

- Proxy configuration: `--api.dashboard=true` (secure mode); keep insecure mode disabled. Save the validated configuration → **Restart Proxy**.
- Edit the main configuration in Coolify (**Servers > server > Proxy > Configuration**) — disk edits to `/data/coolify/proxy/docker-compose.yml` are not synced back and get overwritten on restart.

## Add the dashboard route

- **Dynamic Configurations** → **Add** → `traefik-dashboard.yaml`:
  - middlewares: `dashboard-auth` (`basicAuth.users: ['<username>:<bcrypt-hash>']`), `dashboard-https-redirect` (`redirectScheme.scheme: https`).
  - router `dashboard-http`: rule `Host(`traefik.example.com`)`, entryPoints `[http]`, middlewares `[dashboard-https-redirect]`, service `api@internal`.
  - router `dashboard-https`: rule `Host(...) && (PathPrefix(`/api`) || PathPrefix(`/dashboard`))`, entryPoints `[https]`, middlewares `[dashboard-auth]`, service `api@internal`, `tls.certResolver: letsencrypt`.
- Replace hostname + credential, save; point the hostname's DNS record at this server.
- Open `https://traefik.example.com/dashboard/` (trailing slash) and sign in.

## Never use insecure mode on a public server

- `--api.insecure=true` exposes the dashboard directly on Traefik's API port without authentication; if the config publishes port 8080, insecure mode can expose the dashboard through the firewall.
- Use the authenticated HTTPS route; if temporarily enabling insecure mode for isolated debugging, restrict port 8080 at the firewall and remove the flag immediately afterward.

## Troubleshoot

- Page/assets do not load → open `/dashboard/` WITH the trailing slash (redirects + assets expect it); inspect **Proxy > Logs** for dynamic-config parsing errors.
- Conflicts with the Coolify API → both products use `/api`; they cannot share a hostname with this router configuration — use a different hostname.
- Hostname does not resolve → DNS record must point at the server running this Traefik proxy; ports 80/443 must reach the server.
