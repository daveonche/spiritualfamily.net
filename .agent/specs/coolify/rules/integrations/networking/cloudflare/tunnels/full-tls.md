# Integrations › Networking › Cloudflare › Tunnels › Full TLS

Source: coollabsio/coolify-docs `content/docs/integrations/networking/cloudflare/tunnels/full-tls.mdx` @ main `3b7c416`.

## When

- Apps that require HTTPS on Coolify itself (JWT issuance, callback URLs) — Cloudflare normally terminates TLS while Coolify serves HTTP.
- Prerequisite: a working tunnel setup (all-resource or single-resource guide); before starting, confirm dashboard access via `server-ip:8000` (default port) or the changed/disabled port.

## Origin certificate choice

| | Cloudflare Origin Certificate | Public CA Certificate |
| --- | --- | --- |
| Trusted by | Cloudflare only | Browsers, OSs, Cloudflare |
| Requires CF proxy | Yes | No |
| DNS challenge | No | Yes |

- Always behind Cloudflare → Origin Certificate; otherwise → Public CA (Traefik/Caddy DNS-challenge guides).

## Create a Cloudflare Origin Certificate

- **SSL/TLS > Origin Server > Origin Certificates > Create Certificate**: RSA (2048); hostnames — `example.com` covers only the apex, `*.example.com` covers one subdomain level (NOT `www.app.example.com`; deeper coverage needs Advanced Certificate Manager); validity 15 years; **PEM** format; copy the certificate + private key to a safe place.

## Install on the server

- SSH or Coolify terminal; `cd /data/coolify/proxy`.
- Traefik: `mkdir certs` (sits beside `acme.json`, `docker-compose.yml`, `dynamic`). Caddy: `mkdir -p caddy/data/certs`.
- `touch example.com.cert example.com.key`; paste certificate + key into the files (e.g. `nano`).

## Configure the proxy

- **Servers > server > Proxy > Dynamic Configurations > Add**:
  - Traefik (`.yaml`): `tls.certificates: [- certFile: /traefik/certs/example.com.cert, keyFile: /traefik/certs/example.com.key]` — multiple certs = multiple list entries.
  - Caddy (`.caddy`): `*.example.com, example.com { tls /data/certs/example.com.cert /data/certs/example.com.key }` — drop the wildcard for a single domain; multiple blocks for multiple certs.
- Coolify uses the origin certificate for matching hostnames from now on.

## Cloudflare settings

- **SSL/TLS > Overview > Configure** → **Full (Strict)** → **Save**.
- **SSL/TLS > Edge Certificates** → enable **Always Use HTTPS**.

## Point the tunnel at HTTPS

- Tunnel > route > **Edit route**: service URL `http://localhost:80` → `https://localhost:443`.

## Troubleshoot: 502 after switching to HTTPS

- The origin certificate covers your domain, but cloudflared connects to `localhost` and the hostname check fails.
- Fix: route > **Edit route** > keep `https://localhost:443` > **Additional application settings > TLS** > **Origin Server Name** = a concrete hostname covered by the certificate (e.g. `coolify.example.com`, NOT `*.example.com`) > keep **Disable TLS certificate verification** OFF > save. Repeat per HTTPS route with a hostname its origin serves. Changing the zone SSL/TLS mode does not fix a hostname mismatch.

## Finish

- Update every Coolify URL from HTTP to HTTPS: resource domains + the instance domain in settings.
