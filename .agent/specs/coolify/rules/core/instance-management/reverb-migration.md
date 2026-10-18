# Core › Instance Management › Reverb Migration

Source: coollabsio/coolify-docs `content/docs/core/instance-management/reverb-migration.mdx` @ main `3b7c416`.

## Scope

- New Coolify releases run Laravel Reverb and the web terminal server inside the main `coolify` container — they no longer use the separate `coolify-realtime` container.
- Most installations do not need a manual change. Review your configuration before the update if you use a custom Compose override, custom realtime ports, a firewall, or a Cloudflare Tunnel.
- The update accepts a `soketi` override in `docker-compose.custom.yml` — existing proxy routes and port settings remain compatible. You can move custom port bindings to `coolify` to make the override reflect the new container layout.

## What changes

| Before | After |
| --- | --- |
| Soketi listens in `coolify-realtime` | Reverb listens in `coolify` |
| The terminal server listens in `coolify-realtime` | The terminal server listens in `coolify` |
| The proxy sends realtime traffic to `coolify-realtime:6001` | The proxy sends realtime traffic to `coolify:6001` |
| The proxy sends terminal traffic to `coolify-realtime:6002` | The proxy sends terminal traffic to `coolify:6002` |

- External paths do not change: `/app/*` (browser WebSocket connections), `/apps/*` (Pusher-compatible API requests), `/terminal/ws` (web terminal connections).
- The update keeps the existing Pusher application ID, key, and secret, and keeps `SOKETI_PORT` as the compatible Docker host-port setting — do not rename that variable during this migration.
- The update script removes the old `coolify-realtime` container. If `.env` still contains `PUSHER_BACKEND_HOST=coolify-realtime`, the script changes it to `127.0.0.1` (Reverb now runs in the same container as Coolify).
- The `coolify` container also has `coolify-realtime` as a network alias on the `coolify` Docker network — a reverse proxy that still sends traffic to `coolify-realtime:6001` or `coolify-realtime:6002` keeps working.

## Before you update

- Back up the instance configuration:

```sh
cd /data/coolify/source
cp .env .env.before-reverb

if [ -f docker-compose.custom.yml ]; then
  cp docker-compose.custom.yml docker-compose.custom.yml.before-reverb
fi
```

- Check for a Soketi service override — if the command prints a line, review the override; migrating it is optional because legacy `soketi` overrides are still accepted:

```sh
grep -nE '^[[:space:]]*soketi:' docker-compose.custom.yml 2>/dev/null || true
```

- Record custom realtime settings and keep the output until verification is finished:

```sh
grep -E '^(PUSHER_HOST|PUSHER_PORT|PUSHER_SCHEME|PUSHER_BACKEND_PORT|SOKETI_PORT|TERMINAL_PORT)=' .env || true
```

## Migrate a custom Compose override

- The old firewall example configured ports on two services:

```yaml
services:
  coolify:
    ports: !override
      - "127.0.0.1:${APP_PORT:-8000}:8080"
  soketi:
    ports: !override
      - "127.0.0.1:${SOKETI_PORT:-6001}:6001"
      - "127.0.0.1:6002:6002"
```

- Move all three bindings to the `coolify` service:

```yaml
services:
  coolify:
    ports: !override
      - "127.0.0.1:${APP_PORT:-8000}:8080"
      - "127.0.0.1:${SOKETI_PORT:-6001}:6001"
      - "127.0.0.1:6002:6002"
```

- The web terminal always uses host port `6002` — `TERMINAL_PORT` does not change the Docker host port.
- If your override removed all public bindings, replace the two-service `ports: !override []` form with a single `coolify` service entry:

```yaml
services:
  coolify:
    ports: !override []
```

- Move only port bindings that are still required. Remove Soketi-specific image, command, environment, health-check, and volume settings; remove the `soketi` service block when it is empty.
- WARNING: keep ports `8000`, `6001`, and `6002` reachable until the dashboard, realtime updates, and terminal work through your domain — restrict them after you complete the verification.

## Environment variables

