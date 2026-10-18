# Core › Networking › Proxy › Traefik › Dynamic configuration

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/dynamic-config.mdx` @ main `3b7c416`.

## How it is stored

- Traefik separates startup configuration from dynamic routing configuration. Coolify stores file-based dynamic configuration under `/data/coolify/proxy/dynamic/` on the selected server and mounts it at `/traefik/dynamic/` inside the proxy container.
- Traefik watches that directory: valid file changes take effect without restarting the proxy.

## Add a configuration file

1. **Servers > your server > Proxy > Dynamic Configurations**.
2. **Add** → filename + valid Traefik YAML → save. A missing `.yaml`/`.yml` extension → Coolify adds `.yaml`. Filenames: letters, numbers, dots, dashes, underscores; no path separators; cannot start with a dot.
3. Verify: reload the page and confirm the file appears; then check the behavior it defines and inspect **Servers > your server > Proxy > Logs** for parsing or provider errors.

Example — reusable response-header middleware:

```yaml
http:
  middlewares:
    security-headers:
      headers:
        contentTypeNosniff: true
        frameDeny: true
        referrerPolicy: no-referrer
```

Reference it as `security-headers@file` from a Traefik router.

## Generated files are read-only

- Coolify shows generated files such as `coolify.yaml` and `default_redirect_503.yaml` without edit/delete controls. Change the corresponding Coolify setting instead of replacing generated files.

## Edit or remove a custom file

- Open the file from **Dynamic Configurations**, change the YAML, save. Delete only after removing every router reference to the middleware, service, or TLS option it defines.
- A YAML syntax error or a missing referenced object can leave affected routes unavailable even though Traefik itself keeps running. Restore the last working content and check the proxy logs when a change fails.

## When to restart the proxy

- Dynamic files are watched and normally need no restart. Restart when you change the main `/data/coolify/proxy/docker-compose.yml` — Traefik command flags, environment variables, ports, volumes, or the proxy image.
- Coolify does not sync disk edits to `docker-compose.yml` back into the UI once a proxy configuration is saved. Make changes under **Servers > your server > Proxy > Configuration** and save there; starting or restarting the proxy through Coolify writes its saved configuration to disk and can overwrite disk-only edits.
