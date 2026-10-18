# Applications › Sources › Public repositories

Source: coollabsio/coolify-docs `content/docs/applications/sources/public-repositories.mdx` @ main `3b7c416`.

## Create the application

- Use **Public Repository** only when the repo clones over HTTPS without authentication; never an SSH URL for this source type.
- Project > **+ New** > **Public Repository**; choose the deployment server when prompted.
- Paste the HTTPS clone URL (`https://github.com/coollabsio/coolify-examples.git`) and **Check Repository** — loads repo + branch info; a GitHub branch path in the URL (`.../tree/v2`) preselects that branch; otherwise review the selected branch before continuing.
- Configure **Build pack**, base directory, build settings, internal port, and a domain when the app should be public; **Deploy** and inspect the deployment log.

## Automatic deployments are never automatic

- Connecting by URL configures no automatic deployment. Two options:
  - Manual Git webhook: add it, then **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**.
  - External workflow: call the authenticated **Deploy Webhook** from CI after checks pass.
