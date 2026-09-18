# Sprint 1: Project Scaffolding Stories

Foundational scaffolding stories for the Spiritual Family Network, sequenced ground-up from the pinned Elgg 2.3 baseline through the sequential major-version upgrades to the Elgg 7.1.0 target. Grounded in `docs/vision/project_vision.md`, `docs/requirements/core_requirements.md`, `docs/tech_stack.md`, and `docs/architecture/architecture.md`.

---

Story S1.1: Elgg 2.3 Baseline Establishment
As a developer, I want the existing Elgg 2.3 platform captured as a clean, version-pinned baseline so that the upgrade journey starts from a reproducible ground-zero state.

Acceptance Criteria:
- The exact Elgg 2.3 core version in use is identified and pinned (⚠ currently "latest stable" of the 2.3 line — must be pinned before implementation)
- The PHP version required by Elgg 2.3 is pinned (⚠ exact version must be pinned)
- `composer.json` declares the pinned core and platform versions; `composer.lock` is committed
- The existing production database and data directory are inventoried (size, plugin set, settings) as the migration starting point
- The baseline builds and runs from a fresh checkout

Dependencies: None

Developer Notes:
- This is the ground-zero anchor for REQ-FR-PU-5; nothing is upgraded yet
- Record the pinned Elgg 2.3 and PHP versions in the docs

Definition of Done:
- Baseline builds and runs locally
- Pinned versions documented
- Required configuration files exist (`composer.json`, `composer.lock`)

---

Story S1.2: Docker Compose Environment (Version-Parameterized)
As a developer, I want a Docker Compose environment parameterized by Elgg and PHP version so that every step of the 2.3→7 upgrade journey builds from the same Compose files.

Acceptance Criteria:
- Web service built from a PHP-version-parameterized Dockerfile (build arg; ⚠ each PHP base image tag pinned per step) serving Elgg's entry points (REQ-DEPL-1)
- Docker Compose orchestrates web, db, and cron services (REQ-DEPL-2)
- MailHog service included for development only: catches all outgoing Elgg email (SMTP on its standard port), exposed via its web UI, and excluded from production Compose overrides (dev-only profile/override file)
- Elgg SMTP settings point to MailHog in development via environment variables (e.g., uservalidationbyemail and notification emails are catchable in dev)
- MySQL version pinned (⚠ currently "latest stable" — must be pinned; must remain compatible across the journey or be upgraded alongside)
- Persistent volumes for MySQL data and the Elgg data directory
- Settings and secrets injected via environment variables, never hard-coded
- Build args accept any (Elgg version, PHP version) pair so intermediate upgrade steps are buildable (REQ-FR-PU-5)

Dependencies: S1.1

Developer Notes:
- Single web container keeps Elgg caches from drifting
- The same Compose definition must serve Elgg 2.3 through Elgg 7 — avoid Elgg-7-only assumptions
- MailHog image tag pinned (⚠ pin exact version); it is developer tooling, never deployed to production

Definition of Done:
- `docker compose up` brings up web, db, cron, and MailHog for the Elgg 2.3 baseline
- An Elgg-triggered email (e.g., user validation) appears in the MailHog UI
- DB and file-store data persist across restarts
- Pinned image/DB versions recorded

---

Story S1.3: Elgg 2.3 Installation and Configuration in Compose
As a developer, I want the pinned Elgg 2.3 baseline installed and configured inside the Compose environment so that the legacy platform runs reproducibly before any upgrade work begins.

Acceptance Criteria:
- Elgg 2.3 (pinned version) installed against the MySQL service; schema installs cleanly
- `settings.php` generated from environment variables; committed template documents every required variable (secrets excluded)
- Elgg data directory provisioned on its persistent volume with correct permissions
- Site loads and an admin can log in
- A production database dump restores successfully into the containerized DB (migration-readiness check)

Dependencies: S1.2

Developer Notes:
- This proves the container stack faithfully hosts the legacy platform before upgrades start
- Never commit credentials or secrets

