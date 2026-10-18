# MCP › Capabilities › Teams

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/teams.mdx` @ main `3b7c416`.

## Scope

- Team tools identify the team bound to the API token and list its members.

## Tools

| Tool | Purpose |
| --- | --- |
| `get_current_team` | Return the team associated with the token, including MCP status. |
| `list_team_members` | List members of that team. |

## Team scope

- MCP never switches teams during a request.
- Create a separate token for each team a client must access.
