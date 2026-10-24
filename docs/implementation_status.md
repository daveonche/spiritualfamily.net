# Implementation Status Report

Analysis date: 2026-10-23 (second analysis pass; supersedes the 2026-09-26 analysis)

Scope: Sprint 1 scaffolding stories (S1.1–S1.17) against `docs/requirements/core_requirements.md`, `docs/sprints/sprint_1_stories.md`, and `docs/tech_stack.md`.

## Current Implementation Status

### A. Completed Features

- Version-pinned Elgg 2.3 baseline (S1.1; REQ-FR-PU-5 anchor): `src/composer.json` pins `elgg/elgg: 2.3.17` with platform `php: 7.4.27`; `src/composer.lock` locks elgg/elgg 2.3.17 with `platform-overrides.php: 7.4.27`; `docs/baseline/baseline.md` records the pinned pair. Enforced by `src/tests/BaselineVersionTest.php`, `src/tests/BaselineDependencyPinTest.php`, `src/tests/BaselineRuntimeVersionTest.php`.
- Fresh-checkout reproducibility (S1.1 DoD): `docs/baseline/fresh_checkout.md` documents the end-to-end procedure; `docs/baseline/baseline.md` records the verification (2026-09-18, commit `951ef51`, site loads, admin login, PHP 7.4.27). Test: `src/tests/BaselineFreshCheckoutTest.php`.
- Production inventory as migration starting point (S1.1 Step 4): `docs/baseline/inventory.md` captures the database size (1121.1 MB `spirihf6_elgg114`), the 77-plugin active set with GUIDs and activation dates, site settings, and the data-directory summary (47,939.3 MB / 102,690 files). Test: `src/tests/BaselineInventoryTest.php`.
- Compose orchestration of web/db/cron (S1.2; REQ-DEPL-1, REQ-DEPL-2): `compose.yml` defines `app`, `dbm`, and `cron` on one `appnet` network with a healthcheck-gated `dbm` startup; the cron service renders the tokenized `crontab` and runs `crond -f -d 8`; the dev-only `pma` service is isolated in `compose.dev.yml` (corrected 2026-10-23; the 2026-09-26 entry listed `pma` in `compose.yml`). Test: `src/tests/ComposeOrchestrationTest.php`.
- PHP-version-parameterized app image (S1.2; REQ-FR-PU-5): `docker/app/Dockerfile` declares `ARG PHP_VERSION` and builds from `coldtrick/elgg_webserver:${PHP_VERSION}` with a Composer 2 layer; `compose.yml` passes the build arg and tags `sfn/elgg_webserver:${PHP_VERSION}-composer2`. Tests: `src/tests/ComposeImageBuildTest.php`, `src/tests/VersionPairAgnosticBuildTest.php`.
- Database pinned for production parity (S1.2): `compose.yml` pins the `dbm` image to `mariadb:10.11.18`; parity with production (`10.11.18-MariaDB-log`) is recorded in `docs/baseline/baseline.md`. Test: `src/tests/ComposeDatabasePinTest.php`.
- Environment-variable settings/secrets injection (S1.2/S1.3): `compose.yml` injects configuration via `env_file: .env`; `.env.example` documents every required variable with placeholder values only. Test: `src/tests/ComposeEnvInjectionTest.php`.
- Persistent volumes (S1.2): `compose.yml` defines named volumes `fs-dataroot`, `fs-cacheroot`, `fs-assetroot`, `fs-dbmdata`; `docker/app/Dockerfile` pre-creates the mount points with `www-data` ownership. Test: `src/tests/ComposePersistenceTest.php`.
- Elgg 2.3 installed and configured in Compose (S1.3): `docs/baseline/baseline.md` (Installation and Configuration section) records the clean schema install, admin login, and site URL stored at install time; the committed pre-flight script is verified by `src/tests/InstallProcedureTest.php`; `docs/workflow_state.md` confirms Story S1.3 complete (6/6 steps).
- Production dump restore / migration readiness (S1.3 AC): `docs/baseline/baseline.md` (Production Dump Restore Verification, 2026-09-25) records the gzip dump restored into `elgg_restore` on `dbm`, 36 tables, datalists exact match, drift documented.
- Third-party plugin drop and dropped-plugins list (S1.4; REQ-FR-PU-2, REQ-FR-PU-3, REQ-FR-PU-6, REQ-FR-PU-7; REQ-FR-TR-1 drop half): `docs/dropped_plugins.md` records the full 86-entry disposition list with per-plugin disposition (updated 2026-10-08); Step 2 theme-stack removals verified by `src/tests/LegacyThemeStackRemovalTest.php`; list↔codebase↔tech-stack cross-check by `src/tests/DroppedPluginsDispositionTest.php`; the repo carries 27 plugin directories after the Step 2–3 removals (25 kept plus custom_index and amap_coverphoto deferred); `srokap/code_review` confirmed absent from `src/composer.json`, `src/composer.lock`, and `src/vendor/composer/installed.json` (grep verification 2026-10-23).
- CI pipeline (S1.5): `.github/workflows/ci.yml` enforces the commit-msg hook on every pushed/PR commit and runs the `runtime` job (php:7.4.27-cli), `composer-validate`, `composer-install` from lock (with missing-lock guard), `composer-lock-consistency` (`composer validate --strict` with lock check enabled), `php-lint` (src/ excluding vendor), and `image-build` with base-image digest-pin verification. Tests: `src/tests/CiConventionsTest.php`, `src/tests/CiPinnedRuntimeTest.php`, `src/tests/CiManifestValidationTest.php`, `src/tests/CiLockInstallTest.php`, `src/tests/CiLockConsistencyTest.php`, `src/tests/CiPhpLintTest.php`, `src/tests/CiImageBuildTest.php`; the green-CI DoD is evidenced by the deploy runbook pre-deploy check (deploys proceeded 2026-10-20/22, `docs/deployment/deploy_runbook.md`).
- Coolify production deployment of the Elgg 2.3 baseline (S1.6; REQ-DEPL-3, REQ-DEPL-4): `docs/deployment/coolify.md` documents the platform setup (HTTPS entry routing terminating at the web container, production email via real SMTP through Coolify environment settings, no dev-only Compose overrides in production); `docs/deployment/deploy_runbook.md` records the mandatory pre-deploy database backup sequence and the run log (2026-10-20/21/22); `docs/baseline/baseline.md` records the production version cross-check (2026-10-20), the secrets walk (2026-10-18), the production database migration exact-match (Step 7), production email end-to-end including the silent-loss fix (2026-10-20), and clean re-deploy reproducibility (2026-10-22). Tests: `src/tests/ProductionVersionPinTest.php`, `src/tests/ProductionSecretHygieneTest.php`; `docs/workflow_state.md` confirms all 8 story steps complete.

