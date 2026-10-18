# MCP › Setup

Source: coollabsio/coolify-docs `content/docs/mcp/setup.mdx` @ main `3b7c416`.

## Before you begin

- Use Coolify Cloud or a self-hosted Coolify instance with a public HTTPS URL.
- On self-hosted Coolify, enable **API access** in **Settings > Advanced**.
- Choose the team whose resources the client must access.
- Create an API token with the smallest required permissions — see the api-tokens rule.
- WARNING: an MCP client can use every operation allowed by its token. Store the token in the client's secret configuration, never commit it, and revoke it if exposed.

## Example values

- Coolify URL: `https://app.coolify.io`
- MCP endpoint: `https://app.coolify.io/mcp`
- API token: `67|abcthisisa123dummytoken`

## Enable MCP

- Coolify Cloud: the instance MCP endpoint is already enabled — continue with creating the token. Team access is enabled by default, but check team settings if a client receives `403`.
- Self-hosted via dashboard: **Settings > Advanced** > under **API and MCP**, set **MCP server** to **Enabled** > **Save changes**. Then open **Team > General**, set **MCP server** to **Enabled for this team**, and **Save changes**.
- Self-hosted via API: use a `root` API token to enable or disable the instance endpoint — replace the example URL with your instance's domain:

```sh
export COOLIFY_URL="https://coolify.example.com"
export COOLIFY_ROOT_TOKEN="67|abcthisisa123dummytoken"

curl --fail-with-body --request POST \
  --header "Authorization: Bearer $COOLIFY_ROOT_TOKEN" \
  "$COOLIFY_URL/api/v1/mcp/enable"
```

- The endpoint returns `MCP server enabled.`; disable it with `POST /api/v1/mcp/disable`.
- These endpoints require a `root` token — a `read` or `write` token cannot change the instance MCP setting.

## Create an MCP token

- Create a token from **Keys & Tokens > API Tokens** while the required team is active.
- Coolify displays the complete token only once — copy it into the client's secret configuration before leaving the API Tokens page.
- Use `read` for inventory and inspection; add `read:sensitive` only for supported sensitive data or log summaries; add `deploy` for deploy, start, stop, restart, or cancel operations; do not use `root` for normal MCP clients.
- MCP endpoint URL: `https://app.coolify.io/mcp`.

## Connect a client

- URL: `https://app.coolify.io/mcp`
- Transport: Streamable HTTP
- Authorization: `Bearer 67|abcthisisa123dummytoken`
- Client guides: see the claude-code, cursor, or other-clients rules.

## Verify access

- Ask the client to call `get_current_team` or `get_infrastructure_overview`.
- The response should contain the team and resources associated with the token.
