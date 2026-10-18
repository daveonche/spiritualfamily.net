# Applications › Builds › Railpack (Beta)

Source: coollabsio/coolify-docs `content/docs/applications/builds/railpack.mdx` @ main `3b7c416`.

- Railpack is Railway's Nixpacks successor; Git-based apps only; **Beta** — validate the generated image before production cutover.
- Requires the Docker Buildx CLI plugin on BOTH the build server and the Coolify helper container; missing either stops the deployment with an error.
- **Build pack** at creation → **Build strategy** under **General > Build pipeline**; **Base Directory** `/` or e.g. `/apps/api` (monorepo).
- **Site type** (**General > Build pipeline**): **Dynamic**, **Static**, or **SPA**. At creation: server app → **Web application** output + internal port; static → **Static site** + **Publish Directory** (`/dist`) + keep Nginx port `80`.
- Detected **Install/Build/Start Command** under **General**; override via those fields (preferred for simple overrides) or `railpack.json`.
- Build env vars use the `RAILPACK_` prefix, added with **Build time → Available during build**: `RAILPACK_BUILD_CMD`, `RAILPACK_INSTALL_CMD`, `RAILPACK_START_CMD`, `RAILPACK_PACKAGES` (Mise, space-separated `package[@version]`), `RAILPACK_BUILD_APT_PACKAGES`, `RAILPACK_DEPLOY_APT_PACKAGES` (Coolify also adds `curl` + `wget` to the final image), `RAILPACK_DISABLE_CACHES` (`*` disables all caches), `RAILPACK_VERBOSE`.
- `railpack.json` lives in the Base Directory; `$schema: "https://schema.railpack.com"`; keys: `packages` (e.g. `node: "22"`), `steps.install/build.commands[]`, `deploy.startCommand`; Coolify reads it and passes the config to `railpack prepare`.
- Static/SPA: the publish directory is copied into an Nginx image; **Custom Nginx configuration** is copied at image build → redeploy to apply.
- Railpack vs Nixpacks: `railpack.json` vs `nixpacks.toml|json`; BuildKit frontend via Buildx vs generated Dockerfile; Mise vs Nix; `RAILPACK_` vs `NIXPACKS_`; actively developed vs maintenance mode; Beta vs Supported. Deterministic image needs → Dockerfile.
