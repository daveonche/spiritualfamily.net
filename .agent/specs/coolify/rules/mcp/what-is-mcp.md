# MCP › What is MCP

Source: coollabsio/coolify-docs `content/docs/mcp/what-is-mcp.mdx` @ main `3b7c416`.

## Scope

- Coolify's Model Context Protocol (MCP) server lets compatible AI clients inspect and operate resources in a Coolify team through a standard HTTP connection.
- MCP gives an AI client structured access to Coolify data: ask about infrastructure, find resources, inspect deployments, read supported logs, and run lifecycle operations allowed by its API token.

## What MCP provides

| Capability | Purpose |
| --- | --- |
| Tools | Let an AI client call Coolify operations such as listing applications or starting a resource. |
| Resources | Provide addressable read-only data such as an infrastructure overview or application summary. |
| Prompts | Provide guided workflows for tasks such as troubleshooting an application or explaining a failed deployment. |
| Streamable HTTP | Connects an MCP client to Coolify at `/mcp` without a local MCP process. |

## What MCP does not do

- MCP does not bypass Coolify authentication, team scope, API access, token permissions, or sensitive-value redaction.
- Every request uses an API token, and every resource belongs to the team bound to that token.

## Continue learning

- How MCP works (authentication, team scope, response boundaries): see the how-mcp-works rule.
- Set up MCP (enable the endpoint, create a scoped token, connect a client): see the setup rule.
- MCP capabilities (find tools, resources, and prompts by task): see the SKILL.md rules index.
