# Workflow State

Last updated: 2026-09-19

## Active Workflow

- Workflow: `$workflows-project-scaffolding-chain`
- Current step: Phase 7B `$testing-unit-test S1.1` active; Steps 1–3 tested; environment detour closed; Step 4 scenarios approved (5 tests mapped), implementing src/tests/BaselineInventoryTest.php
- Last completed: Environment detour finished — app image now builds locally (docker/app/Dockerfile: upstream PHP 7.4 webserver + Composer 2 phar + open_basedir CLI wrapper; compose.yml app service switched to build); verified in-container php -v 7.4.27 and composer 2.10.3; baseline.md environment note applied
- Next action: add composer to the orchestration — create `docker/app/Dockerfile` (FROM coldtrick/elgg_webserver:7.4 + COPY --from=composer:2) and switch the app service `image:` to `build:`; load `.agent/.aider.conventions/references/docker-best-practices.md` via /read-only first; re-verify `php -v` reports 7.4.27 and update the environment note in docs/baseline/baseline.md; then run `$testing-unit-test S1.1 4`
- Reload to resume:
  - `.agent/.aider.prompt/workflows/project-scaffolding-chain/SKILL.md`
  - `.agent/.aider.prompt/testing/unit-test/SKILL.md`
  - `.agent/.aider.prompt/testing/unit-test/references/gotchas.md`
  - `.agent/.aider.prompt/testing/unit-test/references/templates.md`
  - `docs/analysis/S1.1-story-steps.md`
  - `docs/baseline/inventory.md`
  - `docs/baseline/baseline.md`
  - `src/composer.json`
  - `src/phpunit.xml`
  - `src/tests/BaselineVersionTest.php`
  - `src/tests/BaselineRuntimeVersionTest.php`
  - `src/tests/BaselineDependencyPinTest.php`
  - `compose.yml`
