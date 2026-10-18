# MCP › Capabilities › Pagination

Source: coollabsio/coolify-docs `content/docs/mcp/capabilities/pagination.mdx` @ main `3b7c416`.

## Arguments

- List tools accept `page` and `per_page` arguments.

| Argument | Default | Maximum |
| --- | :---: | :---: |
| `page` | `1` | — |
| `per_page` | `50` | `100` |

## Continuation responses

- Responses include `_pagination` with totals and a `next` object when another page exists:

```json
{
  "_pagination": {
    "page": 1,
    "per_page": 50,
    "total": 120,
    "total_pages": 3,
    "next": {
      "args": { "page": 2, "per_page": 50 }
    }
  }
}
```

- Use the returned `next.args` values rather than assuming page size or total pages.
- Some tools use narrower input limits for specific filters.
