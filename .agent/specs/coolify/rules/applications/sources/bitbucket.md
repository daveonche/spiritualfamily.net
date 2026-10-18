# Applications › Sources › Bitbucket

Source: coollabsio/coolify-docs `content/docs/applications/sources/bitbucket.mdx` @ main `3b7c416`.

- HTTPS URL for public (`https://bitbucket.org/example/application.git`), SSH URL for private with deploy key (`git@bitbucket.org:example/application.git`); add the deploy key's public key under the repo's access-key settings.

## Automatic deployments

1. **Webhooks** in the Coolify application; copy the **Bitbucket** URL.
2. Enter **Webhook secret** in the **Bitbucket** section; **Save changes**.
3. Bitbucket repo webhook settings: URL + same secret (when the provider flow supports it).
4. Enable **Repository push**; previews: pull request **Created**, **Updated**, **Merged**, **Declined**.
5. **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**.

- Verify separately: push to the configured branch → **Deployment Logs**; open/update a PR → preview events.
