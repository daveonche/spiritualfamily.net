# MCP › Clients › Cursor

Source: coollabsio/coolify-docs `content/docs/mcp/clients/cursor.mdx` @ main `3b7c416`.

## Scope

- Cursor supports remote MCP servers over Streamable HTTP.

## Add Coolify in Cursor

1. Open Cursor settings.
2. Open **Tools & Integrations** and select **MCP**.
3. Add a new remote MCP server.
4. Set the URL to `https://app.coolify.io/mcp`.
5. Set the Authorization header to `Bearer 67|abcthisisa123dummytoken`.
6. Save the server and enable it.

- Use a token with `read` for inspection; add `deploy` only when Cursor must run deployment or lifecycle operations.

## Verify the connection

- Ask Cursor to call `get_current_team` or `get_infrastructure_overview`.
- Confirm the result contains only the expected team's resources.
- Instance and team enablement, token permissions, and security requirements: see the setup and security rules.