Definition of Done:
- Site loads locally with the Elgg 2.3 baseline
- `settings.php` template complete
- Production dump restore verified

---

Story S1.4: Third-Party Plugin Drop and Dropped-Plugins List
As a developer, I want all third-party and legacy plugins dropped with a recorded disposition list so that the codebase is upgrade-clean per the upgrade requirements.

Acceptance Criteria:
- All third-party plugins are removed from the codebase (REQ-FR-PU-2)
- A dropped-plugins list is produced recording, for each plugin, whether an upgraded Composer replacement exists or it must be installed manually (REQ-FR-PU-3); plugins without replacements remain on the list for a later sprint (REQ-FR-PU-6)
- Elgg 2.3 core plugins not bundled in Elgg 7.1.0 are dropped: embed, legacy_urls, diagnostics, logbrowser, logrotate, login_as (REQ-FR-PU-7)
- Legacy theme stack dropped: aalborg_theme, aalborg extras/L1, paul_override (REQ-FR-TR-1 drop half; new theme arrives in S1.16)
- Site still functions on core/bundled views after the drops

Dependencies: S1.3

Developer Notes:
- Cross-check the drop list against `docs/tech_stack.md` (Permanently Dropped and Manual Installs sections)
- custom_index handling completes at Elgg 7 in S1.16 (REQ-FR-TR-1)

Definition of Done:
- Codebase contains no third-party plugins
- Dropped-plugins list committed with per-plugin disposition
- Site loads after drops

---

Story S1.5: Initial Build Pipeline (CI)
As a developer, I want CI that validates the pinned build so that every change keeps the foundation installable and reproducible across the whole upgrade journey.

Acceptance Criteria:
- CI runs on the pinned Elgg 2.3-era PHP version: `composer validate`, `composer install` from lock, PHP syntax lint
- `composer.json` ↔ `composer.lock` consistency enforced
- Docker image build validated in CI using the version build args
- Workflow files follow the repository's CI/CD conventions

Dependencies: S1.1, S1.2

Developer Notes:
- No deployment steps in this story — deployment is S1.6

Definition of Done:
- Green CI run
- Workflow configuration files exist
- Lint checks pass

---

Story S1.6: Coolify Deployment of the Elgg 2.3 Baseline
As a developer, I want the cleaned Elgg 2.3 baseline deployed to Coolify so that version-pinned production deployments are proven before the first upgrade step (REQ-DEPL-3, REQ-DEPL-4).

Acceptance Criteria:
- Coolify application defined for the web, db, and cron services from the repo's Compose files
- Deployment fully version-pinned and reproducible from the repository (no floating tags)
- Secrets injected via Coolify environment settings; never committed
- HTTPS entry routing terminates at the web container
- MailHog and any dev-only Compose overrides are excluded from the production deployment; production email uses real SMTP configured via Coolify environment settings
- Production database migrated into the deployed stack and verified in sync with the baseline
- A clean re-deploy reproduces the identical stack (verified once)

Dependencies: S1.4, S1.5

Developer Notes:
- This is the REQ-DEPL-4 starting point: production runs the pinned Elgg 2.3 baseline before any upgrade
- Database backup step belongs in the deploy runbook (documented here, automated later)

Definition of Done:
- Production site reachable via HTTPS running the pinned Elgg 2.3 baseline
- Clean re-deploy reproduces the stack
- App runs locally and in production

---

Story S1.7: Cron and Scheduled Tasks Wiring
As a developer, I want the cron container executing Elgg's cron hooks so that scheduled maintenance runs at every stage of the journey without the dropped core plugins.

Acceptance Criteria:
- Cron service triggers all Elgg cron intervals supported by the current Elgg version in the sequence
- garbagecollector and uservalidationbyemail scheduled runs verified end-to-end
- Cron invokes the core through the same CLI entry point web requests use
- Container log rotation configured for cron output (replaces dropped logrotate, REQ-FR-PU-7)

Dependencies: S1.3, S1.4

