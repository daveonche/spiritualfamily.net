# Elgg 2.3 Baseline

Ground-zero version reference for the Spiritual Family Network upgrade journey
(Story S1.1). Every later upgrade step cites these pinned values.

## Pinned Versions

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core | 2.3.17 | `src/vendor/elgg/elgg/composer.json` (`"version": "2.3.17"`) |
| PHP runtime | 7.4.27 | Legacy environment Compose stack, app image `coldtrick/elgg_webserver:7.4`, confirmed via `docker compose exec app php -v` |

This is the single pinned Elgg 2.3 core version; it is the reference value for
all subsequent steps in the upgrade sequence (REQ-FR-PU-5).

The pinned PHP runtime sits directly beneath the core version so future steps
can cite them as a pair. Constraint note: the pinned Elgg 2.3.17 core declares
`"php": ">=5.6"` (see `src/vendor/elgg/elgg/composer.json`); 7.4.27 satisfies
that constraint.

## Cross-Check Notes

- The repository-root `composer.json` was overwritten in a previous session and
  currently declares Elgg 7 requirements; it must be corrected back to this
  pinned baseline in Step 3 of Story S1.1 and is therefore not usable as a
  version source.
- Manual verification for this pin: confirm `$release` in
  `src/vendor/elgg/elgg/version.php` (the vendored Elgg 2.3.17 core root)
  reads `2.3.17`, and cross-check against the version reported by the running
  legacy site's administration area.

## Fresh-Checkout Verification

Story S1.1 Step 5 executed `docs/baseline/fresh_checkout.md` from a clean
checkout and confirmed the baseline builds and runs (acceptance criterion:
baseline builds and runs from a fresh checkout).

| Item | Result |
| :--- | :--- |
| Verification date | 2026-09-18 |
| Commit verified | `951ef51` (the commit adding `docs/baseline/fresh_checkout.md`) |
| Site front page | Loaded at the site URL |
| Admin login and `/admin` | Logged in; administration area reachable and functional |
| `php -v` | PHP 7.4.27 (cli), built Jan 26 2022 — matches the pinned PHP runtime |
| Deviations / findings | None |

Remaining manual steps are listed at the bottom of
`docs/baseline/fresh_checkout.md` (web installer, OS hosts entry, optional
host-side Composer check, admin credential recording).
