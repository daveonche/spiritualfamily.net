# MCP › Capabilities › Services

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/services.mdx` @ main `3b7c416`.

## Tools

| Tool | Purpose |
| --- | --- |
| `list_services` | List multi-container services with project, environment, server, status, and name filters. |
| `get_service` | Inspect one service by UUID. |
| `list_service_applications` | List application components in a service. |
| `get_service_application` | Inspect one service application component. |
| `list_service_databases` | List database components in a service. |
| `get_service_database` | Inspect one service database component. |

## Usage

- Use `get_logs` with the appropriate child resource when a service has multiple containers.
