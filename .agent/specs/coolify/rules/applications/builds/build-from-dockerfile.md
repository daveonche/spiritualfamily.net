# Applications › Builds › Dockerfile

Source: coollabsio/coolify-docs `content/docs/applications/builds/dockerfile.mdx` @ main `3b7c416`.

## Where the Dockerfile lives

- Two paths: **Git repository** (build context = **Base Directory**; **Dockerfile Location** relative to it) or **Dockerfile without Git** (stored under **General > Dockerfile**).
- Without Git there is no build context: `COPY`/`ADD` cannot read repository files.
- Without-Git apps have no Git source: Git push events and pull-request previews do not apply — edit the stored Dockerfile and redeploy when its image instructions change.
- Creation label is **Build pack**; after creation it is **Build strategy** under **General > Build pipeline**.

## Runtime wiring

- Set **Ports Exposes** to the internal listening port; the process must bind `0.0.0.0`, not `127.0.0.1`, or the proxy cannot reach it.
- Without-Git apps: the first `EXPOSE` becomes the initial **Ports Exposes**; initial port is `80` when there is no `EXPOSE`.
- Runtime settings (domains, env vars, storage, health checks, limits) are not baked into the image.
- Never copy production secrets into the Dockerfile or image layers.

## Build arguments and secrets

- Under **Advanced > Build > Build arguments**, select **Managed manually in Dockerfile** when the Dockerfile declares and consumes every `ARG` itself.
- `SOURCE_COMMIT` is excluded from Git-based builds by default (cache reuse across commits); set **Source commit availability** to **Available during build** only when the image contents need it.
- Sensitive build-time values: **Build secrets > Docker BuildKit secrets** (requires BuildKit) — read the upstream environment-variables guide (build and runtime scope) before passing credentials to a build.

## Health checks

- Image-level `HEALTHCHECK` works; the image must contain the check command. Alternatively configure under **Healthcheck** — see the health-checks rule for precedence.

## Deployment commands

- **Pre-deployment** runs in the current container before replacement; skipped when no current container exists (first deployment); failure stops the deployment.
- **Post-deployment** runs in the new container; deployment is marked complete before it runs; failure is logged but does not change the successful status.
- Both must be safe to run repeatedly; test migrations against non-production.

## Node.js multi-core scaling

- A plain Node.js process uses one CPU core — see the upstream Node.js multi-core scaling guide when a Node.js or Bun application should use multiple cores from one Dockerfile deployment.

## Troubleshoot a failed domain after a successful build

If the image builds but the domain does not respond:

1. Open **Runtime Logs** and look for exit or bind errors.
2. Confirm the process binds to `0.0.0.0`, not `127.0.0.1`.
3. Confirm **Ports Exposes** matches the internal listening port.
4. Run a health-check request against the port inside the container's **Terminal**.
5. Follow the upstream no-available-server guide when no server is available to run the container.
