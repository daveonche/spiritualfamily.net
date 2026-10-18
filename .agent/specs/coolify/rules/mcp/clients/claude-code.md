# MCP › Clients › Claude Code

Source: coollabsio/coolify-docs `content/docs/mcp/clients/claude-code.mdx` @ main `3b7c416`.

## Scope

- Claude Code can connect directly to Coolify's Streamable HTTP MCP endpoint.

## Add the Coolify server

- Run this command from a terminal, replacing the URL and token:

```sh
claude mcp add --transport http coolify https://app.coolify.io/mcp \
  --header "Authorization: Bearer 67|abcthisisa123dummytoken"
```

- Use a token with `read` for inspection; add `deploy` only when Claude Code must run deployment or lifecycle operations.

## Verify the connection

- Start Claude Code and ask it to call `get_current_team` or `get_infrastructure_overview`.
- Confirm the result contains only the expected team's resources.
- Instance and team enablement, token permissions, and security requirements: see the setup and security rules.
