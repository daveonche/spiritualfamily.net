# Implementation Status Report

Analysis date: 2026-09-26

Scope: Sprint 1 scaffolding stories (S1.1–S1.17) against `docs/requirements/core_requirements.md`, `docs/sprints/sprint_1_stories.md`, and `docs/tech_stack.md`.

## Current Implementation Status

### A. Completed Features

- Version-pinned Elgg 2.3 baseline (S1.1; REQ-FR-PU-5 anchor): `src/composer.json` pins `elgg/elgg: 2.3.17` with platform `php: 7.4.27`; `src/composer.lock` locks elgg/elgg 2.3.17 with `platform-overrides.php: 7.4.27`; `docs/baseline/baseline.md` records the pinned pair. Enforced by `src/tests/BaselineVersionTest.php`, `src/tests/BaselineDependencyPinTest.php`, `src/tests/BaselineRuntimeVersionTest.php`.
- Fresh-checkout reproducibility (S1.1 DoD): `docs/baseline/fresh_checkout.md` documents the end-to-end procedure; `docs/baseline/baseline.md` records the verification (2026-09-18, commit `951ef51`, site loads, admin login, PHP 7.4.27). Test: `src/tests/BaselineFreshCheckoutTest.php`.
- Production inventory as migration starting point (S1.1 Step 4): `docs/baseline/inventory.md` captures the database size (1121.1 MB `spirihf6_elgg114`), the 77-plugin active set with GUIDs and activation dates, site settings, and the data-directory summary (47,939.3 MB / 102,690 files). Test: `src/tests/BaselineInventoryTest.php`.
- Compose orchestration of web/db/cron (+pma) (S1.2; REQ-DEPL-1, REQ-DEPL-2): `compose.yml` defines `app`, `dbm`, `pma`, and `cron` on one `appnet` network; the cron service renders the tokenized `crontab` and runs `crond -f -d 8`. Test: `src/tests/ComposeOrchestrationTest.php`.
- PHP-version-parameterized app image (S1.2; REQ-FR-PU-5): `docker/app/Dockerfile` declares `ARG PHP_VERSION` and builds from `coldtrick/elgg_webserver:${PHP_VERSION}` with a Composer 2 layer; `compose.yml` passes the build arg and tags `sfn/elgg_webserver:${PHP_VERSION}-composer2`. Tests: `src/tests/ComposeImageBuildTest.php`, `src/tests/VersionPairAgnosticBuildTest.php`.
- Database pinned for production parity (S1.2): `compose.yml` pins the `dbm` image to `mariadb:10.11.18`; parity with production (`10.11.18-MariaDB-log`) is recorded in `docs/baseline/baseline.md`. Test: `src/tests/ComposeDatabasePinTest.php`.
- Environment-variable settings/secrets injection (S1.2/S1.3): `compose.yml` injects configuration via `env_file: .env`; `.env.example` documents every required variable with placeholder values only. Test: `src/tests/ComposeEnvInjectionTest.php`.
- Persistent volumes (S1.2): `compose.yml` defines named volumes `fs-dataroot`, `fs-cacheroot`, `fs-assetroot`, `fs-dbmdata`; `docker/app/Dockerfile` pre-creates the mount points with `www-data` ownership. Test: `src/tests/ComposePersistenceTest.php`.
- Elgg 2.3 installed and configured in Compose (S1.3): `docs/baseline/baseline.md` (Installation and Configuration section) records the clean schema install, admin login, and site URL stored at install time; the committed pre-flight script is verified by `src/tests/InstallProcedureTest.php`; `docs/workflow_state.md` confirms Story S1.3 complete (6/6 steps).
- Production dump restore / migration readiness (S1.3 AC): `docs/baseline/baseline.md` (Production Dump Restore Verification, 2026-09-25) records the gzip dump restored into `elgg_restore` on `dbm`, 36 tables, datalists exact match, drift documented.

### B. Partially Implemented Features

