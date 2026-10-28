# Elgg Developer Guide: Security

Delta distillation of plugin security requirements. There is no
`security.rst` source page in `docs/guides/`; this topic distills the
field-observed conventions from the elgg-migrate security review
checklist, cross-checked against this pack's `access`, `actions`,
`database`, `file-system`, `settings`, and `upgrading-data` topics —
mechanics live there; this file records the security requirements.
Update in place when the underlying topics change.

## CSRF beyond the action system

- Actions registered via `elgg-plugin.php` or `elgg_register_action()`
  get automatic CSRF validation from routing middleware; token-less
  calls are ignored with a warning (see `actions`).
- Custom endpoints that bypass the action system (raw routes reading
  POST data) get no middleware: validate manually by reading
  `__elgg_ts` and `__elgg_token` with `get_input()`, regenerating the
  expected token for the submitted timestamp via
  `elgg()->csrf->generateActionToken($ts)`, and comparing (manual
  token flow in `actions`; the JS side reads `elgg.security.token`).

## Access bypasses must be intentional

- `elgg_get_entities()` and the entity APIs enforce access control by
  default.
- `elgg_call(ELGG_IGNORE_ACCESS, ...)` bypasses it (flags and
  mechanics in `access`) — verify every use is intentional and keep
  the bypass scoped inside the closure.
- Check entity ownership before allowing edit or delete operations;
  reachability alone is not authorization.

## Output escaping

- The view system does not auto-escape: echoing `$vars` values or
  entity properties is XSS.
- Escape user-controlled data with `htmlspecialchars()`; use
  `elgg_echo()` for translated strings.

## File uploads

- Check the MIME type server-side; never trust the client
  `Content-Type` header.
- Validate file extensions against an allowlist.
- Store uploads outside the webroot or through Elgg's file storage API
  (see `file-system`); fetch uploads in actions with
  `elgg_get_uploaded_file()` (see `actions`).

## SQL

- Prefer `elgg_get_entities()` and `QueryBuilder` over raw SQL (see
  `database`).
- When raw SQL is unavoidable, use prepared statements via
  `elgg()->db` — never interpolate `get_input()` values into query
  strings.
- Do not interpolate `elgg_get_config('dbprefix')` into raw SQL — the
  migrate tooling's security sweep flags it as a sql-injection risk;
  build the query with `QueryBuilder` instead.

## Hook and event input trust

- Hook values (`$hook->getValue()`) may contain data contributed by
  other plugins — validate before using in security-sensitive
  contexts.
- Verify the entity type of `$event->getObject()` before casting or
  acting on it.

## Plugin settings

- Settings are stored as strings and are visible to site admins —
  never store secrets (API keys, passwords) without encryption (see
  `settings`).

## Upgrade batches

- `Elgg\Upgrade\Batch` scripts run with elevated privileges — validate
  data integrity during migration and wrap multi-step data changes in
  transactions (see `upgrading-data`).
