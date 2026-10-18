# Integrations › Networking › Cloudflare › Tunnels › Single resource

Source: coollabsio/coolify-docs `content/docs/integrations/networking/cloudflare/tunnels/single-resource.mdx` @ main `3b7c416`.

## Prepare the app

1. Remove all domains from the **Domains** field.
2. Set **Ports Exposed** to the app's internal port.
3. Set **Port Mappings** `host:container` (example `4477:80`).
4. **Deploy**.

## Create the tunnel and deploy cloudflared

- Cloudflare **Networking > Tunnels > Create tunnel** > Docker option > copy the token (`eyJ...`).
- Coolify project > **+ New** > **Cloudflared** > Environment Variables > token > deploy; confirm the connector is healthy.

## TLS

- Cloudflare **SSL/TLS > Overview > Configure** → **Full (Strict)** → **Save**.

## Configure the route

- Tunnel > **Configure** > **Routes** > **Add route** > **Published Application**: subdomain (e.g. `helixa`), domain, path empty, **Service URL** `http://localhost:4477` — the HOST port from the port mapping, not the container port.

## Tunnel multiple resources

- Easiest: the all-resource guide via the Coolify proxy.
- Multiple single resources: repeat the prepare step per app, then add one route per app pointing at its mapped host port — no separate tunnel needed.
- Tunnel Coolify (dashboard + realtime + terminal through one route):
  1. Proxy running on the Coolify server.
  2. **Settings > General**: set the instance **URL** to the dashboard domain; disable **Redirect HTTP to HTTPS** (the tunnel connects over HTTP — otherwise a redirect loop).
  3. Coolify generates proxy routes from the instance URL: `/app/*` + `/apps/*` → `coolify:6001`, `/terminal/ws` → `coolify:6002`, `/*` → `coolify:8080`.
  4. One route: `coolify.example.com` → `http://localhost:80`. No separate routes for 6001/6002; no `PUSHER_HOST`/`PUSHER_PORT`/`TERMINAL_HOST`/`TERMINAL_PORT` overrides needed.
  5. Switching from separate routes: remove obsolete `PUSHER_*`/`TERMINAL_*` overrides from `/data/coolify/source/.env` (they take precedence over automatic detection), then restart Coolify (`curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash`).
  6. Verify: dashboard loads; `https://coolify.example.com/realtime` shows a test event; network tab shows realtime/terminal WebSockets on the dashboard hostname.
  - Legacy separate routes still work: `/terminal/ws` → `127.0.0.1:6002`, realtime hostname → `127.0.0.1:6001`, dashboard → `127.0.0.1:8000`; keep the path-specific terminal route ABOVE the dashboard route. A separate realtime hostname needs `PUSHER_HOST=<realtime domain>` + `PUSHER_PORT=443` in `.env` (browser-facing; do NOT set `PUSHER_BACKEND_HOST` to it).
- Ports 8000/6001/6002 stay internal — the tunnel connects from inside the server; no public exposure needed.

## Known issues

- Realtime/terminal fail with `localhost` → the server resolves `localhost` to IPv6 `::1` while the origin listens on IPv4: change the service URL to `http://127.0.0.1:<port>` (HTTPS origin: `https://127.0.0.1:443` + **Origin Server Name** set to the certificate hostname).
- Existing DNS record blocks the route → CNAME record `<subdomain or *>` → `<tunnel-id>.cfargotunnel.com`, proxied.
