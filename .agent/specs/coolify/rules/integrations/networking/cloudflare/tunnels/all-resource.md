# Integrations › Networking › Cloudflare › Tunnels › All resources

Source: coollabsio/coolify-docs `content/docs/integrations/networking/cloudflare/tunnels/all-resource.mdx` @ main `3b7c416`.

## Who this is for

- No public IP for the server (laptop, Raspberry Pi), unable to port forward, keep the server IP private, or reach an already-deployed Coolify resource securely.
- Requirements: free Cloudflare account + a domain with DNS managed by Cloudflare.
- Traffic path: HTTPS visitor → Cloudflare → tunnel → HTTP `localhost:80` → Coolify proxy → resource.

## Create the tunnel

1. Cloudflare dashboard > **Networking > Tunnels > Create tunnel**; enter a name.
2. Select the **Docker** option; copy the token from the `docker run` command (starts with `eyJ`); store it safely.

## Deploy cloudflared on Coolify

- Project > **+ New** > search **Cloudflared** > **Environment Variables** > enter the tunnel token > deploy.
- Confirm the connector appears healthy on the Cloudflare tunnel page.

## Start the Coolify proxy

- **Servers** > select the server > confirm the proxy shows running.

## Configure the tunnel route

1. Tunnel > **Configure** > **Routes** > **Add route** > **Published Application**.
2. **Subdomain** `*` (wildcard), **Domain** = your domain, **Path** empty, **Service URL** `http://localhost:80`.
3. If `localhost` selects an IPv6 address the origin cannot accept, use `http://127.0.0.1:80`.

- The Coolify proxy on `http://localhost:80` routes to every app on the server — no new tunnel routes per deployment.

## Configure the resource domain

- Set the resource domain as HTTPS (e.g. `https://app.example.com`) and deploy.
- **Domains** page: set **Redirect HTTP to HTTPS** = **Disabled** — the tunnel connects over HTTP; leaving it enabled causes **TOO_MANY_REDIRECTS**. Keep it enabled only for normal orange-cloud proxying where Cloudflare connects over HTTPS (Full/Full Strict), or follow the full-tls guide with an HTTPS service URL.

## Multiple domains

- No new tunnels: add a route for the new domain pointing at `http://localhost:80`.

## Known issue: existing DNS record

- Cloudflare creates the DNS record for a new route but will not update an existing one; the app breaks.
- Fix: tunnel > **Configure** > copy the tunnel ID > **DNS > Records** > add a **CNAME** record named `*` (or the subdomain) → target `<tunnel-id>.cfargotunnel.com`, proxy status **Proxied**.