Developer Notes:
- Schedules kept in committed Compose/cron config; command syntax re-verified at each upgrade step

Definition of Done:
- Cron logs show successful execution per interval
- Scheduled tasks complete without errors
- Configuration files committed

---

Story S1.8: Logging, Configuration & Environment Management
As a developer, I want centralized logging and documented environment configuration so that every upgrade step is observable and configurable per environment.

Acceptance Criteria:
- Elgg logging configured per environment with registered error/exception handlers
- A committed, documented `.env` template covers every variable required by `settings.php` and Compose (no real secrets)
- Secrets-handling rules documented (Compose/Coolify injection only)
- Container logs route to stdout/log files with rotation defined

Dependencies: S1.3

Developer Notes:
- Surface deprecation notices during upgrade steps — they signal upcoming breakage at the next major version

Definition of Done:
- Logs observable in dev and production
- `.env` template committed and documented

---

Story S1.9: Upgrade Step — Elgg 2.3 → Elgg 3
As a developer, I want the first sequential upgrade executed and deployed so that the platform moves one major version along the supported path with production in sync (REQ-FR-PU-5, REQ-DEPL-4).

Acceptance Criteria:
- Target Elgg 3.x version pinned (⚠ "latest stable" of the 3 line — pin exact before implementation) together with its required PHP version (⚠ pin exact)
- Official Elgg upgrade tooling/migration executed successfully against a copy of the production database
- Site verified functional post-upgrade (login, core pages, no fatal errors)
- Production deployed at this upgrade point so the production database is upgraded in sync (REQ-DEPL-4), with a pre-upgrade database backup taken
- Upgrade steps documented in the runbook for reuse

Dependencies: S1.4, S1.6

Developer Notes:
- Plugin set is clean (S1.4), so incompatibilities should surface as core-level issues only
- Never deploy an upgrade to production without a verified backup

Definition of Done:
- Local and production run the pinned Elgg 3.x
- Backup-then-upgrade-then-verify sequence executed in production
- Runbook updated

---

Story S1.10: Upgrade Step — Elgg 3 → Elgg 4
As a developer, I want the second sequential upgrade executed and deployed so the platform continues along the supported path with production in sync.

Acceptance Criteria:
- Target Elgg 4.x version pinned (⚠ pin exact) together with its required PHP version (⚠ pin exact)
- Official upgrade tooling executed against a copy of the production database
- Site verified functional post-upgrade
- Production deployed at this upgrade point with pre-upgrade backup (REQ-DEPL-4)
- Runbook updated

Dependencies: S1.9

Developer Notes:
- Re-verify cron command syntax against Elgg 4 conventions

Definition of Done:
- Local and production run the pinned Elgg 4.x
- Backup-then-upgrade-then-verify executed in production
- Runbook updated

---

Story S1.11: Upgrade Step — Elgg 4 → Elgg 5
As a developer, I want the third sequential upgrade executed and deployed so the platform continues along the supported path with production in sync.

Acceptance Criteria:
- Target Elgg 5.x version pinned (⚠ pin exact) together with its required PHP version (⚠ pin exact)
- Official upgrade tooling executed against a copy of the production database
- Site verified functional post-upgrade
- Production deployed at this upgrade point with pre-upgrade backup (REQ-DEPL-4)
- Runbook updated

Dependencies: S1.10

Developer Notes:
- PHP version changes from here on — rebuild images with the pinned per-step base image

Definition of Done:
- Local and production run the pinned Elgg 5.x
- Backup-then-upgrade-then-verify executed in production
- Runbook updated

---

Story S1.12: Upgrade Step — Elgg 5 → Elgg 6
As a developer, I want the fourth sequential upgrade executed and deployed so the platform continues along the supported path with production in sync.

Acceptance Criteria:
- Target Elgg 6.x version pinned (⚠ pin exact) together with its required PHP version (⚠ pin exact)
- Official upgrade tooling executed against a copy of the production database
- Site verified functional post-upgrade
- Production deployed at this upgrade point with pre-upgrade backup (REQ-DEPL-4)
- Runbook updated

