# Core › Networking › Proxy › Caddy › DNS challenge

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/caddy/dns-challenge.mdx` @ main `3b7c416`.

## When and requirements

- Default HTTP challenge needs public port 80; use the DNS challenge for wildcard certificates or when port 80 cannot be exposed during validation.
- Domain hosted by a [Caddy DNS provider](https://github.com/orgs/caddy-dns/repositories) + provider credentials that create/delete DNS records.
- DNS providers are modules compiled into the Caddy binary; the default `lucaslorentz/caddy-docker-proxy:2.13-alpine` image includes none → build a custom image from the proxy Compose config.
- Build and test on a NON-PRODUCTION server first: the proxy image serves every public route; a failed module build or invalid option can leave all routes unavailable.

## Build (Servers > server > Proxy > Configuration, `caddy` service)

- Remove the `image:` line; add `build: dockerfile_inline:`:
  - `FROM caddy:2.11-builder AS builder` → `RUN xcaddy build --with github.com/lucaslorentz/caddy-docker-proxy/v2@v2.8.11 --with github.com/caddy-dns/<provider>@<version>` → `FROM caddy:2.11-alpine` → `COPY --from=builder /usr/bin/caddy /usr/bin/caddy` → `CMD ["caddy", "docker-proxy"]`.
- Keep existing networks, ports, volumes, Coolify labels; add environment: `CADDY_DOCKER_POLLING_INTERVAL=5s`, `CADDY_DOCKER_CADDYFILE_PATH=/dynamic/Caddyfile`, plus the provider token — `CF_API_TOKEN` (Cloudflare), `HETZNER_API_TOKEN` (Hetzner), or `AWS_ACCESS_KEY_ID` + `AWS_SECRET_ACCESS_KEY` + `AWS_REGION` (Route 53).
- Add label `caddy.acme_dns=<provider> {env.<TOKEN>}` (Route 53: plain `caddy.acme_dns=route53`).
- Known pairings: cloudflare@v0.2.4, hetzner/v2@v2.0.0, route53@v1.6.2. Other providers: use that provider's module path + configuration; confirm the module version supports the selected Caddy version before restarting.

## Restart and verify

- Save → **Restart Proxy**; the first restart builds the custom image and takes longer.
- Redeploy a resource with an HTTPS domain; inspect its certificate; check **Servers > server > Proxy > Logs** for image-build, module, DNS, or ACME errors.

## Troubleshoot

- Unknown DNS provider → module not compiled into the running binary; confirm the `xcaddy build` `--with` module, rebuild, restart.
- DNS validation times out → confirm the token can edit the correct zone and `_acme-challenge` TXT records become publicly visible; follow the provider module's propagation settings.
- Let's Encrypt rate limits while testing → add label `caddy.acme_ca=https://acme-staging-v02.api.letsencrypt.org/directory`; REMOVE it after validation succeeds — staging certificates are not browser-trusted.
