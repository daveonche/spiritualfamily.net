# Elgg Developer Guide: Testing

Delta distillation of the elgg-test-writer skill (upstream store
`tmp/upstream/elgg-llm-skills`, distilled 2026-10-28). Records only what
counters stale training-data memory: version-specific test APIs, base
classes, session handling, and the failure modes that recur across
version transitions. Update in place when the upstream skill changes —
not from manual diffs (there is no `testing.rst`).

## PHPUnit base classes and session API by version

| Elgg | Unit base | Integration base | Session API |
| :--- | :--- | :--- | :--- |
| 2.x | `PHPUnit\Framework\TestCase` | custom bootstrap | N/A |
| 3.x | `\Elgg\UnitTestCase` | `\Elgg\IntegrationTestCase` | `elgg_get_session()->setLoggedInUser()` |
| 4.x | `\Elgg\UnitTestCase` | `\Elgg\IntegrationTestCase` | `elgg_get_session()->setLoggedInUser()` |
| 5.x+ | `\Elgg\UnitTestCase` | `\Elgg\IntegrationTestCase` | `_elgg_services()->session_manager->setLoggedInUser()` |

- Gotcha: `session_manager` does not exist in 4.x — using it there is a
  fatal; `elgg_get_session()` is the 3.x/4.x API.

## Integration test environment

- `IntegrationTestCase` uses the DB prefix `c_i_elgg_`: create the test
  tables first (clone the schema from the `elgg_` tables) and re-sync
  entities/metadata/relationships/config after activating or
  deactivating plugins — the test DB is a snapshot, it does not
  auto-sync.
- IntegrationTestCase auto-skips tests when the plugin is not active in
  the test DB, and does not register an inactive plugin's `classes/`
  autoloader — register PSR-4 manually in the bootstrap if you cannot
  activate it.
- Every concrete test class must define both `public function up() {}`
  and `public function down() {}` — even empty — or the class fatals at
  load time.
- `$this->createObject()` requires `['subtype' => '...']` in 4.x.
- `$this->executeAction()` exists only in `ActionResponseTestCase`, not
  `IntegrationTestCase` — test entity behavior directly instead.

## Hooks and events in tests

- `\Elgg\Hook` is an interface (4.x): mock it with
  `$this->getMockBuilder(\Elgg\Hook::class)->getMock()` and stub
  `getValue`/`getParam`/`getType`; never instantiate it and never import
  the internal concrete class (its mock constructor cannot be supplied).
- 4.x event handlers receive one `\Elgg\Event` object; test closures
  with the legacy 3-arg signature throw argument-count errors.
- The `register, menu:*` hook's `$hook->getValue()` returns a
  `MenuItems` collection, not an array — use `->merge($items)`, not
  `array_merge()`.
- Legacy 3-arg handlers in *other active plugins* crash every
  integration test (Elgg 4 always passes one object); fix the handler
  with a polymorphic guard (`if ($event instanceof \Elgg\Event)`) or
  deactivate the plugin.

## Fixtures and plugin settings

- If the plugin owns entity types, ship a `Seeder` subclass extending
  `\Elgg\Database\Seeds\Seed` and reuse it as the canonical fixture
  source; ad-hoc `$this->createObject()` fixtures are auto-tagged and
  cleaned up, but fixtures mirroring the plugin's real entity shape
  must come from the Seeder.
- `elgg_set_plugin_setting()` / `elgg_get_plugin_setting()` were removed
  in 4.x — use `elgg_get_plugin_from_id('<id>')->setSetting()` /
  `->getSetting()`.
- 3.x normalizes plugin IDs to lowercase:
  `elgg_get_plugin_from_id('hypeFoo')` returns null for the plugin
  registered as `hypefoo`.

## Unit vs integration boundary

- Unit tests (`UnitTestCase`) do not boot the app: no database, no
  plugins, and `elgg_view_exists()` returns false — never test views,
  hook registration, or entities there. If the test needs Elgg
  functions, it is an integration test.

## Version pinning

- Pin PHPUnit to the container's PHP, not the Elgg version: PHP 7.2 →
  PHPUnit 8.x; PHP 7.4 → 9.x; PHP 8.1+ → 10.x. Elgg Docker images do
  not ship PHPUnit — `composer require --dev` it first.

## Tests-first migrations

- Before editing plugin code for a version migration, generate and run
  the baseline test (GREEN before and after — the behavior net) and the
  migration regression test (RED before, GREEN after — proves the
  failure classes were present and then fixed). A migration that never
  showed RED never proved it fixed anything.

## JavaScript test stack

- Elgg has no built-in JS test framework; plugin JS brings its own —
  Vitest for unit tests, Playwright for browser-level behavior.
- Vitest aliases must use the array form, most specific first: an
  object alias with a bare `'elgg'` key ahead of `'elgg/Ajax'` rewrites
  the submodule import and breaks it; replacements must be absolute
  URLs, not importer-relative paths.
- Mock `elgg/i18n` with a default export (7.x's real module has a
  default export only); mock jQuery by binding the real library to the
  jsdom window — hand-rolled `$` mocks throw on the first `.on()` /
  `.each()` / `.addClass()` call.
- Playwright: set `workers: 1` (parallel workers race the shared DB
  state); assert both UI state and database state — a form can return
  200 without saving; give every page type a console/pageerror smoke
  gate (HTTP 200 does not mean the JS ran).
