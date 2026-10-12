# Skill Creator Conventions Metadata

## Purpose

Metadata companion for the skill-creator spec pack: provenance,
applicability, and maintenance notes. It never duplicates SKILL.md
content; the entry file and `rules/` carry the conventions themselves.

## AI Assistant Compatibility

- Tested With:
  - Aider
  - LLM: GLM 5.3 Flash (August 27, 2026 release)
- Last Validated Model: Claude 3.5 Sonnet
- Potential Compatible Assistants:
  - Other Claude models
  - GitHub Copilot (with modifications)

## SDLC Phase

- Phase: Development
- Sub-Phase: Skill authoring and evaluation
- Workflow: Advisory conventions (not a staged workflow)

## Complexity Rating

- Complexity: High
- Cognitive Load: High
- Technical Depth: Requires eval design, benchmarking, and upstream tooling awareness

## Usage Guidelines

- Prerequisite: a skill draft or an existing skill to improve
- Requires:
  - Target skill directory (new or existing)
  - Test prompts (drafted during the loop)
  - Upstream tooling for benchmarking/packaging (pinned at dbd4588f)
- Advisory pack: walk the loop phases via the Rules index; no [STOP] gates

## Prompt Characteristics

- Input Driven: Yes
- State Dependent: Yes
- Requires Contextual Awareness: Critical
- Command Driven: No (loaded via routing row on skill-creation tasks)

## Best Practices

- Calibrate jargon to the user's fluency
- Map evals directly to skill intent
- Present results for human review before revising
- Generalize feedback; avoid overfitting to test cases
- Explain the why instead of rigid MUSTs

## Potential Challenges

- Subjective outputs resist quantitative assertions
- Trigger evals mis-scoped (too-easy negatives, thin positives)
- Runner differences (separate processes vs in-session) change the mechanics
- Reference drift from upstream skill-creator evolution

## Recommended Mitigation Strategies

- Qualitative review for subjective skills
- Near-miss negative queries; realistic, detailed positive queries
- Follow rules/environment-adaptations.md per environment
- Freshness check against the provenance manifest before relying on rules

## Gotchas / Sync Notes

- `rules/` filenames mirror upstream page structure so upstream diffs map mechanically onto updates
- Refresh `rules/` against `references/source-listing-dbd4588f.json` whenever the upstream pin moves
- Grading field names (`text`, `passed`, `evidence`) are load-bearing downstream; do not rename them in rules prose

## Version

- Current Version: 1.0.2
- Last Updated: 2026-10-11
- Stability: Experimental

## Validation Checklist

- [ ] `python3 -m pytest tests/test_s4_2_step1.py` passes
- [ ] Provenance manifest pin matches the latest fetched upstream release
- [ ] Routing row for this pack present in `.agent/AGENTS.md`
