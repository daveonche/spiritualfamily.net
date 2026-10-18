# Applications › Configuration › Persistent Storage

Source: coollabsio/coolify-docs `content/docs/applications/configuration/persistent-storage.mdx` @ main `3b7c416`.

- Containers are replaceable; data written only to the container filesystem disappears on redeploy/removal. Mounts attach data outside the container at the path the application expects.

## Mount types

- **Volume Mount**: Docker manages the location; add **Source Path** only when a specific server path is required.
- **Directory Mount**: application needs a specific server directory.
- **File Mount**: one file whose contents Coolify manages.
- **Host File Mount**: existing file at an exact server path.

## Workflow

- Every mount = a server storage location + **Destination Path** inside the container (e.g. `/app/uploads`); only data under the destination path persists.
- Add under **Persistent Storage** → **Add** → type → source + exact destination → save → redeploy/restart → verify read/write.
- Record the source name/path for later migration or backup restore.

## Compose

- The Compose file is the source of truth for volumes/bind mounts; change it there and reload; Coolify only displays parsed mounts (no mount editor).

## Boundaries

- No recovery of data already written to a removed container.
- Storage is local to the deployment server — connecting another server does not share it.
- The container user needs read/write permission on the destination.
- Do not share a writable directory between containers without app-level concurrency/locking support.
- Not a backup: use storage-mount backups for eligible volume/directory mounts and test restores separately.
