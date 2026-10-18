# Services › Configuration › Docker Compose

Source: coollabsio/coolify-docs `content/docs/services/configuration/docker-compose.mdx` @ main `3b7c416`. Canonical reference for `SERVICE_*` magic variables — `rules/applications/builds/docker-compose.md` defers to this table.

## Source vs deployable

- Every Service stores a Compose definition: one-click = copied template, user-defined = entered via **Docker Compose Empty**. Edit under **Configuration > General > Edit Compose File**.
- **Source Compose**: editable, reparsed on save. **Deployable Compose**: generated, read-only — Coolify may add/normalize container names, labels, volume names, networks, environment references, proxy configuration. Troubleshoot: confirm the setting in source, then read the deployable view for the actual Docker Compose input.
- Edit flow: edit source → **Validate** (user-defined only) → **Save** → review component cards, env vars, storage, deployable → deploy/restart → verify every component.
- Saving reparses; removing/renaming a Compose service changes component identification — back up data and inspect storage names first.

## Environment references

- `${DATABASE_URL:?}` = required, deployment blocked while empty; `${LOG_LEVEL:-info}` = initial fallback. Hard-coded values stay in Compose — change them in **Source Compose** (shown for reference only).

## Magic variables — `SERVICE_<TYPE>_<ID>`

| Pattern | Value | Size |
| :--- | :--- | :--- |
| `SERVICE_NAME_<SERVICE>` | Compose service name; auto-created for every service | — |
| `SERVICE_URL_<SERVICE>` / `SERVICE_URL_<SERVICE>_<PORT>` | URL with scheme, routed to the internal port when suffixed | — |
| `SERVICE_FQDN_<SERVICE>` / `SERVICE_FQDN_<SERVICE>_<PORT>` | Hostname without scheme, paired with the URL port routing | — |
| `SERVICE_USER_<ID>` / `SERVICE_LOWERCASEUSER_<ID>` | Random username | 16 chars |
| `SERVICE_PASSWORD_<ID>` / `SERVICE_PASSWORD_64_<ID>` | Random password, no symbols | 32 / 64 |
| `SERVICE_PASSWORDWITHSYMBOLS_<ID>` / `_64_<ID>` | Random password with symbols | 32 / 64 |
| `SERVICE_BASE64_<ID>`, `_32_`, `_64_`, `_128_` | Random alphanumeric, NOT Base64 despite the name | 32 / 64 / 128 |
| `SERVICE_REALBASE64_<ID>`, `_32_`, `_64_`, `_128_` | Base64 of random bytes | 32 / 64 / 128 bytes pre-encoding |
| `SERVICE_HEX_32_` / `_64_` / `_128_<ID>` | Random hex | 32 / 64 / 128 |
| `SERVICE_SUPABASEANON_<ID>` / `SERVICE_SUPABASESERVICE_<ID>` | Supabase JWT `anon` / `service_role`, signed with `SERVICE_PASSWORD_JWT` | JWT |

- Supabase variables are generated only when the Service also defines `SERVICE_PASSWORD_JWT`.
- `<ID>` is ONE token — `SERVICE_PASSWORD_DBPASSWORD`, never `SERVICE_PASSWORD_DB_PASSWORD`. URL/FQDN use the normalized service name (`my-api` → `MY_API`); the final numeric segment is an internal port (`SERVICE_URL_API_3000`).
- The same complete variable name returns the same stored value everywhere; values persist across deployments. Passwords/random values editable under **Environment Variables**; URL/FQDN/name managed from the definition + domain config.

## Domains

- Declare `SERVICE_URL_<SERVICE>[_<PORT>]` on the component needing the domain; a path value appends (`SERVICE_URL_API_3000: /v1`). Alternatively set the component **Domains** field after parsing; review the generated variable after any domain change.

## Storage and health checks are Compose-owned

- Volumes/bind mounts come from the definition; Coolify prefixes managed volume names; the storage view is read-only.
- No standalone health-check page: define `healthcheck:` in Compose or the image; `exclude_from_hc: true` for one-time/optional services.

## Networking

- Components use Compose service names on the Service-specific network; no `ports:` for internal-only components. **Connect To Predefined Network** only for cross-resource communication.

## Update a one-click Service

- The template is copied at creation; Coolify never merges later template changes. Before updating: read release notes + service guide → back up data + test restore → compare current source vs newer template → apply image/variable/mount/migration changes deliberately → save, review deployable, deploy in a maintenance window → verify every component.
- **Pull Latest Images & Restart** only pulls tags already stored in Compose; pin production images when upstream offers versioned tags.
