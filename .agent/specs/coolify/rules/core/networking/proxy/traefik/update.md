# Core › Networking › Proxy › Traefik › Update Traefik

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/update.mdx` @ main `3b7c416`.

## Trigger

- Coolify shows **Traefik patch update available** / **New Traefik minor version available** → update by changing the image version in the UI, saving the configuration, and restarting the proxy.

## Check compatibility first

- Open the [Traefik releases](https://github.com/traefik/traefik/releases); review the release notes between the current and target versions; follow linked migration guides for changed/removed configuration options.
- Default proxy configuration → usually no additional changes. Modified configuration → check custom command flags, environment variables, routing rules, middleware, and dynamic files against the new version first; major-version upgrades can require additional migration steps.
- Keep a copy of the current configuration + image version for restoration. Restarting the proxy briefly interrupts traffic to resources routed through it.

## Change the image version

1. **Servers > [server] > Proxy > Configuration**.
2. In **Configuration file**, find `image` under `services.traefik` and replace the tag with the reviewed version:

```yaml
services:
  traefik:
    image: traefik:v3.7.14
    # Keep the rest of your existing configuration.
```

- The version is an example — use the release you selected, applying any changes its release notes require. Use an explicit version tag so future updates are deliberate.
- Edit in Coolify: direct edits to `/data/coolify/proxy/docker-compose.yml` do not sync back into the UI once Coolify has a saved proxy configuration, and a restart through Coolify can overwrite disk-only edits.

## Save and restart

- **Save changes** → **Restart Proxy** → wait for the proxy to report running → check **Proxy > Logs** for startup/configuration errors → open application domains and verify HTTPS + any custom routing or middleware.
- New version fails → restore the previous image tag + configuration in the UI, save, restart the proxy again.
