# MCP › Capabilities › Environment and Storage

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/environment-and-storage.mdx` @ main `3b7c416`.

## Tools

| Tool | Permission | Purpose |
| --- | --- | --- |
| `list_env_keys` | `read` | List environment-variable key names for an application, database, or service. Never returns values. |
| `list_shared_env_keys` | `read` | List shared key names at project or environment scope. Never returns values. |
| `list_storages` | `read` | List persistent and file storage mounts. Never returns file contents. |

## Response boundaries

- Task command bodies and supported sensitive fields require `read:sensitive`.
- Environment values are not returned by these tools.
