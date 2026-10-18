# MCP › Clients › Other Clients

Source: coollabsio/coolify-docs `content/docs/mcp/clients/other-clients.mdx` @ main `3b7c416`.

## Scope

- Any MCP client that supports remote Streamable HTTP servers can connect to Coolify.

## Required configuration

```yaml
server_url: https://app.coolify.io/mcp
transport: streamable-http
authorization: Bearer 67|abcthisisa123dummytoken
```

- Client configuration formats differ — follow your client's documentation for adding a remote server and setting HTTP headers.

## Verify the connection

- Call `get_current_team` or `get_infrastructure_overview`.
- If the client cannot connect, see the troubleshooting rule.
