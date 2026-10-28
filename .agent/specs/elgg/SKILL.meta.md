# Metadata: # Elgg Framework Conventions

## Purpose

This metadata file describes the elgg spec pack entry (`SKILL.md`). The
delta conventions, role, rules index, references index, and load
procedure live in `SKILL.md`; nothing here duplicates them.

## SDLC Phase

- Phase: Cross-cutting
- Sub-Phase: Conventions reference
- Workflow: Loaded on demand via the Conventions Reference Routing table
  in `.agent/AGENTS.md` when Project Framework Detection identifies Elgg

## Complexity Rating

- Complexity: Low
- Cognitive Load: Low
- Technical Depth: Elgg plugin architecture (events, hooks, views,
  routing, services) and upgrade-path discipline

## Usage Guidelines

- Prerequisite: Project Framework Detection has identified Elgg
- Requires:
  - The pack entry `.agent/specs/elgg/SKILL.md`
  - Task-matched rule files from `rules/`, loaded on demand
  - `references/upgrading.md` for any work crossing a version boundary
  - `references/site-upgrades.md` for site-level upgrade work

## Prompt Characteristics

- Input Driven: No (reference material, not a command workflow)
- State Dependent: No
- Requires Contextual Awareness: High (Elgg version and plugin set)
- Command Driven: No

## Gotchas / Sync Notes

- Rules are delta guidance only; never add general Elgg practices, API
  references, or tutorials to the pack.
- Version-specific guidance lives in `references/upgrade-notes/`; the
  pack has no per-version convention directories.
- `references/upgrade-notes/` drives `rules/` updates in the same change.
- Keep workflow instructions and indexes in `SKILL.md`; do not duplicate
  them here.
- `rules/security.md` and the upgrade-notes pitfall sections derive
  from the elgg-migrate tooling checklist (field-observed), not the
  manual; `rules/performance.md` and `rules/testing.md` distill the
  elgg-benchmark and elgg-test-writer skills from the upstream store
  (`tmp/upstream/elgg-llm-skills` — read-only, never edit it);
  `references/source-listing-7.1.2.json` remains the single provenance
  manifest for manual-sourced topics and the freshness check is
  unchanged.
- `references/site-upgrades.md` distills the elgg-site-upgrade skill
  (iron laws, verification gates, anonymized-seed footguns, cutover);
  keep it self-contained with no skill-tree paths. The upstream
  elgg-llm-skills repository is source material only: distill concrete
  knowledge from it into the pack; never edit or modify the skill repo
  itself.

## Version

- Current Version: 1.4.0
- Last Updated: 2026-10-28
- Stability: Stable

## Sync / Validation Checklist

Before considering this metadata file current, verify:

- [ ] Scope description matches `SKILL.md`
- [ ] Gotchas / Sync Notes reflect the current `SKILL.md` gotchas
- [ ] Version and Last Updated are incremented after `SKILL.md` changes
- [ ] No rules, indexes, or role text are duplicated here
