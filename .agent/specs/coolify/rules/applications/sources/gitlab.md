# Applications › Sources › GitLab

Source: coollabsio/coolify-docs `content/docs/applications/sources/gitlab.mdx` @ main `3b7c416`.

## Method selection

- Public repo → HTTPS URL. One private repo / OAuth unavailable → deploy key (SSH URL + repo deploy key). Private repos on GitLab.com or self-managed → GitLab App (OAuth, repo browsing, push + MR events). GitLab App requires Coolify v4.3.0+.

## GitLab App setup

1. **Sources** > **New source > GitLab App**: **Name**; optional comma-separated **Group name** limits listed repos; self-managed: expand **Self-hosted GitLab**, enter **GitLab URL** + **API URL** (GitLab.com default). **System wide** (self-hosted Coolify only) only when every team may use the source. **Continue** shows the redirect URI.
2. GitLab **User Settings > Applications** (group/instance admins can create it at group or instance level): exact redirect URI; scopes `api`, `read_user`, `read_repository`; save, copy **Application ID** + **Secret**.
3. Back in Coolify: enter ID + Secret, confirm URLs, save; **Connect to GitLab**, authorize; **Test Connection** after the redirect.
4. Webhooks: copy URL + secret token; per project **Settings > Webhooks**: URL + matching secret, enable **Push events** (branch deploys) + **Merge request events** (previews). Coolify rejects requests when the secret is missing or mismatched.
5. Deploy: project > **+ New** > **Private Repository (with GitLab App)**; server + app; load repositories; pick repo + branch; configure; verify clone + commit in the deployment log.

## Deploy key path

1. Private key under **Keys & Tokens > Private Keys**; public key under repo **Settings > Repository > Deploy keys**.
2. SSH URL (`git@gitlab.com:example/application.git`); create **Private Repository (with deploy key)**.

## Manual webhook (public URL / deploy key apps)

- **Webhooks** page: copy **GitLab** URL, enter **Webhook secret** in the GitLab section, **Save changes**; mirror URL + secret in GitLab **Settings > Webhooks**; enable push + (previews) merge-request events; **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**.

## GitLab Container Registry

- Repository credentials do not authenticate Docker to the registry. Private registry: GitLab deploy token with `read_registry`; `docker login registry.gitlab.com` on every server that pulls; use the complete image reference incl. tag in a Docker Image or Docker Compose app.
