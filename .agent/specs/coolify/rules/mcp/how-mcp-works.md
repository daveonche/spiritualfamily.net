# MCP › How MCP Works

Source: coollabsio/coolify-docs `content/docs/mcp/how-mcp-works.mdx` @ main `3b7c416`.

## Connection flow

- Coolify exposes an MCP server at `/mcp`; an MCP-compatible client sends Streamable HTTP requests to that endpoint with a Coolify API token.

1. Enable MCP for the Coolify instance.
2. Keep MCP enabled for the team the token belongs to.
3. Create an API token for that team.
4. Configure the client with the endpoint and Bearer token.
5. The client negotiates MCP capabilities and calls tools, reads resources, or uses prompts.

## Authentication and scope

- The client sends the complete token as a Bearer credential:

```http
Authorization: Bearer 67|abcthisisa123dummytoken
```

- The token is bound to one Coolify team; MCP only returns resources owned by that team. Create one token per team when a client needs access to multiple teams.
- The instance MCP setting must be enabled before `/mcp` responds. Team owners can disable MCP for individual teams.
- A disabled instance returns `404`; a disabled team returns `403`.

## MCP primitives

| Primitive | Role |
| --- | --- |
| Tools | Perform Coolify queries and operations. Read tools need `read`; deployment and lifecycle tools need `deploy` — the SKILL.md capabilities index groups tools by task. |
| Resources | Read-only addresses an MCP client can request directly; Coolify currently exposes an infrastructure overview and an application summary resource. |
| Prompts | Guided workflows; Coolify provides prompts for troubleshooting applications and explaining failed deployments. |

## Response boundaries

- Coolify returns structured responses with `data` and may include `_pagination`, `_actions`, or failure details.
- Environment values, configuration snapshots, and full deployment logs are not returned.
- Log summaries are capped and best-effort redacted.
