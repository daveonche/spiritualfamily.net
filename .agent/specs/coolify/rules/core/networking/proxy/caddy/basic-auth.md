# Core › Networking › Proxy › Caddy › Basic authentication

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/caddy/basic-auth.mdx` @ main `3b7c416`.

## What it protects

- HTTP basic auth requires a username + password before Caddy forwards the request; it is ONE shared credential — it does not replace application accounts, role-based access, MFA, or SSO.

## Standard application (automatic)

- Application > **General** > **Security > Authentication** → **HTTP Basic Authentication** → **Username** + **Password** → save → **redeploy**; verify in a private browser window.
- Coolify hashes the password and adds a `caddy_<index>.basicauth.<username>` label for each generated Caddy route.

## Manual (any resource)

- Generate the hash: `caddy hash-password --plaintext '<password>'`.
- Standard app: **General > Container labels** → **Label management** → **Managed manually (edit labels yourself)**; add `caddy_0.basicauth.<username>="<bcrypt-hash>"` keeping the other generated labels (`caddy_0.encode`, `caddy_0.handle*`, `caddy_0.header`, `caddy_0.try_files`, `caddy_0=https://domain.com`, `caddy_ingress_network`).
- Docker Compose/service: add the label to the service; the `caddy_0` prefix must match the generated Caddy route index for the domain being protected.
- Redeploy and verify in a private browser window.

## Hash rule

- Never place the plaintext password in the label; Caddy expects a supported password hash — `caddy hash-password` generates the correct format.

## Troubleshoot

- Prompt does not appear → the resource was not redeployed after enabling authentication or changing labels.
- Credentials rejected → generate a NEW hash with `caddy hash-password`, replace the label value, redeploy; never use plaintext or a hash generated for a different authentication format.
- Label not applied → the `caddy_0` route index must match the domain label on the running container; inspect **Servers > server > Proxy > Logs** for Caddyfile adaptation or authentication errors.
