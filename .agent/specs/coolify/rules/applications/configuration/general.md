# Applications › Configuration › General

Source: coollabsio/coolify-docs `content/docs/applications/configuration/general.mdx` @ main `3b7c416`.

## Settings by deployment method

- Nixpacks/Railpack: build commands, repo paths, static-site controls, publish directory, domains, registry output, network, labels.
- Static: static server image, repo root, custom Nginx config, domains, registry, network, labels.
- Dockerfile from Git: repo root, Dockerfile path, optional build stage. Without Git: stored Dockerfile field, no **Build strategy** selector.
- Docker Compose from Git: Compose paths, per-service domains, parsed content, repo preservation, optional commands, label escaping. Compose apps do NOT use single-container **Network**, **HTTP Basic Authentication**, or editable **Active labels**.
- Docker Image: image name, tag/hash, container options, domains, network, labels; no **Build strategy** selector.

## General tab

- **Name** required; **Description** optional. **Domains** are managed on the Domains page (Protocol, Domain, optional internal Port, optional Path; Compose adds **Service**) — not a comma-separated list here. A domain port selects a container port; it does not publish it.

## Build tab

- **Build strategy** selector unavailable for Docker Image and Dockerfile-without-Git apps.
- Nixpacks/Railpack: Install/Build/Start commands detected; override only when unsuitable and keep the pack config consistent. **Base Directory** `/` or `/apps/api` (monorepo). Static site type: **SPA** for client-route fallback; **Publish Directory** required; optional **Custom Nginx configuration**.
- Static: web server image `nginx:alpine`; **Generate default** overwrites the custom Nginx config.
- Dockerfile: resolved path = Base Directory + Dockerfile Location (both begin `/`); Base Directory defines the build context for `COPY`/`ADD`; **Docker Build Stage Target** empty = final stage.
- Docker Compose: **Load/Reload Compose File** parses; raw vs deployable content is read-only — edit in Git; **Preserve repository during deployment** for runtime repo-relative files; Custom Build/Start Command: Coolify adds `-f`/`--env-file` unless the command supplies its own; keep paths relative to Base Directory and use the parsed file.
- Docker Image: **Image** (`ghcr.io/example/api`), **Tag** (pin a version/digest for immutability), **Custom Docker Options**.
- Builder selection (non-Compose source builds): a dedicated build server pushes to the registry; a registry image name is required.
- **Watch Paths**: GitHub App/GitLab sources only (not public URLs or deploy keys); patterns evaluated in order, support `*`, `**`, `?`, `!`, last match wins.
- Registry output: source builds push **Image** (+ **Tag** in addition to the commit-based tag); output image required for Swarm, additional deployment servers, separate build servers.

## Network tab (non-Compose)

- **Ports Exposes**: comma-separated internal ports; first port = proxy-routing default + default health-check port; empty is valid for non-HTTP workers; read-only for static apps and manual-label mode.
- **Port Mappings** `host:container`: bypasses domain routing; a fixed host port prevents rolling updates; not shown for Swarm.
- **Network Aliases**: extra hostnames on the Docker network for stable container-to-container DNS; private only; not shown for Swarm.

## Labels tab

- **HTTP Basic Authentication**: proxy username/password for non-Compose; Compose defines middleware via labels.
- **Label management**: **Managed by Coolify** = read-only editor, Domains/www redirect/Ports Exposes stay primary; **Managed manually** = editable labels, those controls read-only, no repair on recreation. **Reset to defaults** replaces custom proxy labels.
- **Special characters**: **Escape ($ becomes $$)** vs **Keep unescaped (allow env variables)** for intentional env refs.

## Deployment tab

- **Pre-deployment**: `sh -c` in the CURRENT container after workspace prep, before replacement; failure stops the deployment; skipped on first deployment; Compose: adjacent **Container Name** (empty = single-container stack).
- **Post-deployment**: `sh -c` in the NEW container after the deployment is marked complete; failure is logged, status stays successful.
- Apply: **Save changes** for edited fields; redeploy after build method/commands/paths/image/Dockerfile/Compose location/build-time values/registry output; restart or redeploy after runtime ports/labels/auth/deployment commands.
