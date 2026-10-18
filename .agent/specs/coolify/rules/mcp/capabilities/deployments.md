# MCP › Capabilities › Deployments

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/deployments.mdx` @ main `3b7c416`.

## Tools

| Tool | Permission | Purpose |
| --- | --- | --- |
| `list_deployments` | `read` | List queued, in-progress, or application deployment history. |
| `get_deployment` | `read` | Inspect one deployment; build logs are not returned. |
| `deploy` | `deploy` | Queue an application deployment by UUID. Supports force rebuild and preview IDs. |
| `cancel_deployment` | `deploy` | Cancel a queued or in-progress deployment. |
| `control` | `deploy` | Start, stop, or restart an application, database, or service. `stop` requires `confirm=true`. |

## Usage

- Follow the security rule before granting `deploy`.

## Preview deployments

- Preview pull request IDs must be positive integers no greater than `2147483647`; invalid IDs produce a validation error.
- The related API workflow is documented upstream at `/applications/deployments/preview-deployments#manage-previews-through-the-api`.
