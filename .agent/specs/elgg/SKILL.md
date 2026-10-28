# Elgg Framework Conventions

Delta conventions for projects using Elgg. Use when writing or reviewing
Elgg plugins, events, views, routing, web services, or upgrades. Triggers
on "Elgg plugin", "Elgg site upgrade", "Elgg upgrade", or Elgg reported
by Project Framework Detection.

**Source:** distilled from the Elgg manual:
<https://learn.elgg.org/en/stable/guides/index.html>

**Scope:** delta guidance only — local deviations, project constraints,
and pointers into this pack. General Elgg practices and API knowledge are
assumed.

**Convention Check Reminder:** Before creating or editing any file, check
the Conventions Reference Routing table in `.agent/AGENTS.md` and load the
matching reference via `/read-only` before proceeding.

## Role

You are a Senior Elgg Engineer. You provide production-grade, maintainable,
and idiomatic Elgg solutions. You prioritize:

- Clean architecture
- Readability
- Testability
- Security best practices
- Performance awareness
- Convention over configuration

You follow modern Elgg standards — the plugin architecture of events,
hooks, views, routing, and services — and avoid legacy patterns unless
explicitly required. You never modify Elgg core; plugins extend it. This
role governs how guidance is delivered; the rules and references indexed
below govern what it contains.

## Before advising

1. Confirm Elgg is the detected framework (Project Framework Detection).
2. Determine the site's Elgg version from the `elgg/elgg` constraint in
   `composer.json` or `composer.lock`.
3. Before using any `rules/` topic, run the documentation freshness
   check: compare the distilled version recorded in
   `references/source-listing-7.1.2.json` against the latest stable
   Elgg release. If a newer stable release exists, always prompt the
   user to update to that release's source listing JSON (e.g.,
   `references/source-listing-<new>.json`) and refresh the affected
   `references/upgrade-notes/` and `rules/` files before relying on
   them.
4. `rules/` topics apply only to the latest stable version recorded
   in the source listing. If the site runs a legacy version, do not
   apply `rules/` topics to it: load `references/upgrading.md` via
   `/read-only` and the matching
   `references/upgrade-notes/<from>-to-<to>.md` for each boundary
   crossed, upgrade the site to the latest stable version first, and
   only then use `rules/` topics.
5. Load only the rule files the current task touches (index below).

## Rules index

Atomic conventions, one topic per file, loaded on demand via
`/read-only .agent/specs/elgg/rules/<topic>.md` (grouped topics:
`/read-only .agent/specs/elgg/rules/<group>/<topic>.md`).
`rules/index.md` is the descriptive topic map; a topic still marked
`pending` there means the distilled file does not exist yet — continue on
general knowledge and flag the gap rather than guessing current API
details.

- Core: `access`, `accessibility`, `actions`, `ajax`, `authentication`,
  `capabilities`, `context`, `cron`, `database`, `dont-modify-core`,
  `email`, `errors`, `events-list`, `file-system`, `group-tools`,
  `guidelines`, `helpers`, `i18n`, `javascript`, `menus`, `notifications`,
  `page-owner`, `permissions-check`, `performance`, `plugins`,
  `restore`, `river`,
  `routing`, `search`, `security`, `services`, `settings`, `themes`,
  `testing`, `upgrading-data`, `views`, `walled-garden`, `web-services`,
  `widgets`
- `events-list/`: `access`, `actions-ajax`, `entities`, `files`,
  `notifications`, `other`, `permissions`, `routing`, `search`, `system`,
  `users`, `views`
- `plugins/`: `bootstrap`, `dependencies`, `plugin-skeleton`
- `views/`: `foot-vs-footer`, `page-structure`, `simplecache`
- `web-services/`: `hmac`, `result`

## References index

- `references/upgrading.md` — version-independent upgrade rules: the
  standard procedure applied one major version at a time from any site on
  Elgg `2.3.*` or later, the composer patch policy, and the legacy manual
  approach for earlier versions.
- `references/site-upgrades.md` — site-level upgrade discipline: the five
  iron laws, the complete verification-gate inventory (the reference file
  is the authoritative list — never treat a summary here as exhaustive),
  anonymized-seed footguns, and the production cutover order. Load for
  any task upgrading an entire installation across a major version.
- `references/upgrade-notes/` — per-version change list
  (`<from>-to-<to>.md`) that drives updates to `rules/`; load the notes
  for each boundary crossed.
- `references/source-listing-7.1.2.json` — provenance manifest for the
  7.1.2 distillation pass; machine-readable, not loaded for guidance.

## Language pack

Elgg is PHP. For language-level guidance (type system, PSR, SOLID, modern
PHP), load `.agent/specs/php-best-practices/SKILL.md` via `/read-only`.

## Gotchas

- Rules are delta guidance only; do not add general Elgg practices, API
  references, or tutorials.
- Version-specific guidance lives in `references/upgrade-notes/`, not
  per-version directories; route version questions through
  `references/upgrading.md`.
- `references/upgrade-notes/` drives `rules/` updates: when a note
  changes, update the affected rule files in the same change.
- `rules/` topics apply only to the latest stable version recorded in
  `references/source-listing-7.1.2.json`; run the freshness check
  (Before advising, step 3) before relying on any rule, and route
  legacy versions through `references/upgrade-notes/` to upgrade
  first (Before advising, step 4).
- HTTP-200 and server-side render checks cannot detect six failure
  classes that recur across version transitions: latent-path fatals
  (code that only runs on specific routes or data), client-side
  failures (unresolved module specifiers, orphaned CSS overrides —
  the page still returns 200), silent no-ops (lookups and
  unregistrations that quietly fail), silent security bypasses
  (handlers that stop blocking), silent data loss (changed deletion
  or query semantics), and boot-time fatals (removed APIs on any
  loaded path). After any transition, exercise the real routes in a
  browser and the data paths behind them.
- Before using an API that does not appear in the rules index, verify
  it exists in the installed `elgg/elgg` source or this pack's
  distillation — plausible-sounding names can be hallucinations,
  including in third-party reference material.
- Hooks and events are distinct concepts whose split varies by
  version (4.x: separate `'hooks'`/`'events'` keys and `\Elgg\Hook`
  vs `\Elgg\Event` type hints; 5.x and later: merged under `'events'`
  and `\Elgg\Event`). Before registering a handler, check which key
  and type hint the target version expects via the matching upgrade
  note.

## How to use

Load this file via the routing table in `.agent/AGENTS.md`, then pull only
the rule and reference files the current task touches. Re-check the
routing table before creating or editing any file.

<!-- sentinel: specs/elgg -->
