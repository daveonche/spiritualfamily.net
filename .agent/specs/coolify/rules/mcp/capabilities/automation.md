# MCP › Capabilities › Automation

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/automation.mdx` @ main `3b7c416`.

## Tools

| Tool | Purpose |
| --- | --- |
| `list_scheduled_tasks` | List scheduled tasks for an application or service. |
| `list_scheduled_task_executions` | List recent executions for a scheduled task. |
| `list_unhealthy_resources` | Find resources that appear unhealthy or down; use `sample_only=true` for a cheap first check. |
| `get_infrastructure_overview` | Return SQL-based health-hint counts and open deployment counts. |

## Response boundaries

- Task command bodies and execution messages require `read:sensitive` and are best-effort redacted.
