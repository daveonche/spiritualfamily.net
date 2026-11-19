# Component Test Templates

Exact output templates for `$testing-component-test`. Bracketed items inside
these templates are placeholders: resolve every one from the current context
before outputting — never emit brackets literally (Placeholder Convention in
`.agent/AGENTS.md`).

## Component tooling missing (inventory step)

```
No component testing tool detected. Before proceeding, we need to:
1. Select a component testing tool for the project's stack
2. Add required testing dependencies
3. Set up initial component test configuration

Would you like to:
A) Let me analyze your project and recommend a component testing stack  ← **Recommended default**
B) Specify your preferred component testing tool

Please choose A or B
```

Route the chosen setup through `$code-dependency-management`
(`.agent/workflows/code/dependency-management/SKILL.md`).

## Component inventory (inventory step)

```
Component Inventory for Story S<X.Y>:

| Component | Step | Render states | Props / events contract | External edges | Locator anchors |
| --- | --- | --- | --- | --- | --- |
| [name] | [step] | [loading/empty/error/success] | [props in, events out] | [network/browser API or "none"] | [role/label/text] |

Component tooling: [detected tool and runtime, or "Not installed"]
```

## Coverage audit (test plan step)

For each render state and interaction contract, classify before proposing
any new test:
- EXISTS — name the exact existing test file and test; propose no new test.
- PARTIAL — name the existing test and state precisely what remains
  uncovered; scope any new test to that gap only.
- NOT COVERED — permitted only after a repo-wide search over the test
  directory for the requirement's keywords (e.g.,
  `grep -rn "<keyword>" tests/`) with the empty result recorded as evidence.

```
Coverage Audit for Story S<X.Y>:

[component] [state/interaction]:
  Status: [EXISTS — file/test / PARTIAL — test + gap / NOT COVERED — empty search result]
```

## Test plan (test plan step)

```
Test Plan for Story S<X.Y>:

Component: [name]
Required Tests:
1. When [scenario], then [expectation]
   Maps to: [exact requirement/state text]
   Coverage: [NOT COVERED / PARTIAL gap]
   Fixtures: [real-response fixtures with provenance, or "none"]
   Factories: [build<Entity>() needs, or "none"]
   Error scenarios: [status → error mapping per external edge, or "none"]
   Assertions (max 3): [rendered-output assertions via user-facing locators]
```

## Component report skeleton (report step)

```
# S<X.Y> Component Test Report

- Date: [date]
- Tool: [tool and runtime]
- Components covered: [list with states]
- Test files created: [paths]
- Run results: [suite command, pass/fail counts, duration]
- Contract mismatches found and fixed: [list or "None"]
- Known gaps / quarantined flakes: [list or "None"]
```

## Status output (`#component-test-status`)

```
Component testing for S<X.Y>:
- Last completed: [step]
- Current: [step]
- Next action: [exact next command or action]
```
