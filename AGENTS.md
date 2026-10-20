# Repository Agent Notes

Operational conventions for AI assistants working in this repository.

## Development stack and test suite (required command form)

The development stack is the **merged** Compose definition. Plain
`docker compose ...` (compose.yml only) runs the production-shaped stack:
its app container bakes only the `src/` tree and carries no repo bind
mounts, so the guard test suite (`src/tests/`) cannot run there — it
reports "No tests executed!" or fails on missing repo files.

Always pass both files for development and testing:

1. Start/build the dev stack:

```bash
docker compose -f compose.yml -f compose.dev.yml up -d --build
```

2. Run the guard test suite:

```bash
docker compose -f compose.yml -f compose.dev.yml exec -T app composer test
```

The dev bind mounts (`compose.dev.yml`) map the repo into the container;
the tests resolve the repo root at `/var/www`.

## Production deployment

Production deploys follow `docs/deployment/deploy_runbook.md` (mandatory
pre-deploy database backup). Platform setup: `docs/deployment/coolify.md`.