Dependencies: S1.11

Developer Notes:
- Confirm no deprecated-API usage that would block the final Elgg 7 step

Definition of Done:
- Local and production run the pinned Elgg 6.x
- Backup-then-upgrade-then-verify executed in production
- Runbook updated

---

Story S1.13: Upgrade Step — Elgg 6 → Elgg 7.1.0
As a developer, I want the final sequential upgrade executed and deployed so the platform reaches the pinned Elgg 7.1.0 target on PHP 8.3 with production in sync.

Acceptance Criteria:
- elgg/elgg 7.1.0 pinned with PHP 8.3 (per `docs/tech_stack.md`)
- Official upgrade tooling executed against a copy of the production database
- Site verified functional post-upgrade on PHP 8.3 with no deprecation/fatal noise
- Production deployed at this upgrade point with pre-upgrade backup (REQ-DEPL-4)
- Runbook updated; upgrade sequence complete

Dependencies: S1.12

Developer Notes:
- This completes REQ-FR-PU-5; feature restoration follows in S1.14–S1.16

Definition of Done:
- Local and production run elgg/elgg 7.1.0 on PHP 8.3
- Backup-then-upgrade-then-verify executed in production
- Runbook updated

---

Story S1.14: Plugin Provisioning at Elgg 7 (Composer, Bundled, Manual)
As a developer, I want the required Elgg 7 plugin set provisioned with exact, recorded versions so that dropped functionality is restored on the target platform.

Acceptance Criteria:
- Composer plugins added with exact versions per `docs/tech_stack.md`: coldtrick/event_manager v21.2, coldtrick/newsletter v13.0, coldtrick/questions v15.0, coldtrick/site_announcements v13.0, coldtrick/group_tools v23.2, coldtrick/blog_tools v17.0, coldtrick/profile_manager v23.0, coldtrick/widget_manager v18.0.1, coldtrick/analytics v5.0, elgg/data_views 1.0.0
- htmlawed/htmlawed pinned to an exact version (⚠ currently ~1.2.15 constraint only — must be pinned before implementation)
- A manual-plugins directory plus version manifest exists for non-Packagist plugins (sbw_campaigns, videos, faq, tidypics, views_counter, captcha); each version recorded at install time (⚠ recorded-at-install versions must be locked in the manifest)
- `composer.lock` updated and committed
- No permanently dropped plugins reintroduced (`docs/tech_stack.md` drop list)

Dependencies: S1.13

Developer Notes:
- Cross-check pinned versions against the compatibility matrix in `docs/tech_stack.md`
- Manual manifest entries must never say "latest"

Definition of Done:
- `composer install` provisions all vendor plugins at pinned versions
- Manual-plugin manifest complete with recorded versions
- All plugins load without PHP 8.3 fatal errors

---

Story S1.15: Plugin Activation and Site Baseline at Elgg 7
As a developer, I want the required plugin set activated through a repeatable script so that the runtime baseline matches the requirements on every environment.

Acceptance Criteria:
- Required bundled Elgg 7.1.0 plugins activated: blog, bookmarks, ckeditor, discussions, externalpages, thewire, tagcloud, search, likes, groups, members, invitefriends, messages, site_notifications, uservalidationbyemail, reportedcontent, web_services, developers, activity, dashboard, file, friends, friends_collections, garbagecollector, messageboard, pages, profile, system_log, theme_sandbox
- The bundled custom_index plugin deactivated (final handling in S1.16 per REQ-FR-TR-1)
- Core notifications service active (PS-1 baseline)
- Activation runs via committed elgg-cli/provisioning commands and is idempotent
- Site browses with the baseline active and no activation errors

Dependencies: S1.14

Developer Notes:
- Follow Elgg 7 plugin priority/ordering when activating
- garbagecollector + system_log cover the dropped logrotate/logbrowser duties (REQ-FR-PU-7)