- CI pipeline (S1.5): `.github/workflows/ci.yml` exists and enforces the commit-msg hook on every pushed/PR commit (pinned checkout action SHA, read-only permissions, subjects validated via `.githooks/commit-msg`). Remaining per the S1.5 acceptance criteria: `composer validate`, `composer install` from lock, PHP syntax lint, `composer.json` ↔ `composer.lock` consistency enforcement, and Docker image build validation using the version build args.
- Cron execution of Elgg hooks (S1.7): `crontab` triggers all nine Elgg cron intervals and `compose.yml` renders/runs the spool file. Remaining: end-to-end verification of garbagecollector and uservalidationbyemail runs, confirming the invocation path per the AC (the crontab currently uses `wget` against HTTP `/cron/*` URLs, not a CLI entry point), and cron log rotation (replacing dropped logrotate, REQ-FR-PU-7).
- Logging &amp; environment management (S1.8): the documented `.env` template AC is met (`.env.example`, `src/tests/ComposeEnvInjectionTest.php`). Remaining: per-environment Elgg logging with registered error/exception handlers, secrets-handling rules documentation, and container log routing/rotation.

### C. Not Yet Implemented Features

- S1.4 — Third-party plugin drop and dropped-plugins list (REQ-FR-PU-2, REQ-FR-PU-3, REQ-FR-PU-6, REQ-FR-PU-7; REQ-FR-TR-1 drop half). Evidence of not-started: `src/composer.lock` still carries `srokap/code_review` (permanently dropped per `docs/tech_stack.md`); no dropped-plugins list document exists in the repository yet.
- S1.6 — Coolify deployment of the Elgg 2.3 baseline (REQ-DEPL-3, REQ-DEPL-4).
- S1.9–S1.13 — Sequential upgrades 2.3→3→4→5→6→7.1.0 (REQ-FR-PU-5, REQ-DEPL-4).
- S1.14–S1.16 — Plugin provisioning, activation, and theme skeleton at Elgg 7 (REQ-FR-PU-4, REQ-FR-TR-1).
- S1.17 — Developer workflow and upgrade runbook documentation.
- All `REQ-FR-CF-*`, `REQ-FR-CMF-*`, `REQ-FR-PS-*` feature preservation — scheduled for restoration at Elgg 7 in S1.14–S1.15, by design.

## Priority Order for Next Implementation Phase

- Priority 1 - Upgrade-Clean Codebase (S1.4):
  - Drop all third-party plugins (REQ-FR-PU-2); produce the dropped-plugins list with per-plugin disposition (REQ-FR-PU-3, REQ-FR-PU-6)
  - Drop legacy core plugins (embed, legacy_urls, diagnostics, logbrowser, logrotate, login_as) and the legacy theme stack (aalborg_theme, l1, mrdave_aalborg, paul_override) (REQ-FR-PU-7, REQ-FR-TR-1)
  - Rationale: unblocks S1.6 and S1.9; `src/composer.lock` still contains a permanently-dropped plugin, and `docs/baseline/inventory.md` confirms all drop-listed plugins are active in the baseline

- Priority 2 - CI (S1.5):
  - Extend `.github/workflows/ci.yml` with composer validate, install-from-lock, PHP lint, lock consistency, and parameterized Docker build validation
  - Rationale: gates every later change; required before S1.6

- Priority 3 - Production deployment of the baseline (S1.6):
  - Coolify deploy, fully pinned, secrets via environment, HTTPS, production DB migrated in sync (REQ-DEPL-3, REQ-DEPL-4)
  - Rationale: REQ-DEPL-4 requires production running pinned Elgg 2.3 before the first upgrade step

- Priority 4 - Cron verification and logging (S1.7 remainder, S1.8):
  - End-to-end cron checks, cron log rotation, per-environment logging
  - Rationale: observability must exist before upgrade steps start surfacing deprecation notices

- Priority 5 - Upgrade sequence and restoration (S1.9–S1.17):
  - Sequential major-version upgrades with backup-then-upgrade-then-deploy per step, then plugin provisioning/activation/theme, then runbook
  - Rationale: strictly ordered per REQ-FR-PU-5; feature restoration only after Elgg 7.1.0 is reached

Next workflow step: `#generate-sprint-stories`
