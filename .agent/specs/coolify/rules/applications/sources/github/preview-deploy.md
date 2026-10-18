# Applications › Sources › GitHub › Preview deployments

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/preview-deploy.mdx` @ main `3b7c416`.

## Behavior

- Pull requests deploy separately from production; Coolify removes the automatic preview after the PR is merged or closed.
- PRs already open when previews were enabled are NOT auto-deployed: **Load Pull Requests**, then **Deploy** per required PR.

## Domains

- **Preview URL Template**: `{{random}}` and `{{pr_id}}` placeholders.
- Wildcard DNS FIRST: `A`/`AAAA` record for `*.preview.example.com` pointing at the deployment server before using `https://123.preview.example.com`.

## Who can deploy

- **Preview Deployments**: trusted repository members, collaborators, contributors.
- **Allow Public PR Deployments**: public contributors — PR code is untrusted and would run on your server; keep disabled unless the risk is accepted.

## Scoped secrets

- **Production Environment Variables** are never passed to previews; **Preview Deployment Environment Variables** are used only by PR deployments.
- Never copy production credentials into preview variables for untrusted PRs — use limited, non-production credentials.

## Automated comments

- Deployment status + domain comments (updated on state change) REQUIRE GitHub App permissions; manual webhooks can trigger previews but not comments.

## Setup — GitHub App

- Automated installation: set **Preview deployment access > Read and update pull requests** BEFORE **Register with GitHub** (requests read-write PR access + subscribes to PR events).
- Manual installation: **Pull requests = Read and write** under Repository permissions + subscribe to the **Pull request** event.
- Update an existing app: **Sources > [app] > Permissions > Update** → Pull requests Read and write → subscribe Pull request events → accept.

## Setup — repository webhook

1. **Webhooks**: random **Webhook secret** in the **GitHub** section → **Save changes** → copy the GitHub URL under **Manual Git webhooks**.
2. GitHub repo settings > **Webhooks > Add webhook**: Payload URL; SAME secret; SSL verification; **Let me select individual events** → **Pull requests**; **Active**.

## Verify

1. Open/update a PR → separate preview deployment appears → 2. test the changed behavior on the preview domain → 3. close the PR → preview removed.
