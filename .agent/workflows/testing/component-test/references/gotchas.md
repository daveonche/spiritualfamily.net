# Component Test Gotchas

Load before starting `$testing-component-test`.

- `S<X.Y>` story IDs must come from the sprint stories file (chain Phase 2); do not guess them.
- Run this gate only after the story's Phase 4A/4B (or 7A/7B) unit iteration loop has completed for the story's steps — component tests assert implemented components, not per-step progress.
- Do not run component tests and unit tests in the same command; different targets, different speeds.
- Detect the component tool and its runtime from the dependency definition file and config; write in that idiom — do not import Playwright CT patterns into Cypress CT, Storybook, or Testing Library, or vice versa.
- Know your runtime: jsdom-based runners render no layout or visibility — visibility and positioning assertions need a real browser runtime (e.g., Playwright CT, Cypress). Match assertion types to the runtime actually configured.
- Mount components in isolation with their real child components, providers, and wrappers; never stub child components, stores, routers, or internal state — doubles are allowed only at external edges (network, browser APIs).
- Prefer network interception over function mocks for HTTP edges; use the tool's interception facility (e.g., Playwright `page.route`, Cypress `cy.intercept`, MSW where supported).
- No time-based waits (`sleep`, `waitForTimeout`); drive async rendering through the tool's auto-retriable assertions and waiting facilities.
- Fixtures must mirror real API responses with provenance (endpoint, date captured, backend version); never invent fantasy shapes.
- A flaky component test is a failed test: fix it or quarantine it with a note in the report; never ignore reds.
- Component tests complement unit tests — they do not re-test mocked logic; the test-plan coverage audit exists to prevent duplication.
- Real backend edges: point interception or test servers at dedicated test instances; never at shared or production systems.
- Bracketed items in templates (e.g., `[filename]`, `[chosen filename]`) are placeholders — resolve them from context, never output them literally.
