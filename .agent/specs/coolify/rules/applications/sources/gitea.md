# Applications › Sources › Gitea

Source: coollabsio/coolify-docs `content/docs/applications/sources/gitea.mdx` @ main `3b7c416`.

- HTTPS URL for public (`https://gitea.example.com/example/application.git`), SSH URL for private with deploy key (`git@gitea.example.com:example/application.git`).
- Add the deploy key's public key to the repository BEFORE creating the private application.

## Automatic deployments

1. **Webhooks** in the Coolify application; copy the **Gitea** URL.
2. Enter **Webhook secret** in the **Gitea** section; **Save changes**.
3. Gitea repo webhook settings: URL + same secret.
4. Enable **Push**; enable pull-request events when using preview deployments.
5. **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**.

- Verify a push deployment first; then open/update a PR and inspect **Preview Deployments**.
