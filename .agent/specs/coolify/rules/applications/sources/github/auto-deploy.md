# Applications › Sources › GitHub › Auto Deploy

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/auto-deploy.mdx` @ main `3b7c416`.

## Three paths

- **GitHub App**: Coolify sets **Auto deploy** to **Deploy on push (webhooks)** by default. If set to **Manual deployments only**: application > **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**; push a commit to the configured branch and confirm a deployment appears.
- **GitHub Actions**: when a workflow should run tests, build and publish an image, then request the Coolify deployment.
- **Repository webhook**: for a public repository or a deploy-key-connected private repository.

## Repository webhook setup

1. Application > **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**.
2. **Webhooks** page: enter a random **Webhook secret** in the **GitHub** section → **Save changes** → copy the GitHub URL under **Manual Git webhooks**.
3. GitHub repo settings > **Webhooks > Add webhook**: paste the Coolify URL into **Payload URL**; enter the SAME secret; keep **Enable SSL verification**; select **Just the push event**; keep **Active**.
4. Push a commit; confirm GitHub records a successful delivery and Coolify starts a deployment.

## Secret handling

- The webhook secret authenticates deliveries — store it like a password and use the same value in Coolify and GitHub.
