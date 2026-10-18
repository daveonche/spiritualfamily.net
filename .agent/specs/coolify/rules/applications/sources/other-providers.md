# Applications › Sources › Other Git providers

Source: coollabsio/coolify-docs `content/docs/applications/sources/other-providers.mdx` @ main `3b7c416`.

## Source types

- Forgejo, Gogs, self-hosted Git servers: **Public Repository** (unauthenticated HTTPS clone URL) or **Private Repository (with deploy key)** (SSH clone URL).
- The deployment server must resolve and reach the Git host.

## Automatic deployments

- Coolify's manual provider webhooks handle provider-specific payloads/signatures only for GitHub, GitLab, Bitbucket, Gitea.
- Other providers: call **Deploy webhook URL** from the provider's CI/workflow after required checks pass:

```bash
curl --fail --request POST "$COOLIFY_WEBHOOK" \
  --header "Authorization: Bearer $COOLIFY_TOKEN"
```

- Store the webhook URL + API token as protected CI secrets.

## Preview deployments

- Automatic PR previews need a supported provider event format; otherwise use the Coolify API, a CI deployment webhook, or manual previews.
- Before production reliance: test the clone, one manual deployment, one automated deployment.
