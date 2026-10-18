# Services › Configuration › Environment Variables

Source: coollabsio/coolify-docs `content/docs/services/configuration/environment-variables.mdx` @ main `3b7c416`.

## Variable sources

| Source | Change it in |
| :--- | :--- |
| `${VARIABLE}` reference in Compose | **Environment Variables** |
| Hard-coded Compose `environment:` entry | **Edit Compose File** (shown separately, not in the variable list) |
| `SERVICE_URL_*` / `SERVICE_FQDN_*` / `SERVICE_NAME_*` | Managed by Coolify from the definition + domain config |
| Generated credential `SERVICE_PASSWORD_*` etc. | **Environment Variables**, unless locked by template or role |
| Shared-variable reference | The shared-variable scope + the Service variable referencing it |

## Editing

- **Normal view**: one variable; **Is Multiline?** for certificates/keys/config with line breaks; **Is Literal?** keeps `$` as text instead of expanding.
- **Developer view**: `.env` format; saving creates/updates/removes managed variables to match. Locked secrets and multiline values cannot be edited here.

## Requirement and defaults

- `${VARIABLE}` → editable, may be empty. `${VARIABLE:-default}` → created with the fallback. `${VARIABLE:?}` → required; deployment blocked while empty (e.g. enter `APP_SECRET` after saving Compose, before deploying).

## Reuse

- Reference the same key from every component (`DATABASE_URL: ${DATABASE_URL:?}` in api + worker) — one stored value written into the stack `.env`.
- Shared variables across Coolify resources: value `{{environment.DATABASE_URL}}` (team/project/environment/server scopes).
- Secret managers: Doppler, Infisical, HashiCorp Vault via `{{vault.KEY}}`; the source applies to the whole stack.

## Generated values

- URL/FQDN variables tie to the matching Compose service + domain configuration; credentials persist with the Service so components share them. Read the magic-variable rules (`docker-compose.md`) before renaming/deleting one — deletion is blocked while Compose still references it; remove the reference first.

## Apply and verify

1. Save → 2. confirm every required value filled → 3. restart or deploy (rewrites `.env`, recreates containers) → 4. inspect deployment output + component logs → 5. verify the consuming behavior.

- Never paste production secrets into support messages or screenshots; team members may have restricted access to secret values.
