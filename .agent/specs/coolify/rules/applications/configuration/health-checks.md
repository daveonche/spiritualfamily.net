# Applications › Configuration › Health Checks

Source: coollabsio/coolify-docs `content/docs/applications/configuration/health-checks.mdx` @ main `3b7c416`.

## Where checks live

- **Healthcheck** page: HTTP or CMD without image changes (Nixpacks, Railpack, Static, Dockerfile, Docker Image apps).
- Dockerfile `HEALTHCHECK`: travels with the image.
- Compose `healthcheck`: per service; Compose apps have no standard Healthcheck page.
- Non-Compose Dockerfile app: Coolify detects the image `HEALTHCHECK` and uses it INSTEAD of the dashboard check; remove the instruction + redeploy to switch.
- Traefik (Coolify Proxy) removes unhealthy containers from routing; all unhealthy → `404` / `No available server`.

## HTTP check

- Runs INSIDE the container — the final image must contain `curl` or `wget` (installing on the server is not enough).
- Method GET/POST; Scheme http/https; Host normally `localhost`; Port empty = first **Ports Exposes** value; static apps use port 80; Path e.g. `/health`.
- Verify: `command -v curl || command -v wget`, then `curl --fail http://localhost:3000/health` (exit 0).
- Current validation uses exit status ONLY — **Expected code** / **Expected response text** are not compared.

## CMD check

- Command must exist in the container; exit 0 = ready. No shell operators (`;`, `|`, `&`, `$()`, `>`, `<`) — put multi-step logic in a script inside the image.
- Examples: `pg_isready -U postgres`, `node healthcheck.js`.

## Timing (both types)

- **Interval** ≥ 1s; **Timeout** ≥ 1s; **Retries** ≥ 1; **Start period** before failures count — size it for migrations/cache warmup.

## Enable

- **Save changes** → **Enable** → confirm; restart or redeploy so the container is recreated with the check.

## Dockerfile / Compose definitions

- `HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD curl --fail http://localhost:3000/health || exit 1`; every referenced binary must be in the final stage; use the internal port.
- Compose `healthcheck` overrides the image `HEALTHCHECK` for that service; omit the block to inherit.

## Rolling updates

- The check lets Coolify wait for the replacement before removing the current container; a failed replacement is removed and the healthy version keeps running.

## Troubleshoot

- Terminal → run the same request/command → confirm the binary exists → confirm the INTERNAL port → inspect Runtime Logs.
