# MCP › Capabilities › Databases

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/databases.mdx` @ main `3b7c416`.

## Tools

| Tool | Purpose |
| --- | --- |
| `list_databases` | List standalone databases with project, environment, server, status, and name filters. |
| `get_database` | Inspect one database by UUID. |
| `list_database_backups` | List backup schedules for a database. |
| `list_backup_executions` | List executions for a scheduled database backup. |

## Response boundaries

- Backup execution messages require `read:sensitive` and are best-effort redacted.
