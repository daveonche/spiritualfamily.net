# Core › Security › Credentials › API Tokens

Source: coollabsio/coolify-docs `content/docs/core/security/credentials/api-tokens.mdx` @ main `3b7c416`.

## Scope

- API tokens authenticate integrations, scripts, deploy webhooks, and MCP clients to Coolify. Each token belongs to the user who creates it, is bound to the active team, and carries selected permissions.
- WARNING: treat an API token as a password — it grants access without an interactive sign-in or 2FA challenge. Store it in a secret manager, never commit it to a repository, and revoke it immediately if it is exposed.

## Before you create a token

- Switch to the team that owns the resources the integration must access — a token cannot access another team's resources.
- Self-hosted: **Settings > Advanced** > set **API access** to **Enabled**. Coolify Cloud keeps API access available, so Cloud users do not enable the instance-wide setting.
- When callers use predictable source addresses, set **Allowed API IPs** to the required IP addresses or CIDR ranges.
- Decide the smallest permission set and shortest practical expiration period.

## Create a token

- **Keys & Tokens** (main sidebar) > **API Tokens**.
- **Description**: identifies the caller and purpose (e.g. `production-status-monitor`, `docs-deploy-workflow`); 3–255 characters.
- **Expires in**: `7 days`, `30 days`, `60 days`, `90 days`, `1 year`, or `Never`. Prefer an expiration for automation that can be rotated; use `Never` only when the integration cannot be rotated safely on a schedule.
- **Permissions**: select only the permissions required — review the table below before using `write`, `read:sensitive`, or `root`.
- **Create token** > copy the generated token immediately — Coolify shows the complete value only once. Store it in the caller's secret manager before leaving the page; if the value is lost, revoke the issued token and create another one.

## Token permissions

| Permission | Use it for | Important behavior |
| :--- | :--- | :--- |
| `read` | List and inspect resources. | Sensitive response values remain redacted. |
| `read:sensitive` | Read secrets, logs, passwords, private keys, environment values, and Compose content returned by supported endpoints. | Coolify also adds `read` when this permission is selected. |
| `write` | Create, update, and delete resources through write endpoints. | The token owner must remain an administrator or owner of the token's team when the request runs. |
| `deploy` | Trigger deployments, restarts, stops, cancellations, and deploy webhooks. | Selecting `deploy` creates a deploy-only token in the dashboard. |
| `root` | Bypass the `read`, `write`, and `deploy` permission checks on API endpoints. | Instance-wide **API access** and IP allowlist controls still apply. Only team administrators and owners can create this token. Selecting it removes the other permissions because they are unnecessary. |

- Use `read` for inventory and monitoring, `deploy` for a deployment-only workflow, and `write` only when the caller must change configuration. Add `read:sensitive` only when redacted values are insufficient.
- A `root` token still belongs to one team and its owner must remain a member of that team — it does not provide cross-team access.

## Make a test request

- Send the complete token as a Bearer credential:

```sh
export COOLIFY_URL="https://coolify.shadowarcanist.com"
export COOLIFY_TOKEN="<api-token>"

curl --fail-with-body \
  --header "Authorization: Bearer $COOLIFY_TOKEN" \
  "$COOLIFY_URL/api/v1/teams/current"
```

- A successful request returns the team bound to the token. Do not paste a real token directly into shell history — load it from the caller's secret store in production.

## Team and role scope

- Coolify records the active team when the token is created; switching teams later does not change an issued token.
- Create a separate token for each team an integration must access.
- Removing the token owner from the team makes the token invalid.
- A `write` or `root` request fails if the token owner no longer has the administrator or owner role.
- Changing a user's password revokes that user's API tokens.
- Removing a member, downgrading a member's role through Coolify, or deleting a team revokes affected team tokens.
- The **Issued Tokens** list belongs to the signed-in user — a user can revoke their own token but cannot copy its secret value again.

## Rotate or revoke a token

- Rotate before it expires or whenever its storage or caller changes:
  1. Create a replacement with the same team and only the permissions still required.
  2. Copy the replacement into the integration's secret store.
  3. Make a test request with the replacement.
  4. **Keys & Tokens > API Tokens** > **Revoke token** for the previous token.
  5. Confirm the integration keeps working and the previous token now returns `401 Unauthenticated`.
- An expired token is rejected automatically. Coolify checks hourly for tokens expiring within 24 hours and sends the team's enabled **API token expiring** notifications.

## Troubleshoot

- 401 Unauthenticated → confirm the complete token appears after `Bearer`, has not expired or been revoked, and still belongs to a user who is a member of the token's team. A password change, team removal, or team deletion can revoke or invalidate the token.
- 403 Forbidden → self-hosted: confirm **API access** is enabled and the caller's IP matches **Allowed API IPs**. Then check the endpoint's required permission — for `write` or `root`, confirm the token owner is still a team administrator or owner.
- Sensitive values are redacted → create or use a token with both `read` and `read:sensitive`, or use a `root` token only when full administrative access is required.
- The expected resource is missing → it may belong to another team; switch to that team and create a separate token — an issued token's team cannot be changed.
- The token value is no longer visible → Coolify displays the complete token only after creation; revoke the token if its value was not saved, then create and store a replacement.
