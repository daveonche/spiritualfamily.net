# Applications › Configuration › Environment Variables

Source: coollabsio/coolify-docs `content/docs/applications/configuration/environment-variables.mdx` @ main `3b7c416`.

## Views

- **Normal view**: one variable at a time (Name, Value, scope, value settings).
- **Developer view**: `.env` format; `#` lines ignored; saving creates/updates/removes entries to match. Locked secrets and multiline values cannot be edited here — use Normal view.

## Scope

- **Build time** and **Runtime** are independent dropdowns; new variables default to available during build AND in the container.
- Sensitive build-time values: **Build secrets → Docker BuildKit secrets** (fallback: plain build arguments, which can remain visible in image metadata).

## Value types

- **Multiline** for line-break values (keys, certificates, config files).
- **Interpolation → Literal (keep $ characters as-is)** when `$` must stay unchanged; multiline values are already literal.

## References

- Shared variables: `{{environment.DATABASE_URL}}` (team/project/environment/server scopes).
- Secret managers: Doppler, Infisical, HashiCorp Vault via `{{vault.KEY}}`.

## Predefined variables

- `COOLIFY_FQDN`, `COOLIFY_URL`, `COOLIFY_BRANCH`, `COOLIFY_RESOURCE_UUID`, `COOLIFY_CONTAINER_NAME`, `SOURCE_COMMIT`, `PORT` (first exposed port when unset), `HOST` (`0.0.0.0` when unset).
- `SOURCE_COMMIT` excluded from builds by default (cache reuse); **Advanced > Build > Source commit availability → Available during build** to include.
- Reassign to another name: `APP_REVISION=$SOURCE_COMMIT`.

## Docker Compose detection

- `${VAR}` references are shown as app variables; `${VAR:?}` is required — deployment blocked while empty.

## Apply and naming

- Save, then redeploy (build-time values changed) or restart (runtime-only).
- Names: `[A-Za-z_][A-Za-z0-9_.]*`. Invalid runtime-only names deploy with a warning + suggested name; invalid build-time names stop the build.
- Copy button resolves shared-variable and `$SERVICE_*` references; locked variables have no copy button; references to locked shared variables stay references.
