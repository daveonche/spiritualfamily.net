# Core › Automation › Deploy Webhooks

Source: coollabsio/coolify-docs `content/docs/core/automation/deploy-webhooks.mdx` @ main `3b7c416`.

## Requirements

- API access enabled (self-hosted: **Settings > Advanced > API Access** if disabled).
- Permission to create an API token for the active team.
- The resource or tag to deploy; a secure secret store in the calling system.

## Deploy token

- **Keys & Tokens > API Tokens**: description, expiration, `deploy` permission.
- A deploy-only token can trigger deploy webhooks without general read/write API access.
- Copy the token immediately — shown once. Store as e.g. `COOLIFY_TOKEN`; never commit it or place it in a workflow file.

## Webhook URLs

- Resource (one application/service): open the resource > **Webhooks** > copy **Deploy Webhook (auth required)** → `https://<host>/api/v1/deploy?uuid=<resource-uuid>&force=false`.
- Tag (every application/service with the tag): **Tags** > select tag > copy **Deploy Webhook URL** → `https://<host>/api/v1/deploy?tag=<tag-name>`.

## Trigger

- `GET` (or `POST`) with `Authorization: Bearer $COOLIFY_TOKEN`.
- Requires exactly one of `uuid` or `tag` — never both in one request.

## Request options

| Option | Purpose | Example |
| --- | --- | --- |
| `uuid` | Deploy one or more resource UUIDs, comma-separated. | `uuid=abc,def` |
| `tag` | Deploy one or more tag groups, comma-separated. | `tag=api,worker` |
| `force` | Rebuild without using the build cache. | `force=true` |

- JSON `POST`: send the same option names in the request body.

## Verify

- An application request returns deployment details, including a deployment UUID when one was queued; confirm on the application's **Deployments** page.
- For services or tags, verify each affected resource from its own page — an accepted API request is not proof every resource became healthy.

## Troubleshoot

- 401 Unauthorized → token complete after `Bearer`, not expired, belongs to the team owning the target resource/tag.
- 403 Forbidden → token needs `deploy` permission; read-only tokens cannot trigger the endpoint.
- "uuid or tag is required" → include exactly one of `uuid`/`tag` in query string or JSON body.
- No resources found → UUID/tag name correct and in the token's team; for tags, at least one resource still has the tag.
- 429 Too Many Requests → wait for `Retry-After`; a busy deployment queue can also return 429 while a group deployment is being created.
