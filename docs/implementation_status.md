# Implementation Status Report

Analysis date: 2026-11-18 (fourth analysis pass; supersedes the 2026-11-07 analysis)

Scope: Sprint 1 scaffolding stories (S1.1–S1.17) against `docs/requirements/core_requirements.md`, `docs/sprints/sprint_1_stories.md`, and `docs/tech_stack.md`.

## Current Implementation Status

### A. Completed Features

- Version-pinned Elgg 2.3 baseline (S1.1; REQ-FR-PU-5 anchor): `src/composer.json`/`src/composer.lock` pin history and `docs/baseline/baseline.md` records — unchanged from the third pass; the pin has since moved twice (see S1.10/S1.11 below). Enforced by `src/tests/BaselineVersionTest.php`, `src/tests/BaselineDependencyPinTest.php`, `src/tests/BaselineRuntimeVersionTest.php`.
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

- Cron and scheduled tasks wiring (S1.7; REQ-FR-PU-7): `crontab` schedules all nine Elgg cron intervals supported by Elgg 2.3.17 and `compose.yml` renders/runs the spool file; the sanctioned entry point is the core's documented HTTP cron endpoint invoked via `wget` (per `docs/analysis/S1.7-story-steps.md` Step 1; per-upgrade duty: move to the core CLI entry point when a later core version ships one), guarded by `src/tests/CronSanctionedEntryPointTest.php` and `src/tests/CronScheduleCoverageTest.php`; garbagecollector cleanup and uservalidationbyemail notification verified end-to-end with records in `docs/verification/` (`src/tests/CronCleanupVerificationTest.php`, `src/tests/CronValidationVerificationTest.php`); cron output log rotation with archive retention and prune replaces the dropped logrotate plugin (REQ-FR-PU-7), record `docs/verification/s1.7-logrotation-verification.md` (`src/tests/CronLogRotationTest.php`, `src/tests/CronLogRotationVerificationTest.php`); guard suite green 2026-10-26 at 117 tests / 1166 assertions.

- Logging, configuration &amp; environment management (S1.8): `settings.php` env-injection and secrets rules asserted by `src/tests/LoggingEnvironmentTest.php`, `src/tests/SecretsHandlingRulesTest.php`, `src/tests/ProductionSecretHygieneTest.php`, `src/tests/ContainerLogRoutingTest.php`; the Coolify environment-variable walk recorded green in `docs/baseline/baseline.md` (Production Environment Injection, Step 3 Manual Verification).

- Upgrade Step — Elgg 2.3 → Elgg 3 (S1.9; REQ-FR-PU-5 first hop, REQ-DEPL-4): `docs/baseline/baseline.md` (Upgrade-Stage Pin — Elgg 3.3.25 / PHP 7.4.27; Production Upgrade S1.9 Step 8 — admin area reports v3.3.25, pending list empty, backup-restore-recover sequence recorded); `docs/deployment/deploy_runbook.md` (S1.9 upgrade-sequence record, Run log 2026-11-05 commit `b84b6e1`); `docs/dependencies/S1.9-dependencies.md`; `docs/analysis/S1.9-story-steps.md`; `docs/workflow_state.md` records Story S1.9 complete (Steps 1–10 implemented and verified).
- Upgrade Step — Elgg 3 → Elgg 4 (S1.10; REQ-FR-PU-5, REQ-DEPL-4) — new since the third pass: `docs/baseline/baseline.md` Upgrade-Stage Pin (Story S1.10 Step 1) — elgg/elgg 4.3.10 pinned with PHP 7.4.27 unchanged (Packagist resolution via `composer show -a elgg/elgg`, 2026-11-07; preserved as the resolution record after S1.11 moved the active pin); `docs/dependencies/S1.10-dependencies.md` — transitive tree replacement (zendframework→laminas, symfony 3.4/4.4→~5.3, doctrine/dbal→~3.3.0, bower-asset→npm-asset renames) and security-advisory acceptances via `policy.advisories.ignore-id`; production deployed in sync — the S1.10 pre-deploy backup (`sfn-elgg-20261116T100100.sql.gz`, 96 MB, 2026-11-16, cited in `docs/verification/s1.11-backup-verification.md`) and that record's scope note confirming the VPS production database ran Elgg 4.3.10 until the S1.11 deploy; sprint file status: Completed 2026-11-16.
- Upgrade Step — Elgg 4 → Elgg 5 (S1.11; REQ-FR-PU-5, REQ-DEPL-4) — new since the third pass: `src/composer.json` pins `elgg/elgg: 5.1.12` with platform `php: 8.2.21` — the active pin, enforced by the pin guards; `docs/baseline/baseline.md` Upgrade-Stage Pin (Story S1.11 Step 1) — 5.1.12/8.2 resolved from Packagist p2 metadata, the (5.1.12, 8.3) pair proven unresolvable (elgg 5.1.12 exact-pins laminas/laminas-mime 2.11.0, excluding PHP 8.3) and the pin corrected to 8.2; Step 3 rebuild record (2026-11-17) — app image on `coldtrick/elgg_webserver:8.2`, vendor-tree sync completed, guard suite green (138 tests, 1158 assertions) with the pin guards updated to the new pair; Production Upgrade (Story S1.11 Step 9, 2026-11-17/18) — PHP 8.2.21 in production, Move Files 3403/3403 after the `limits.ini` raise, site email moved to `noreply@spiritualfamily.online`, deployed commit `87b34fe9`; `docs/dependencies/S1.11-dependencies.md` — transitive tree replacement, laminas-mime runtime-pin correction, lock content-hash refresh; `docs/verification/s1.11-backup-verification.md` — Step 8 pre-upgrade backup (97 MB, restorability verified into scratch `backup_verify`, 32 tables); `docs/verification/s1.11-cron-verification.md` — Step 7 scheduled-task verification under PHP 8.2.21 (minute/halfhour/fifteenmin completed post-recovery, zero wget errors, clean Elgg-log scan; stale `PHP_VERSION=7.4` export gotcha recorded with the explicit-pin recreate remedy); sprint file status: Completed 2026-11-18.

