# Spec Pack Standard

Canonical layout and conventions for `.agent/specs/`: how framework and
technology conventions are organized so agents can discover and load
them mechanically. Load this reference before creating or editing
anything under `.agent/specs/` (see the Conventions Reference Routing
table in `.agent/AGENTS.md`).

Mechanical enforcement lives in `tests/test_s4_2_step1.py`: it
discovers packs structurally and applies the invariants below without
naming any framework. Keep the test and this document in sync.

## Directory layout

```text
.agent/specs/
├── <pack>/                     # one directory per framework/technology
│   ├── SKILL.md                # distilled entry point
│   ├── SKILL.meta.md           # metadata companion
│   ├── rules/                  # atomic conventions, one topic per file
│   └── references/             # narrative/deep supporting material
├── <NAME>.md                   # legacy flat framework file (migrate)
└── references/                 # cross-cutting file-type references
```

- Pack directories are lowercase-hyphenated (`elgg/`,
  `php-best-practices/`).
- A pack is any `.agent/specs/` subdirectory containing `SKILL.md`.
- `references/` at the top level is reserved for cross-cutting,
  file-type-matched references (routing-table targets); it is not a
  pack.
- A flat `<NAME>.md` participates in the standard when it declares
  itself delta conventions/guidance in its opening; migrate it to a
  pack (see below).

## SKILL.md skeleton

Fixed section order:

1. H1: `<Pack> Conventions`
2. Prose description, then `**Source:**` and `**Scope:**` lines
3. Convention Check Reminder line
4. `## Role` — persona and priorities
5. Detection / before-advising steps
6. Rules index — every rule file, one line each
7. References index
8. Language pack link (framework packs link their language pack)
9. Gotchas
10. How to use
11. Final line: `<!-- sentinel: specs/<pack> -->`

## SKILL.meta.md

Every `SKILL.md` has a sibling `SKILL.meta.md`, following the same
convention as `.agent/workflows/**/SKILL.meta.md`: purpose, SDLC phase,
complexity, usage guidelines, gotchas/sync notes, version, and a
validation checklist. Never duplicate `SKILL.md` content in the meta
file; increment Version and Last Updated after `SKILL.md` changes.

## rules/ and references/

- `rules/` holds atomic conventions, one topic per file; filenames
  mirror their source pages so upstream diffs map mechanically onto
  updates.
- `references/` holds narrative material: upgrade notes, version
  matrices, provenance manifests.
- Version-specific guidance lives in the pack's upgrade notes or
  version directories — never in per-version copies of the entry file.
- Upgrade notes drive `rules/` updates: when a note changes, update the
  affected rule files in the same change.
- Empty `rules/` or `references/` directories are kept in git with a
  `.gitkeep` file; remove it when the first content file lands.

## Source freshness

A pack distilled from upstream documentation must keep a
machine-readable provenance manifest under its `references/` (e.g.,
`source-listing-<version>.json`) naming the upstream version the
distillation covers. Its `SKILL.md` before-advising steps must include
a freshness check: before using any `rules/` topic, compare the
manifest's version against the latest stable upstream release; if a
newer stable exists, prompt to fetch that release's source listing and
refresh the affected `references/upgrade-notes/` and `rules/` files
before relying on them. `rules/` topics apply only to the latest
stable version the manifest records; the pack must direct
legacy-version work to its upgrade notes to upgrade to the latest
stable before any `rules/` topic is used.

## Adding a pack

1. Create `<pack>/` with `SKILL.md`, `SKILL.meta.md`, `rules/`, and
   `references/` per this standard.
2. Add a routing row in `.agent/AGENTS.md` naming the pack's
   `SKILL.md`.
3. Framework packs link their language pack (for example, Elgg links
   `php-best-practices/SKILL.md`).
4. If the pack distills upstream documentation, add the provenance
   manifest and freshness check per Source freshness.
5. Run `python3 -m pytest tests/test_s4_2_step1.py`.

## Migrating a flat file

Follow the elgg migration pattern: `git mv` the content into the pack,
distill the flat file into `SKILL.md`, fix stale internal paths, delete
the flat file, and update the routing row.
