# Applications › Configuration › Advanced

Source: coollabsio/coolify-docs `content/docs/applications/configuration/advanced.mdx` @ main `3b7c416`.

## Build

- **Build cache**: **Rebuild from scratch every time** (no cache reuse) vs **Use Docker build cache** (default) vs **Force deploy (without cache)** (next deployment only). A custom Compose build command controls its own cache flags.
- **Build arguments**: **Inject build args automatically** (Coolify adds `ARG` instructions) vs **Managed manually in Dockerfile** (Dockerfile owns every `ARG`).
- **Source commit availability**: default **Runtime only (preserves cache)**; **Available during build** only when the image needs `SOURCE_COMMIT`.

## Deployment

- **Auto deploy**: **Deploy on push (webhooks)** (default; needs a working webhook/provider integration) vs **Manual deployments only**.
- Preview enablement + **PR deployment access** (Repository members only vs Public, fork PRs allowed) live on **Preview Deployments**; public previews run contributor-controlled code.

## Git

- **Submodules**: **Clone submodules**; the source/deploy key must reach private submodules; prefer relative URLs in `.gitmodules` for same-org private submodules; commit the new submodule pointer in the parent repo before deploying.
- **Git LFS**: enable only when the build needs LFS objects.
- **Clone depth**: **Shallow clone (latest commit only)** `--depth=1` vs **Full history** (tags, history-based versioning).

## Docker compose

- **Compose deployment**: **Raw (deploy file as-is)** removes Coolify's modifications — you supply proxy labels, networking, runtime config.
- **Predefined network**: join the destination network only for cross-resource communication; service DNS names can change after joining.

## Container

- **Consistent name (no rolling updates)**: application UUID as the name on every deployment; a fixed name prevents starting the replacement beside the current container — rolling updates unavailable.
- **Custom container name** (consistent mode): slugified, unique per server, also disables rolling updates.
- **Container Name Prefix** (generated mode): slugified, ≤ 30 chars, unique per server; `shop-api-20260908T141530`, Compose service `web-shop-api-...`. API: `custom_container_name_prefix`; `custom_internal_name` enables consistent naming — combining it with `is_consistent_container_name_enabled: false` returns `422`.

## Proxy (labels managed by Coolify)

- **Redirect HTTP to HTTPS** (Domains page): needs an existing HTTPS domain + DNS + certificate; does not convert HTTP-only domains.
- **Gzip compression**: disable when the app compresses responses or streams binary workloads.
- **Strip prefixes**: forwards `/api/users` as `/users`; test routes after — app links/redirects may still carry the prefix.
- Manual-label mode hides these controls; edit the equivalent labels yourself.

## Operations

- **Stop grace period (seconds)**: empty = 30s default; whole number 1–3600; does not fix a process that ignores the shutdown signal.
- **Max restart count**: default `10`; `0` disables; Coolify stops the app and notifies when the limit is hit.
- Most dropdowns + grace period + restart count save immediately; container-name fields and GPU config use **Save changes**; redeploy/restart after changing image, name, proxy, network, shutdown, logging, or GPU settings.

## Logs

- **Log drain**: forward to the server's log-drain endpoint; rejected when draining is not enabled on that server.

## GPU (non-Compose only; drivers + container runtime required on the server)

- **GPU access → Enabled** reveals driver/count/device/options fields.
- **GPU count** (empty = all GPUs) and **GPU device ids** (comma list `0,2`) are mutually exclusive — providing both is rejected and both values cleared.
- **GPU options**: driver-specific runtime options only.