### B. Partially Implemented Features

None — no story has source evidence of partial progress without completion.

### C. Not Yet Implemented Features

- S1.12 — Upgrade Step — Elgg 5 → Elgg 6 (REQ-FR-PU-5, REQ-DEPL-4): the only unblocked open story
- S1.13 — Upgrade Step — Elgg 6 → Elgg 7.1.0 (REQ-FR-PU-5, REQ-DEPL-4): blocked behind S1.12
- S1.14–S1.16 — Plugin provisioning, activation, and theme skeleton at Elgg 7 (REQ-FR-PU-4, REQ-FR-TR-1): blocked behind S1.13
- S1.17 — Developer workflow and upgrade runbook documentation: blocked behind S1.15/S1.16
- All `REQ-FR-CF-*`, `REQ-FR-CMF-*`, `REQ-FR-PS-*` feature preservation — scheduled for restoration at Elgg 7 in S1.14–S1.15, by design.

## Priority Order for Next Implementation Phase

- Priority 1 - Platform upgrade sequence (S1.12):
  - Upgrade Step — Elgg 5 → Elgg 6: pin the latest stable 6.x with its required PHP version, execute the official upgrade tooling against the reused verified database copy, verify the site post-upgrade, deploy production in sync with a pre-upgrade backup, update the runbook
  - Rationale: REQ-FR-PU-5 mandates one major version at a time; S1.12 → S1.13 → S1.14 → S1.15 → S1.16 is a strict dependency chain and S1.12 is its head — the only unblocked open story

- Priority 2 - Feature restoration at Elgg 7 (S1.14–S1.16):
  - Plugin provisioning (S1.14), activation baseline (S1.15), theme skeleton (S1.16) — restore the REQ-FR-CF/CMF/PS/TR requirement set
  - Rationale: blocked by S1.13; strictly after the upgrade chain per REQ-FR-PU-5

- Priority 3 - Documentation (S1.17):
  - Developer workflow and upgrade runbook consolidation
  - Rationale: blocked by S1.15 and S1.16

## Sprint State

Latest sprint file: `docs/sprints/sprint_1_stories.md`
- S1.12: Open — basis: the platform pins elgg/elgg 5.1.12 (`src/composer.json`); no Elgg 6.x pin exists; no upgrade-tooling execution, post-upgrade verification, production deploy, or runbook-update evidence for the 5→6 hop
- S1.13–S1.17: Not Yet Implemented — blocked behind S1.12

Sprint state: OPEN

Next workflow step: `$planning-story-analysis S1.12`
