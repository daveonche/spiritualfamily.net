# Applications › Builds › Docker Compose

Source: coollabsio/coolify-docs `content/docs/applications/builds/docker-compose.mdx` @ main `3b7c416`.

## Two resource paths

- Git repo + **Compose** build pack → Application (branch deploys, webhooks, previews).
- **Docker Compose Empty** → user-defined Service; no Git source/webhooks/previews; edit via **Edit Compose File**.
- File located by **Base Directory** + **Docker Compose Location** (relative). Git-based: edit in the repo and reload; rendered **Docker Compose Content** is not the primary copy.

## Domains

- **Domains > Add domain** per HTTP service; include the internal port when ≠ 80 (`https://app.example.com:3000`); public traffic still uses normal HTTP/HTTPS ports.

## Environment variables

- `${VAR}` → empty editable var; `${VAR:-text}` → default when unset **or empty**; `${VAR-text}` → default only when unset; `${VAR:?}` → required, blocks deploy when empty; `${VAR?}` → also required (prefer `:?`).
- Coolify keeps existing values when the definition is reloaded; dashboard edits survive Compose re-parse.
- Defaults may nest: `${PUBLIC_API_URL:-${SERVICE_URL_API}/v1}` — keep braces balanced.
- Reuse one value by referencing the same key across services/fields, including bind-mount sources.
- Shared variables: declare `${VAR}` in Compose, then set its value to `{{environment.VAR}}` under **Environment Variables**.

## Generated (magic) variables — `SERVICE_<TYPE>_<ID>`

- `SERVICE_URL_<ID>` (with scheme) / `SERVICE_FQDN_<ID>` (no scheme): `<ID>` = Compose service name with `-`/`.` → `_`; a final numeric segment is the internal port (`SERVICE_URL_API_3000` → port 3000); assign a path as the value (`SERVICE_URL_API_3000: /v1`).
- Credentials: `SERVICE_USER_*` / `SERVICE_LOWERCASEUSER_*` (16 chars), `SERVICE_PASSWORD_*` (32), `SERVICE_PASSWORD_64_*` (64), `SERVICE_PASSWORDWITHSYMBOLS_*` / `_64_*`, `SERVICE_BASE64_*` / `_32_` / `_64_` / `_128_` (NOT Base64-encoded despite the name), `SERVICE_REALBASE64_*` (real Base64 of random bytes), `SERVICE_HEX_32/64/128_*`.
- Credential `<ID>` must be a single token without underscores (`SERVICE_PASSWORD_DBPASSWORD`, not `SERVICE_PASSWORD_DB_PASSWORD`); ordinary names like `DATABASE_PASSWORD` are not magic.
- The same complete variable name returns the same stored value to every service; values persist and are editable under **Environment Variables** (URL/FQDN managed from domain config).

## Storage

- Compose-owned volumes are read-only under **Persistent Storage**; change them in the Compose file.
- `external: true` volumes keep their declared Docker names.
- Relative bind sources resolve from the resource directory (`../` supported); paths are validated; managed file paths must stay inside it.
- Extensions: `is_directory: true` pre-creates a directory; `content: |` pre-creates a file with content. Repo-sourced bind files need **Preserve repository during deployment** (**General > Build pipeline**).
- Missing file bind source → Docker may create a directory at that path → container fails with `not a directory: unknown`; fix the source and redeploy.

## Health checks and networking

- Health checks come from the Compose definition/image (Git-based Compose apps have no standard **Healthcheck** page); `exclude_from_hc: true` for one-time containers (e.g. migrations).
- Coolify creates a resource-specific network; services reach each other by service name + internal port.
- **Advanced > Docker compose > Predefined network** only to reach other resources on the destination; verify prefixed generated names before using them as hostnames.
- Never publish `ports:` unless the service must be reachable on a host interface — it bypasses domain-based proxy routing.

## Raw mode, labels, build args, commands

- **Advanced > Docker compose > Compose deployment → Raw (deploy file as-is)**: no Coolify modifications; you must supply proxy labels and networking. Normal path auto-adds `coolify.managed=true`, `coolify.applicationId=<id>`, `coolify.type=application`, plus proxy labels.
- Build args: same policy as Dockerfile apps (**Managed manually in Dockerfile**; `SOURCE_COMMIT` excluded by default).
- **Custom Build Command** / **Custom Start Command** replace the Compose commands; Coolify injects compose-file + env-file flags unless the custom command supplies its own; keep paths relative to **Base Directory** and use the parsed definition.
- Network names support interpolation (`${NETWORK:-coolify}`, `${COMPOSE_PROJECT_NAME}_default`); a single-variable network name becomes an editable env var.
