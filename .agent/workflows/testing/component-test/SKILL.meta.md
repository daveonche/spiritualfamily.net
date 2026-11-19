# Component Test Generation — Workflow Metadata

## AI Assistant Compatibility
- Designed for aider-driven orchestration per `.agent/AGENTS.md` (staged execution, `/ask` ↔ `/code` mode gates, one-line `/read-only` and `/drop` commands).
- Tool-agnostic: works with any assistant that can follow staged prompts and run the project's detected component test runner in a container.

## SDLC Phase
- Testing (sprint execution): per-story gate alongside `$testing-integration-test`, after the story's unit iteration loop (Phase 4A/4B or 7A/7B) and before sprint close (`$testing-e2e-test`, `$code-security-audit`).

## Complexity Rating
- Moderate: structured steps with STOP gates; the main complexity is tool/runtime detection and mocking-boundary discipline.

## Usage Guidelines
- Invoke with `$testing-component-test S<X.Y>` after the story's components are implemented and unit-tested.
- Use `#component-test-status` to resume after a session break; never use it to advance state.
- Requires the story steps report, sprint story, component implementation files, dependency definition file, and (when present) component tooling config in context.

## Prompt Characteristics
- Role: Component Test Specialist (per-story UI-layer gate).
- Style: staged, STOP-gated, exact-output templates; lean core with on-demand references.
- Inputs: story docs, component sources, dependency definition file, tooling config.
- Outputs: approved inventory and plan, green component test suite, `docs/testing/S<X.Y>_component_report.md`.

## Best Practices
- Mount real children; double only external edges (network, browser APIs).
- User-facing locators only; auto-retriable assertions; no time-based waits.
- Fixtures copied from real API responses with provenance; coverage audit before any new test.

## Potential Challenges
- Component tooling absent in the project (the common case) — resolved at the inventory step via `$code-dependency-management`.
- jsdom vs real-browser runtime confusion — visibility assertions are invalid under jsdom.
- Reference drift between this pack and its sibling testing packs.

## Recommended Mitigation Strategies
- Keep tool detection mechanical from the dependency definition file; route setup through `$code-dependency-management`.
- State the runtime explicitly in the inventory and the report.
- When sibling testing packs change, re-check this pack's inherited rules in the same session.

## Version
- Current Version: 1.0.0
- Last Updated: 2026-11-19
- Stability: New
