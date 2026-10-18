# MCP › Capabilities › Prompts and Resources

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/prompts-and-resources.mdx` @ main `3b7c416`.

## Resources

| Resource | Purpose |
| --- | --- |
| `coolify://overview` | Infrastructure overview for the authenticated team. |
| `coolify://application/{uuid}` | Summary JSON for a team-owned application. |

## Prompts

| Prompt | Purpose |
| --- | --- |
| `troubleshoot_application` | Guide an application diagnosis using database-first checks and optional live logs. |
| `explain_failed_deploy` | Explain a failed deployment using deployment details and optional log summary. |

## Scope

- Prompts do not expand token permissions.
- The tools they recommend still enforce team scope and token abilities.
