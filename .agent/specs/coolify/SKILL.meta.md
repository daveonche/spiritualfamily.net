# Metadata: Coolify Conventions

## Purpose

This metadata file describes the coolify spec pack entry (`SKILL.md`). The
delta conventions, role, detection steps, indexes, and load procedure live
in `SKILL.md`; nothing here duplicates them.

## SDLC Phase

- Phase: Cross-cutting
- Sub-Phase: Conventions reference
- Workflow: Loaded on demand via the Conventions Reference Routing table
  in `.agent/AGENTS.md` when a task touches Coolify administration —
  instance management, applications, services, networking, notifications,
  security (authentication, credentials), MCP, or Cloudflare Tunnel
  integrations

## Complexity Rating

- Complexity: Medium
- Cognitive Load: Medium
- Technical Depth: Coolify dashboard operations, Docker/Compose
  administration, cron scheduling, webhook and API-token security, and
  internal PostgreSQL maintenance

## Usage Guidelines

- Prerequisite: none (task-matched; not gated on Project Framework
  Detection)
- Requires:
  - The pack entry `.agent/specs/coolify/SKILL.md`
  - Task-matched rule files from `rules/`, loaded on demand
  - `references/` for the provenance manifest and webhook payload
    reference, loaded on demand

## Prompt Characteristics

- Input Driven: No (reference material, not a command workflow)
- State Dependent: No
- Requires Contextual Awareness: High (deployment context recorded in the
  `SKILL.md` Scope; several rules cover destructive operations that need
  explicit confirmation)
- Command Driven: No

## Gotchas / Sync Notes

- Rules are delta guidance only; never add general Coolify practices, API
  references, or tutorials to the pack.
- The upstream `databases/` section was not distilled; database-resource
  tasks are out of scope for this pack.
- The Dockerfile build rule is named `build-from-dockerfile.md` because
  editor tooling associates `Dockerfile*` filenames with Dockerfile
  syntax; keep that filename when updating the pack.
- Keep workflow instructions and indexes in `SKILL.md`; do not duplicate
  them here.

## Version

- Current Version: 1.0.0
- Last Updated: 2026-10-18
- Stability: Stable

## Sync / Validation Checklist

Before considering this metadata file current, verify:

- [ ] Scope description matches `SKILL.md`
- [ ] Gotchas / Sync Notes reflect the current `SKILL.md` gotchas
- [ ] Version and Last Updated are incremented after `SKILL.md` changes
- [ ] No rules, indexes, or role text are duplicated here
