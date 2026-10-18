# Core › Networking › Proxy › Traefik › Custom middlewares

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/custom-middlewares.mdx` @ main `3b7c416`.

## Scope

- Traefik middleware changes a request before it reaches a resource: rate limits, response headers, IP allow lists, redirects, external authentication.

## Prefer dynamic configuration over manual labels

- Choosing **Managed manually (edit labels yourself)** stops Coolify regenerating application labels — you become responsible for every router, middleware, service, and certificate label. Use dynamic configuration with a small reference label when it can meet the requirement.

## Define reusable middleware (file-based)

- **Servers > server > Proxy > Dynamic Configurations** → **Add** → e.g. `shared-middlewares.yaml`; combine multiple definitions in one file.
- Rate limiting: `http.middlewares.app-rate-limit.rateLimit` → `average: 100`, `period: 1m`, `burst: 50`.
- Security headers: `headers` → `contentTypeNosniff: true`, `frameDeny: true`, `stsSeconds: 31536000`, `stsIncludeSubdomains: true`.
- IP allow list: `ipAllowList.sourceRange: ['192.168.1.0/24', '10.0.0.0/8']`.
- The `@file` suffix identifies middleware loaded from Traefik's file provider.

## Attach — standard application

- **General > Container labels** → **Managed manually** → append middleware names to the existing HTTPS router label:
  - `traefik.http.routers.https-0-<resource-id>.middlewares=gzip` → `gzip,app-rate-limit@file,security-headers@file`
- Keep every existing middleware name. Save + redeploy.
- If the route breaks: **Reset to defaults**, set **Label management** back to **Managed by Coolify (auto-generated)**, redeploy before trying again.

## Attach — Docker Compose or service

- Add the Coolify shorthand to the service receiving the domain: `coolify.traefik.middlewares=app-rate-limit@file,security-headers@file`; Coolify adds the named middleware while generating the router. Save + redeploy.
- Labels alternative: `traefik.http.middlewares.app-rate-limit.ratelimit.average=100` / `.period=1m` / `.burst=50` + `coolify.traefik.middlewares=app-rate-limit`.
- File-based when several resources share the middleware; labels when it belongs to one Compose service.

## Redirects — www / non-www

- Standard applications: built-in **www redirect** on **Domains** (add both hostnames, keep labels managed by Coolify): **No redirect** (both available) / **Redirect to www** / **Redirect to non-www**. Save + redeploy if the running container lacks regenerated labels; both hostnames need DNS records pointing at the server.
- Compose: add both domains + redirect middleware labels on the receiving service:
  - to www: `redirectregex.regex=^(http|https)://(?:www\.)?(.+)`, `redirectregex.replacement=${1}://www.${2}`, `redirectregex.permanent=true`, `coolify.traefik.middlewares=example-redirect`
  - to non-www: `regex=^(http|https)://www\.(.+)`, `replacement=${1}://${2}`
- Assumes **Escape special characters in labels?** enabled; otherwise write each `$` as `$$` yourself.

## Redirects — one domain to another

- Dynamic middleware preserving the path: `redirectRegex` → `regex: '^https?://source\.example\.com/(.*)'`, `replacement: 'https://target.example.com/${1}'`, `permanent: true`.
- Configure `https://source.example.com` as a domain on the resource so Traefik creates the source router + certificate; attach `move-domain@file` via the appropriate method.
- Use `permanent: false` while testing — browsers and search engines can cache permanent redirects.

## Troubleshoot

- Traefik cannot find the middleware → name + provider suffix must match exactly (`@file` for file-provider); inspect **Proxy > Logs** for missing-middleware/YAML errors.
- Defined but does not change requests → confirm it is attached to the expected router; defining without a router processes no traffic.
- Existing middleware stops working → keep existing names (e.g. `gzip`); append instead of replacing the complete value.
- Label with dollar signs invalid → with escaping enabled enter each `$` once (Coolify writes `$$` in the deployable file; `docker inspect` should show `${1}://${2}`); with escaping disabled write `$$` yourself.
