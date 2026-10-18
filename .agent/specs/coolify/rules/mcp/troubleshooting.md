# MCP › Troubleshooting

Source: coollabsio/coolify-docs `content/docs/mcp/troubleshooting.mdx` @ main `3b7c416`.

## Troubleshoot

- The MCP endpoint returns `404` → set **MCP server** to **Enabled** in **Settings > Advanced > API and MCP**, then **Save changes** — the instance endpoint returns `404` while this setting is disabled.
- The MCP endpoint returns `403` → open **Team > General** for the token's team, set **MCP server** to **Enabled for this team**, then **Save changes**.
- The client returns `401` or authentication fails → confirm the complete token appears after `Bearer`, has not expired or been revoked, and belongs to a team member; confirm the client sends the header on every MCP request.
- The client cannot connect → confirm the URL ends with `/mcp`, the instance is reachable from the client machine, HTTPS is valid, and transport is **Streamable HTTP** — do not configure SSE or stdio for Coolify's remote endpoint.
- A tool reports missing permissions → use `read` for inspection, `read:sensitive` for supported sensitive output, and `deploy` for deployment or lifecycle operations; the token owner must retain the required team role for restricted operations.
- A resource is missing → MCP returns only resources owned by the token's team; create or use a token for the team that owns the resource, and start with `get_current_team` or `get_infrastructure_overview` to inspect the token's scope.
- Logs are unavailable → `get_logs` requires `read:sensitive`, a running container, and a reachable server; for a failed deployment, call `get_deployment` first and request an optional log summary only when needed.
