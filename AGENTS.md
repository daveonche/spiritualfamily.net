# Repository Agent Notes

Operational conventions for AI assistants working in this repository.

## Repository layout (first two directory levels)

```text
.
├── .github/
│   └── workflows/       CI pipeline (composer validate, lock install, PHP lint, image build)
├── docker/
│   ├── app/             App webserver image (PHP runtime + Composer 2; bakes the src/ tree and php/ ini overrides)
│   └── cron/            Cron image (BusyBox crond; bakes the committed crontab)
├── docs/
│   ├── analysis/        Story steps reports (S<X.Y>-story-steps.md)
│   ├── architecture/    Architecture and frontend design artifacts
│   ├── baseline/        Pinned baseline versions
│   ├── dependencies/    Dependency-management reports (S<X.Y>-dependencies.md)
│   ├── deployment/      Deploy runbook and Coolify setup
│   ├── pkgs/            Per-plugin package-lookup snapshots (JSON) backing the Composer-vs-manual install split
│   ├── requirements/    Core requirements
│   ├── sprints/         Sprint story files (sprint_<n>_stories.md)
│   ├── verification/    Verification records (e.g., the S1.7 cron cleanup run)
│   └── vision/          Project vision
└── src/
    ├── bin/             Operational shell scripts (install_preflight.sh: pre-install checks for the Elgg web installer)
    ├── elgg-config/     Elgg configuration (settings.php)
    ├── mod/             Elgg plugin directory
    ├── tests/           Guard test suite (PHPUnit; run via composer test)
    └── vendor/          Composer dependencies, including the vendored Elgg core
```

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

## Container log reading (required command form)

Never read `/dev/stdout` or `/dev/stderr` through `docker compose exec`
(e.g. `grep ... /var/log/apache2/error.log`): inside an `exec` session
those device symlinks are the session's own pipes, which never close, so
the command hangs until interrupted. `docker compose logs <service>` is
the correct reader for stdout/stderr sinks; only regular files on disk
may be read through `exec`.

## Production deployment

Production deploys follow `docs/deployment/deploy_runbook.md` (mandatory
pre-deploy database backup). Platform setup: `docs/deployment/coolify.md`.
