# MCP › Capabilities › Integrations

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/integrations.mdx` @ main `3b7c416`.

## Tools

| Tool | Purpose |
| --- | --- |
| `list_github_apps` | List GitHub Apps available to the team without returning secrets. |
| `list_github_repositories` | List repositories through a team GitHub App. |
| `list_github_branches` | List branches for a repository through a team GitHub App. |

## Usage

- Repository and branch tools register only when a usable GitHub App exists.
- Call `list_github_apps` first.
