# Integrations › Networking › Cloudflare › Tunnels › Server SSH

Source: coollabsio/coolify-docs `content/docs/integrations/networking/cloudflare/tunnels/server-ssh.mdx` @ main `3b7c416`.

## Purpose

- Connect a remote server to Coolify over SSH through a Cloudflare Tunnel: hides the server IP, allows closing SSH ports, works without a static public IP (manual setup).
- NOT needed for the server Coolify itself runs on — Coolify already has root access there.

## Automated setup (server has a public IP + active SSH port during setup)

1. **Keys & Tokens > Private Keys > New private key** → **Generate ED25519**/**Generate RSA** (saved immediately); name it; copy the **Public key**.
2. SSH into the remote server: `echo "<public key>" >> ~/.ssh/authorized_keys`.
3. **Servers > New server > IP address or domain**: name, description, the server's public IP, **Port** (advanced), **User** with root privileges, the private key → **Continue**.
4. **Validate connection** — Coolify logs in and prepares everything; the server page shows all checks green.
5. Cloudflare: **Networking > Tunnels > Create tunnel** > Docker option > copy the token (`eyJ...`).
6. Server page > **Cloudflare Tunnel** > **Automated setup**: enter the **Cloudflare token** + **SSH domain** → **Configure tunnel**; confirm cloudflared shows healthy in Cloudflare.
7. Tunnel > **Configure** > **Routes** > **Add route** > **Published Application**: subdomain (e.g. `helixa`), domain, path empty, **Service URL** `ssh://localhost:22` (the server's actual SSH port when different).
8. Coolify automatically rewrites the server IP to the SSH domain; if not, change it manually under the server's **General**.

- The SSH port can be closed on the server afterward.

## Manual setup (no public IP required)

1. Create the private key in Coolify and add its public key to the server's `authorized_keys` (steps 1–2 above).
2. **Servers > New server**: enter the **SSH domain** (e.g. `helixa.shadowarcanist.com`) as the address, port, user, key → **Continue**.
3. Server > **Cloudflare Tunnel**: complete the tunnel installation + route configuration in the following steps BEFORE marking the manual setup complete.
4. Cloudflare: create the tunnel; run the `docker run` command ON the remote server (add `-d` to keep cloudflared running after the terminal closes).
5. Route: **Published Application** → subdomain, domain, **Service URL** `ssh://localhost:22`.
6. Back on the server's **Cloudflare Tunnel** page: unvalidated server → **mark a manual configuration as complete**; already validated → **I configured the tunnel manually** under **Manual setup**.
7. **General > Validate connection**; Coolify logs in and prepares the server.

## Known issue: existing DNS record

- Cloudflare will not update an existing DNS record for the hostname: create a **CNAME** record (name = the subdomain) → `<tunnel-id>.cfargotunnel.com`, proxy status **Proxied**.