Definition of Done:
- Provisioning script idempotent against a fresh install
- Admin can browse the site with the baseline plugin set
- No activation errors in the Elgg log

---

Story S1.16: Theme Plugin Skeleton
As a developer, I want the new theme plugin skeleton activated so that the legacy theme stack replacement is completed at the foundation level (REQ-FR-TR-1).

Acceptance Criteria:
- New theme plugin created with a valid Elgg 7 plugin skeleton (manifest, elgg-plugin.php/start.php, views structure) and activates on Elgg 7.1.0
- Bundled custom_index deactivated or its front page overridden by the theme (REQ-FR-TR-1)
- Legacy theme plugins confirmed absent (dropped in S1.4)
- No core view files patched; overrides only via the Elgg views system
- Site renders with the theme skeleton active

Dependencies: S1.15

Developer Notes:
- Keep the skeleton minimal; visual styling is later sprint work

Definition of Done:
- Theme skeleton activates and the site renders correctly
- custom_index deactivated or overridden
- No PHP 8.3 errors from the theme plugin

---

Story S1.17: Developer Workflow and Upgrade Runbook Documentation
As a developer, I want the daily workflow and the completed upgrade runbook documented so that the team can develop and execute future upgrade points reproducibly.

Acceptance Criteria:
- README documents the local workflow: `composer install`, `docker compose up`, Elgg install, plugin provisioning/activation, cache clear/warm
- The upgrade runbook (produced across S1.9–S1.13) is consolidated and committed, covering backup-then-upgrade-then-deploy per step (REQ-DEPL-4 pattern for future upgrade points)
- Docs follow the repository's GitHub-Flavored Markdown conventions

Dependencies: S1.15, S1.16

Developer Notes:
- The runbook is the reusable template for any future Elgg major-version moves

Definition of Done:
- A new developer can build and run the stack from the README alone
- Runbook committed and complete
- Documentation files lint clean

---

## Technical Dependencies Graph

- S1.1 → S1.2 → S1.3 → S1.4
- S1.1 + S1.2 → S1.5
- S1.4 + S1.5 → S1.6
- S1.3 + S1.4 → S1.7
- S1.3 → S1.8
- S1.4 + S1.6 → S1.9 (2.3→3) → S1.10 (3→4) → S1.11 (4→5) → S1.12 (5→6) → S1.13 (6→7.1.0)
- S1.13 → S1.14 → S1.15 → S1.16
- S1.15 + S1.16 → S1.17

## Verification Checkpoints

- After S1.1: pinned Elgg 2.3 + PHP versions documented; baseline reproducible
- After S1.2: `docker compose up` on the 2.3 baseline; volumes persist
- After S1.3: site loads; production dump restores into containerized DB
- After S1.4: codebase plugin-clean; dropped-plugins list committed
- After S1.5: green CI including parameterized image build
- After S1.6: production runs pinned Elgg 2.3 over HTTPS; clean re-deploy reproduces it
- After S1.7: cron intervals succeed
- After S1.8: logs observable; `.env` template documented
- After S1.9–S1.13 (each): backup taken → upgrade executed → site verified → production deployed in sync
- After S1.14: installed versions match the tech-stack compatibility matrix; manual manifest complete
- After S1.15: provisioning idempotent; zero activation errors
- After S1.16: site renders on theme skeleton; custom_index deactivated/overridden
- After S1.17: fresh developer productive from README; runbook complete

## Version Pinning Flags

- Elgg 2.3 exact version + its PHP version — pin in S1.1
- MySQL server version — flagged "latest stable", pin in S1.2
- PHP Docker base image tags — pin per step in S1.2/S1.9–S1.13
- MailHog image tag — pin exact in S1.2
- Intermediate Elgg 3.x/4.x/5.x/6.x versions + their PHP versions — flagged "latest stable", pin per step in S1.9–S1.12
- htmlawed/htmlawed ~1.2.15 — pin exact in S1.14
- Manual-install plugin versions (recorded at install) — lock in manifest in S1.14
