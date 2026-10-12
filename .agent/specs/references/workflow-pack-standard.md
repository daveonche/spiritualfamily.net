# Workflow Pack Standard

Canonical layout and conventions for `.agent/workflows/`: how staged
prompt workflows are organized so the orchestrator can discover, load,
and drop them mechanically. Load this reference before creating or
editing anything under `.agent/workflows/` (see the Conventions
Reference Routing table in `.agent/AGENTS.md`).

Mechanical enforcement lives in `tests/test_s1_7_step1.py` through
`tests/test_s1_7_step3.py`: they verify the prompt directory structure,
that every category's `SKILL.md` files define prompt instructions, and
that shorthand commands map to `SKILL.md` files. Keep the tests and
this document in sync.

## Directory layout

```text
.agent/workflows/
├── <category>/                 # one directory per category (core, code, testing, ...)
│   └── <promptname>/           # one workflow pack per shorthand command
│       ├── SKILL.md            # staged prompt entry point
│       ├── SKILL.meta.md       # metadata companion
│       └── references/         # on-demand supporting material
│           ├── gotchas.md      # load before starting
│           ├── templates.md    # exact output and configuration templates
│           └── rules.md        # consolidated rules
└── phases/
    └── <chain-name>/
        ├── SKILL.md            # chain entry point ($workflows-<name>-chain)
        └── SKILL.meta.md       # metadata companion
```

- Category directories are lowercase (`core/`, `code/`, `testing/`).
- A workflow pack is any `.agent/workflows/<category>/<promptname>/`
  directory containing `SKILL.md`; its shorthand command is
  `$<category>-<promptname>`, derived mechanically from the path.
- `phases/` holds chain entry points activated by the
  `$workflows-<name>-chain` commands; a chain orchestrates packs across
  categories and is not itself a staged workflow.

## SKILL.md skeleton

Fixed section order. The reference example is
`.agent/workflows/testing/unit-test/SKILL.md`.

1. H1: the workflow role title (e.g., `Unit Test Generation Prompt`).
2. Commands the role responds to: the activating
   `$<category>-<promptname>` plus any status command.
3. Convention Check Reminder line pointing at the routing table in
   `.agent/AGENTS.md` (verified on load per routing rule 3).
4. Activation trigger (`When you see $<category>-<promptname>, activate
   this role:`) followed by the role persona paragraph.
5. Quick Start: mode verification with an exact-output string and a
   `[STOP]` gate.
6. Gotchas pointer: load `references/gotchas.md` before starting.
7. Workflow Overview: `### [STEP n]` sections, each ending in a
   `[STOP]` gate wherever user input is required.
8. Status command section (if the pack defines one).
9. Decision Defaults: one recommended default per menu.
10. Consolidated Rules pointer (`references/rules.md`).
11. Final line: `<!-- sentinel: <category>/<promptname> -->` — the
    orchestrator quotes it before staged execution to verify the file
    loaded completely.

### Staged execution conventions

- Every step needing a value, file, or decision ends in `[STOP]`;
  never proceed on assumed input (Stop on Missing Input in
  `.agent/AGENTS.md`).
- Exact-output strings (mode verification, early exits) are quoted
  verbatim; bracketed items inside them are placeholders resolved from
  context per the Placeholder Convention.
- Mode changes (`/ask`, `/code`) are explicit user instructions with
  confirmation before proceeding.
- Context checks ask the user what is loaded; never assess context
  contents from transcript recall (Critical Rule 3).

## SKILL.meta.md

Every `SKILL.md` has a sibling `SKILL.meta.md` with fixed sections (see
`testing/unit-test/SKILL.meta.md`): AI Assistant Compatibility, SDLC
Phase, Complexity Rating, Usage Guidelines, Prompt Characteristics, Best
Practices, Potential Challenges, Recommended Mitigation Strategies, and
Version (`Current Version`, `Last Updated`, `Stability`). Never
duplicate `SKILL.md` content in the meta file; increment Version and
Last Updated after `SKILL.md` changes.

## references/

- On-demand material loaded at the point of use: `gotchas.md` (before
  starting), `templates.md` (exact output and configuration templates),
  `rules.md` (consolidated rules).
- The core `SKILL.md` stays lean (progressive disclosure); it names
  each reference and when to load it.
- Keep references in sync with the core workflow; reference drift is a
  listed potential challenge in the meta file.

## Source freshness

For packs distilled from upstream documentation, follow the Source
freshness rules in `spec-pack-standard.md`: provenance manifest under
`references/`, freshness check in the before-advising steps, and
legacy-version work routed through upgrade notes.

## Adding a workflow pack

1. Create `.agent/workflows/<category>/<promptname>/` with `SKILL.md`,
   `SKILL.meta.md`, and `references/` per this standard.
2. Define the commands the pack responds to in its `SKILL.md` opening;
   no routing-table row is needed — the orchestrator maps
   `$<category>-<promptname>` mechanically from the path.
3. Run `python3 -m pytest tests/test_s1_7_step1.py
   tests/test_s1_7_step2.py tests/test_s1_7_step3.py`.
