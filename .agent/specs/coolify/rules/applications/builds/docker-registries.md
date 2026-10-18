# Applications › Builds › Docker registries

Source: coollabsio/coolify-docs `content/docs/applications/builds/docker-registries.mdx` @ main `3b7c416`.

## Pull

- Docker Image application takes a ref (`ghcr.io/example/api:1.4.0`) or digest (`@sha256:<digest>`); a digest pins content, a tag can move.

## Push

- **General > Docker Registry**: **Docker Image** (`ghcr.io/example/api`; empty = Coolify never pushes) + optional **Docker Image Tag**; Git-based builds also get a source-commit tag.

## Authentication

- **Registries** (main sidebar) or **Servers > [server] > Registries**; presets: Docker Hub, GHCR, GitLab, Google Artifact Registry (service-account JSON key), Azure ACR, AWS ECR, custom.
- Coolify runs `docker login` as the configured server user; the token is not stored in Coolify's DB — Docker keeps it in `~/.docker/config.json` (encoded, not encrypted).
- Authenticate every deployment **and** build server: a build server cannot run the app — it pushes, the deployment server pulls.
- API: `GET/POST /servers/{uuid}/registries`, `POST .../registries/{registry}/check`, `DELETE .../registries/{registry}`; listing requires `read:sensitive`.
- SSH fallback: `docker login <registry-host>`; token-based: `printf '%s' "$REGISTRY_TOKEN" | docker login <registry-host> --username <registry-user> --password-stdin`.

## Swarm

- Every manager and worker must resolve, reach, and authenticate with the registry; Coolify passes auth to `docker stack deploy`; test a pull from each node.

## Hosted registry (one-click service)

- `registry:3`; username/password via service variables; htpasswd generated at start; images in the persistent `registry-data` volume — include it in backups and configure retention outside Coolify.
