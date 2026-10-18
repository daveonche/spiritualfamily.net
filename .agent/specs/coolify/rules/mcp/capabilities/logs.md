# MCP › Capabilities › Logs

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/logs.mdx` @ main `3b7c416`.

## Tools

| Tool | Permission | Purpose |
| --- | --- | --- |
| `get_logs` | `read:sensitive` | Fetch recent logs for an application, database, service, or service child. |
| `get_deployment` | `read:sensitive` | Optionally return a capped, best-effort-redacted deployment log summary with `include_log_summary=true`. |

## Usage

- `get_logs` requires a running container on a reachable server.
- It returns up to 500 lines and may return a structured failure reason with suggested next tools.

## Response boundaries

- Log redaction is best effort, not a guarantee.
- Do not send unrestricted production logs to an AI client unless required.
