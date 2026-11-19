# Component Test Rules

Consolidated rules for `$testing-component-test`. Apply when writing new
component tests and when reviewing existing ones.

## Structure

- Title pattern: `When {scenario}, then {expectation}`.
- Three phases — Arrange, Act, Assert — with line breaks between; assertions only in the Assert phase.
- Max ~10 statements and max 3 assertions per test; totally flat: no try-catch, no loops, no console output.
- Self-contained: each test mounts its own component instance with its own data and never relies on other tests.

## Mocking boundaries (critical)

- Double ONLY external collaborators: third-party network edges and browser APIs (clipboard, observers, media, externally driven timers). Never mock child components, stores, routers, or internal state.
- Prefer network interception over function mocks for HTTP (the tool's interception facility, or MSW where supported).
- Doubles use the real types/interfaces of the mocked code, so contract changes fail fast; define and reset them in the test file or setup, never in external files.
- If a component cannot be tested without mocking an internal collaborator, do not silently mock it — flag it in the report as a design smell.

## Rendering and locators (critical)

- Mount the component with its real children and the providers/wrappers its real composition requires.
- Only user-facing locators: role, label, and text (e.g., `getByRole`, `getByLabel`, `getByText`). No CSS selectors, XPath, test IDs, or positional selectors (`nth()`, `first()`, `last()`).
- Exception: a test ID is acceptable for elements with no accessible role (error toasts, loading indicators) — prefer adding an accessible role first.
- Assert rendered output and user-visible behavior — never component instance internals, internal state, or emitted-event spies alone.

## Waiting and assertions

- No time-based waits; use the tool's auto-retriable assertions so the test retries until the outcome appears.
- Prefer one strong assertion over many weak ones; use matchers that show full diffs on failure.
- Match assertion capabilities to the runtime: jsdom renders no layout or visibility.

## Contracts

- Fixtures mirror the ACTUAL API response structure — copy real responses, document provenance (endpoint, date captured, backend version).
- Consume responses in tests exactly as the component does — this catches property-name mismatches.
- Include edge-case fixtures: empty arrays, null values, missing optional fields.

## Error handling

- Map HTTP status to the correct error state: 404 → NotFound, 401 → Unauthorized, 403 → Forbidden, 5xx → ServiceUnavailable. One test per error scenario.
- Assert the error state renders with context (message, resource identifiers); never a generic "throws anything" assertion.

## Data factories

- Build entities from factory functions with sensible defaults plus per-test overrides; type the params like the code under test.
- Meaningful domain data; faker for universal fields; arrays default to 2 items.
- Never rely on factory defaults for boolean flags under test — set both states explicitly.

## Test discipline

- Extra mile: testing a filtered list? Also assert items that should NOT appear. Testing a toggle? Render both states.
- Deliberate fire: choose options more likely to fail (boundary values, empty and overflow states).
- Bug fixes: amend the existing test that SHOULD have caught the bug, and verify it fails before applying the fix — don't just add a new test.
- Coverage audit: never propose a new test that duplicates existing coverage (EXISTS/PARTIAL/NOT COVERED gate in the test-plan step).
