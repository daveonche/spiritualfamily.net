# Core › Networking › Proxy › Traefik › Basic authentication

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/basic-auth.mdx` @ main `3b7c416`.

## What it protects

- HTTP basic auth requires a username + password before Traefik forwards a request; ONE shared credential — it does not replace application accounts, role-based access, MFA, or SSO.

## Standard application (automatic)

- Application > **General** > **Security > Authentication** → **HTTP Basic Authentication** → **Username** + **Password** → save.
- Keep **Label management** = **Managed by Coolify (auto-generated)** so Coolify keeps generating the proxy labels.
- Redeploy; verify in a private browser window that credentials are requested before the application loads.

## Manual (standard application)

- Generate a bcrypt credential: `htpasswd -nbB <username> '<password>'` (Debian/Ubuntu: `apache2-utils` package).
- **General > Container labels** → **Label management** → **Managed manually (edit labels yourself)**.
- Two labels: one DEFINES the middleware, one ATTACHES it to the HTTPS router:
  - `traefik.http.middlewares.http-basic-auth.basicauth.users=<username>:<bcrypt-hash>`
  - `traefik.http.routers.https-0-<uuid>.middlewares=http-basic-auth`
- Keep every other generated label (`traefik.enable`, the redirect-to-https middleware, both routers' entryPoints/rule/service/tls labels, both services' loadbalancer ports).
- If the HTTPS router already lists middleware (e.g. `gzip`), append `,http-basic-auth` instead of replacing the value.
- Manual label management makes you responsible for every generated route/middleware label (see `custom-middlewares.md`). Redeploy; verify in a private browser window.

## Docker Compose or service

- Generate the credential with `htpasswd -nbB`; output form `<username>:<bcrypt-hash>`.
- Keep each `$` ONCE in the source Compose file while **Escape special characters in labels?** is enabled (default) — Coolify writes the required `$$` form in the deployable Compose file.
- Add to the service receiving the domain:
  - `traefik.http.middlewares.app-auth.basicauth.users=<username>:<bcrypt-hash>`
  - `coolify.traefik.middlewares=app-auth`
- `coolify.traefik.middlewares` tells Coolify to add `app-auth` to the generated router middleware chain. Save, redeploy, verify in a private browser window.

## Secret handling

- Never store the plaintext password in labels: use only the `htpasswd` output; keep the plaintext in a password manager; rotate by replacing the hash and redeploying the resource.

## Troubleshoot

- No login prompt → resource not redeployed after enabling auth/changing labels; for Compose inspect the running container labels and confirm `app-auth` is attached to the expected router; check **Servers > server > Proxy > Logs** for missing-middleware errors.
- Traefik reports an invalid label → keep **Escape special characters in labels?** enabled and enter each `$` once; if disabled, write every `$` as `$$` yourself; check label quoting and keep `<username>:<bcrypt-hash>` on one line.
- Another middleware stops working → keep every existing middleware name; append `app-auth` to the chain instead of replacing `gzip` or another custom middleware.
