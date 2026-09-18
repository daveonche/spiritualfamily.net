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
