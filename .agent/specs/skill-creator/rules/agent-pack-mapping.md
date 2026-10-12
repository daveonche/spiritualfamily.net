# Agent Pack Mapping

How to map the skill-creator loop onto `.agent/` pack targets. Repo
standards override upstream layout guidance: a skill created for this
repository must be orchestrator-discoverable and mechanically
enforced by the repo's tests.

## Workflow pack targets

Target: `.agent/workflows/<category>/<promptname>/`. Created or
updated workflow skills inherit the staged skeleton in
`workflow-pack-standard.md` — the same atomic-step convention
`.agent/workflows/testing/unit-test/SKILL.md` uses:

- Map each loop phase to a `### [STEP n]` section; end every step
  needing a value, file, or decision with a `[STOP]` gate — never
  proceed on assumed input.
- Follow the fixed section order: H1 role title, commands the role
  responds to, Convention Check Reminder, activation trigger and
  persona, Quick Start mode gate, gotchas pointer, workflow steps,
  status command (if any), Decision Defaults, consolidated rules
  pointer, sentinel.
- Final line: `<!-- sentinel: <category>/<promptname> -->`.
- Sibling `SKILL.meta.md` with the fixed meta sections; never
  duplicate `SKILL.md` content; bump Version and Last Updated on
  every `SKILL.md` change.
- `references/` holds `gotchas.md`, `templates.md`, `rules.md`; keep
  the core `SKILL.md` lean with load-on-demand pointers.
- The shorthand `$<category>-<promptname>` derives mechanically from
  the path; no routing-table row is needed.
- Validate: `python3 -m pytest tests/test_s1_7_step1.py
  tests/test_s1_7_step2.py tests/test_s1_7_step3.py`.

## Spec pack targets

Target: `.agent/specs/<pack>/`. Spec packs are advisory references,
not staged workflows — no `[STEP n]`/`[STOP]` sections. Follow
`spec-pack-standard.md`:

- Skeleton: H1 `<Pack> Conventions`, prose with `**Source:**` and
  `**Scope:**`, Convention Check Reminder, `## Role`, before-advising
  steps, Rules index, References index, Gotchas, How to use, final
  sentinel `<!-- sentinel: specs/<pack> -->`.
- `rules/` holds atomic conventions, one topic per file;
  `references/` holds narrative material and the provenance manifest.
- Upstream-distilled packs need the provenance manifest and freshness
  check (Source freshness in `spec-pack-standard.md`).
- Add a routing row in `.agent/AGENTS.md` naming the pack's
  `SKILL.md`.
- Validate: `python3 -m pytest tests/test_s4_2_step1.py`.

## Loop-to-steps mapping

Distilling the loop into a workflow pack: capture intent, interview,
draft, test cases, run and evaluate, improve, optional description
optimization, package — one `[STEP n]` per phase, a `[STOP]` gate
with a recommended default wherever the user must decide.
