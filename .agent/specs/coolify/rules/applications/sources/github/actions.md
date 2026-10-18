# Applications › Sources › GitHub › Actions

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/actions.mdx` @ main `3b7c416`.

## Deployment type

- GitHub Actions builds the image, so Coolify deploys the published image instead of rebuilding: a **Docker Image** application, or Docker Compose referencing `image:` (never `build:`).
- Authenticate the deployment server before using a private image.

## Coolify API setup

- Self-hosted: **Settings > Advanced > API and MCP > API access** → **Enabled** > **Save changes**. Coolify Cloud has it enabled by default; Cloud users cannot access that setting.
- Token: **Keys & Tokens > API Tokens**; **Description** identifies the workflow; set **Expires in**; **Permissions > Deploy**; **Create token**; copy immediately and store in a password manager.
- Webhook: application > **Webhooks** > copy **Deploy webhook URL**. The URL identifies the application; the API token authorizes the request.

## GitHub repository secrets

- `COOLIFY_WEBHOOK` = authenticated deploy webhook URL; `COOLIFY_TOKEN` = Coolify API token. Never commit either to the workflow file.

## Workflow shape

- Trigger on push to the configured branch; checkout; `docker/login-action` (GHCR: `github.actor` + `secrets.GITHUB_TOKEN`); `docker/build-push-action` (context, `file: Dockerfile`, platforms, `push: true`, tags `REGISTRY/IMAGE_NAME:latest`); then `curl --fail --request POST '$COOLIFY_WEBHOOK' --header 'Authorization: Bearer $COOLIFY_TOKEN'`.
- Keep the Coolify request AFTER tests, security checks, the image build, and the registry push — otherwise Coolify can pull the previous image or deploy an unvalidated release.
- Immutable releases: publish a commit-specific tag and update the deployed image reference as part of the release process instead of relying only on `latest`.

## Registry auth on deployment servers

- For a private registry, authenticate Docker on EVERY deployment server that must pull, as the server user configured in Coolify.
- GHCR: token with read-packages; `echo "$GH_TOKEN" | docker login ghcr.io --username "$GITHUB_USERNAME" --password-stdin`. Docker Hub: `docker login`; prefer an access token over the account password.

## Verify

1. GitHub Actions publishes the expected image → 2. Coolify request succeeds → 3. Coolify pulls the new image → 4. container starts and becomes healthy.
