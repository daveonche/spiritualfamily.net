# Elgg 2.3 Baseline

Ground-zero version reference for the Spiritual Family Network upgrade journey
(Story S1.1). Every later upgrade step cites these pinned values.

## Pinned Versions

| Component | Pinned version | Source |
| --- | --- | --- |
| Elgg core | 2.3.17 | `src/vendor/elgg/elgg/composer.json` (`"version": "2.3.17"`) |

This is the single pinned Elgg 2.3 core version; it is the reference value for
all subsequent steps in the upgrade sequence (REQ-FR-PU-5). The PHP version
required by this Elgg release is recorded here in Step 2 of Story S1.1.

## Cross-Check Notes

- The repository-root `composer.json` was overwritten in a previous session and
  currently declares Elgg 7 requirements; it must be corrected back to this
  pinned baseline in Step 3 of Story S1.1 and is therefore not usable as a
  version source.
- Manual verification for this pin: confirm `$release` in `src/version.php`
  reads `2.3.17`, and cross-check against the version reported by the running
  legacy site's administration area.