| Variable | Purpose |
| --- | --- |
| `SOKETI_PORT` | Compatible Docker host-port override for origin port `6001` |
| `REVERB_PORT` | Optional; takes precedence over `SOKETI_PORT` for the Docker host port of origin port `6001` — not needed when you already use `SOKETI_PORT` |
| `PUSHER_PORT` | Public port used by the browser |
| `PUSHER_BACKEND_PORT` | Internal Reverb listen port, normally `6001` |
| `PUSHER_BACKEND_HOST` | Host Coolify uses to send events to Reverb, normally unset or `127.0.0.1` |
| `TERMINAL_PORT` | Public browser port for the web terminal (e.g. `443` behind a reverse proxy); does not change the Docker host port, which is always `6002` |

- Do not change an existing `SOKETI_PORT` value — existing firewall rules and port forwarding can depend on it.
- Dashboard behind Cloudflare: the public browser port is normally `443` — set `PUSHER_PORT=443` and `PUSHER_BACKEND_PORT=6001`. Do NOT set `PUSHER_PORT=6001` for a Cloudflare-proxied hostname: `6001` is the origin port and the browser connects to Cloudflare on `443`.
- Without an explicit `PUSHER_PORT`, leave it unset — Coolify selects the public port from the dashboard request and keeps direct IP access compatible.

## Cloudflare configurations

- Dashboard through the Coolify proxy (recommended): send the dashboard hostname to the Coolify proxy on port `80` or `443`; the generated proxy configuration routes `/app/*` and `/apps/*` to `coolify:6001`, `/terminal/ws` to `coolify:6002`, and `/*` to `coolify:8080` — no separate Cloudflare routes for Reverb or the terminal are needed.
- For a Cloudflare Tunnel that terminates TLS at Cloudflare, set the tunnel service URL to `http://localhost:80`; for end-to-end TLS, use the HTTPS origin configuration from the upstream Full TLS guide (`/integrations/networking/cloudflare/tunnels/full-tls`).
- Separate realtime hostname (older tunnel configurations) remains compatible — keep `PUSHER_HOST=coolify-realtime.example.com`, `PUSHER_PORT=443`, `PUSHER_BACKEND_PORT=6001`; the Cloudflare route still connects to origin port `6001`, but Reverb now handles that port in the `coolify` container.

## Update and verify

- Update Coolify from **Settings > Updates** — existing realtime routes, port settings, and legacy `soketi` overrides remain compatible. Keep the configuration backups until you have verified the dashboard, realtime updates, and terminal.
- If you migrate an override manually, check that all required port bindings are now on `coolify`; review the update log in `/data/coolify/source/upgrade-*.log` if the update reports a configuration error.
- After the update, run:

```sh
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
docker logs --tail 100 coolify
```

- Confirm: `coolify`, `coolify-db`, and `coolify-redis` are running; the `coolify-realtime` container is gone; the dashboard loads through its normal URL; browser developer tools show a successful WebSocket request to `/app/...` using `wss` and public port `443` when Cloudflare serves the dashboard over HTTPS; a deployment or status change appears without a page refresh; the web terminal connects through `/terminal/ws`.
- You can also open `/realtime` in two browser tabs and send a test event.

## Troubleshoot

- The `coolify` container stays unhealthy → the container healthcheck now includes Reverb; check the Reverb log inside the container and confirm nothing else listens on port `6001`:

```sh
docker exec coolify curl --fail http://127.0.0.1:6001/up
sudo ss -tulpn | grep :6001
```

  The dashboard keeps working while the container is unhealthy — only realtime updates stop.
  
- Cloudflare returns an error for the WebSocket request → confirm the browser connects to public port `443` (not origin port `6001`), Cloudflare WebSockets are enabled for the zone, the tunnel or proxy route includes `/app/*`, and the Coolify proxy can reach `coolify:6001` on the `coolify` Docker network.
- The dashboard works but realtime updates do not → check `PUSHER_HOST` and `PUSHER_PORT` in `/data/coolify/source/.env`; remove obsolete overrides when the dashboard and Reverb use the same hostname, or keep port `443` for a separate Cloudflare realtime hostname. Also confirm `PUSHER_BACKEND_HOST` is unset or `127.0.0.1` — a public hostname in that variable sends server-side events to the wrong place.
- Direct IP access stops receiving updates → confirm the `coolify` container publishes host port `${REVERB_PORT:-${SOKETI_PORT:-6001}}` to container port `6001` and host port `6002` to container port `6002`, and that the server firewall permits these host ports from your client address.
