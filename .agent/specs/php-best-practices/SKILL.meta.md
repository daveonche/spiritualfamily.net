# Metadata: PHP Best Practices Conventions

## Purpose

This metadata file describes the php-best-practices spec pack entry
(`SKILL.md`). The delta conventions, role, rules index, references
index, and load procedure live in `SKILL.md`; nothing here duplicates
them.

## SDLC Phase

- Phase: Cross-cutting
- Sub-Phase: Conventions reference
- Workflow: Loaded on demand via the Conventions Reference Routing table
  in `.agent/AGENTS.md` when a task touches PHP code or a framework pack
  links its Language pack section here

## Complexity Rating

- Complexity: Low
- Cognitive Load: Low
- Technical Depth: PHP 8.x type system, modern features, PSR standards,
  SOLID principles, error handling, performance, and security rules with
  per-rule version annotations

## Usage Guidelines

- Prerequisite: a task touching PHP code, or a framework pack's Language
  pack link
- Requires:
  - The pack entry `.agent/specs/php-best-practices/SKILL.md`
  - Task-matched rule files from `rules/`, loaded on demand
  - `references/source-listing-2.1.0.json` for provenance and the
    freshness check

## Prompt Characteristics

- Input Driven: No (reference material, not a command workflow)
- State Dependent: Yes (project PHP version determines applicable rules)
- Requires Contextual Awareness: High (PHP version and composer
  constraints)
- Command Driven: No

## Gotchas / Sync Notes

- Rules are delta guidance only; never add general PHP practices, API
  references, or tutorials to the pack.
- Version-specific guidance lives in per-rule PHP version annotations,
  not per-version directories.
- Upstream skill releases drive `rules/` updates in the same change.
- Keep workflow instructions and indexes in `SKILL.md`; do not duplicate
  them here.

## Version

- Current Version: 1.0.0
- Last Updated: 2026-10-09
- Stability: Stable

## Sync / Validation Checklist

Before considering this metadata file current, verify:

- [ ] Scope description matches `SKILL.md`
- [ ] Gotchas / Sync Notes reflect the current `SKILL.md` gotchas
- [ ] Version and Last Updated are incremented after `SKILL.md` changes
- [ ] No rules, indexes, or role text are duplicated here
