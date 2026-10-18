# MCP › Capabilities › Servers

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/servers.mdx` @ main `3b7c416`.

## Tools

| Tool | Purpose |
| --- | --- |
| `list_servers` | List team-visible servers; optionally filter by reachability. |
| `get_server` | Inspect one server by UUID. Sensitive connection fields remain redacted. |
| `get_server_domains` | List domains hosted on a server. |
| `get_server_resources` | List resources defined on a server. |
| `list_destinations` | List Docker destinations; optionally filter by server. |
| `get_destination` | Inspect one Docker destination by UUID. |

## Usage

- Use `get_infrastructure_overview` first for counts and server health hints.