### B. Partially Implemented Features

- Cron execution of Elgg hooks (S1.7): `crontab` triggers all nine Elgg cron intervals and `compose.yml` renders/runs the spool file. Remaining: end-to-end verification of garbagecollector and uservalidationbyemail runs, confirming the invocation path per the AC (the crontab currently uses `wget` against HTTP `/cron/*` URLs, not a CLI entry point), and cron log rotation (replacing dropped logrotate, REQ-FR-PU-7).
- Logging &amp; environment management (S1.8): the documented `.env` template AC is met (`.env.example`, `src/tests/ComposeEnvInjectionTest.php`). Remaining: per-environment Elgg logging with registered error/exception handlers, secrets-handling rules documentation, and container log routing/rotation.

### C. Not Yet Implemented Features

- S1.9–S1.13 — Sequential upgrades 2.3→3→4→5→6→7.1.0 (REQ-FR-PU-5, REQ-DEPL-4).
- S1.14–S1.16 — Plugin provisioning, activation, and theme skeleton at Elgg 7 (REQ-FR-PU-4, REQ-FR-TR-1).
- S1.17 — Developer workflow and upgrade runbook documentation.
- All `REQ-FR-CF-*`, `REQ-FR-CMF-*`, `REQ-FR-PS-*` feature preservation — scheduled for restoration at Elgg 7 in S1.14–S1.15, by design.

## Priority Order for Next Implementation Phase

- Priority 1 - Cron and scheduled tasks (S1.7):
  - Replace the HTTP `wget` cron invocation with the core CLI entry point per the AC
  - End-to-end garbagecollector and uservalidationbyemail verification; cron log rotation (replaces dropped logrotate, REQ-FR-PU-7)
  - Rationale: next story in sequence (S1.4 and S1.6 dependencies complete); observability must exist before upgrade steps start surfacing deprecation notices

- Priority 2 - Logging remainder (S1.8):
  - Per-environment Elgg logging with registered error/exception handlers, secrets-handling rules documentation, container log routing/rotation
  - Rationale: same observability rationale before the upgrade sequence begins

- Priority 3 - Upgrade sequence and restoration (S1.9–S1.17):
  - Sequential major-version upgrades with backup-then-upgrade-then-deploy per step, then plugin provisioning/activation/theme, then runbook
  - Rationale: strictly ordered per REQ-FR-PU-5; feature restoration only after Elgg 7.1.0 is reached

Next workflow step: `$planning-story-analysis S1.7`
