# Services › Configuration › Persistent Storage

Source: coollabsio/coolify-docs `content/docs/services/configuration/persistent-storage.mdx` @ main `3b7c416`.

## Compose is the source of truth

- Storage parsed from a Service is READ-ONLY on **Configuration > Persistent Storages** (grouped by component). Add/change/remove mounts in **Configuration > General > Edit Compose File**, save, review the parsed result.

## Mount choice

| Mount | Use when |
| :--- | :--- |
| Named volume | Docker should manage the server-side location |
| Bind-mounted directory | The stack needs a specific server directory |
| Bind-mounted file | One config/data file at an exact path |

- Every mount = source + container destination; the destination must match where the component actually reads/writes.

## Definitions

- Named volume: `volumes: [database-data:/var/lib/postgresql/data]` + top-level `volumes: { database-data: }`; Coolify prefixes the deployed name with a resource identifier — check **Show Deployable Compose**/**Persistent Storages** for the actual name.
- Relative bind sources live under the Service configuration directory. Coolify extensions: `is_directory: true` pre-creates a directory; `content: |` pre-creates a file with managed content.
- Absolute source paths only when deliberately depending on that exact server path; it must exist with permissions suitable for the container user.

## Change a mount safely

- Changing source or destination does NOT move files — the container can start empty while old data sits elsewhere. Sequence: identify the component + exact destination → back up source + test restore → stop writes (database/shared mounts) → edit Compose → save + review deployable + storage view → deploy/restart → verify read/write BEFORE removing the previous source.

## Backups and boundaries

- Mounts survive container recreation but are NOT backups: database-aware dumps for DB data, storage-mount backups where supported, external tooling otherwise; keep a tested copy OFF the server.
- No recovery of data written only to a removed container filesystem; a server path does not follow a clone to another server; sharing one writable mount needs application-level concurrency/locking; removing a mount can remove its Coolify record — never treat that record as the only inventory.
