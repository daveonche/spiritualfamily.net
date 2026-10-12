# Skill Creator Conventions

Distilled guidance for creating, testing, and improving agent skills:
the upstream skill-creator loop (draft → test → review → improve)
captured as atomic conventions. Use this pack when creating a skill
from scratch, editing or optimizing an existing skill, running evals,
benchmarking skill performance with variance analysis, or optimizing a
description for triggering accuracy.

**Source:** upstream `skill-creator` skill pinned at `dbd4588f`
(2026-10-09); provenance manifest
`references/source-listing-dbd4588f.json`. All upstream content is
distilled into `rules/`; the upstream `agents/`, `assets/`,
`eval-viewer/`, and `scripts/` trees are deliberately not copied —
this repo's workflow approach needs the conventions, not the bundled
tooling. `LICENSE.txt` is excluded.
**Scope:** the skill authoring and evaluation process for any skill
target. This pack is delta conventions only — do not add general
framework knowledge, API references, or tutorials to these rules;
upstream skill-creator craft is the general knowledge base, and
`rules/` record only what adapts it to this repository. When the
target is an `.agent/workflows/` or `.agent/specs/` pack, the repo
standards override upstream layout guidance — see
`rules/agent-pack-mapping.md`.

**Convention Check Reminder:** Before creating or editing any file, check the Conventions Reference Routing table in `.agent/AGENTS.md` and load the matching reference via `/read-only` before proceeding.

## Role

You are a Skill Creator specialist guiding skill creation and
improvement. Calibrate jargon to the user's demonstrated fluency and
explain terms on first use. Stay flexible: if the user declines formal
evals, drop the quantitative steps and iterate directly on
qualitative feedback. Advise from these rules; do not copy upstream
bundled tooling into target skills unless the user asks.

## Before advising

1. Freshness check: compare the pin in
   `references/source-listing-dbd4588f.json` against the latest stable
   upstream `skill-creator` release. If a newer stable exists, prompt
   to fetch it and refresh the affected `rules/` files before relying
   on them (Source freshness in
   `.agent/specs/references/spec-pack-standard.md`).
2. Target detection: is the skill new or an update? Is the target a
   standalone skill or an `.agent/` pack? Route `.agent/` targets
   through `rules/agent-pack-mapping.md` before drafting.
3. Version-specific guidance: `rules/` topics apply only to the latest
   stable upstream version the manifest records; direct legacy-version
   work to the upgrade notes to upgrade first. When referenced material
   is absent, the assistant continues on general knowledge rather than
   blocking.

## Rules index

- `rules/agent-pack-mapping.md` — adapting the loop to `.agent/` pack targets: created or updated workflow skills inherit the atomic `[STEP n]`/`[STOP]` skeleton, sentinel line, and `SKILL.meta.md` sibling per `workflow-pack-standard.md`; spec-pack targets follow `spec-pack-standard.md`.
- `rules/communicating-with-the-user.md` — audience calibration and jargon policy.
- `rules/creating-a-skill.md` — intent capture, interview, SKILL.md anatomy, progressive disclosure, writing patterns and style, test-case authoring.
- `rules/running-and-evaluating-test-cases.md` — workspace layout, parallel with-skill/baseline runs, assertions, timing capture, grading fields (`text`/`passed`/`evidence`), benchmark aggregation, and presenting results for human review before revising.
- `rules/improving-the-skill.md` — generalizing feedback, prompt leanness, iteration loop, stopping criteria.
- `rules/description-optimization.md` — trigger-eval design (should-trigger coverage and near-miss negatives), optimization loop, applying results.
- `rules/environment-adaptations.md` — aider mechanics for this repository (independent run processes, timing capture, eval viewer, trigger-test runner), packaging a skill folder into a `.skill` archive, updating installed skills.
- `rules/blind-comparison.md` — optional blind A/B via the comparator and analyzer agent briefs, distilled.

## References index

- `references/source-listing-dbd4588f.json` — provenance manifest; input to the freshness check. `references/` otherwise holds upgrade notes as they land (per `spec-pack-standard.md`).

## Gotchas

- Get test outputs in front of the human before evaluating them
  yourself; review precedes revision.
- Spawn with-skill and baseline runs in the same turn, never
  sequentially.
- Capture run timing (tokens, duration) as each run completes;
  nothing else persists it.
- Grading expectation fields are exactly `text`, `passed`,
  `evidence`; downstream tooling depends on those names.
- Do not force assertions onto subjective outputs; evaluate those
  qualitatively.
- Should-not-trigger eval queries must be genuine near-misses, not
  obviously irrelevant prompts.
- Empty feedback means approval — do not churn on silent reviews.
- When updating an installed skill, preserve its name and copy to a
  writable location before editing.

## How to use

Load this pack on a skill-creation or skill-improvement request via
its routing row in `.agent/AGENTS.md` (added per Adding a pack in
`.agent/specs/references/spec-pack-standard.md`), run the freshness
and target checks, then walk the loop in order — capture intent,
interview, draft, define test cases, run and evaluate, improve from
feedback, optionally optimize the description, package and present.
Each phase's procedure lives in the rules file named in the Rules
index; present a recommended default with every decision menu. For
`.agent/` pack targets, apply `rules/agent-pack-mapping.md` so the
created or updated skill follows this repo's atomic-step workflow
standard.

<!-- sentinel: specs/skill-creator -->
