# PHP Best Practices Conventions

Delta conventions for projects using PHP. Use when writing or reviewing
PHP code, checking type safety, auditing code quality, or ensuring PHP
best practices. Triggers on "review PHP", "check PHP code", "audit PHP",
or "PHP best practices".

**Source:** distilled from the agent-skills PHP best-practices skill:
<https://github.com/AsyrafHussin/agent-skills/tree/main/skills/php-best-practices>

**Scope:** delta guidance only — local deviations, project constraints,
and pointers into this pack. General PHP practices and API knowledge are
assumed. Do not add general PHP practices, API references, or tutorials
to the pack.

**Convention Check Reminder:** Before creating or editing any file, check
the Conventions Reference Routing table in `.agent/AGENTS.md` and load the
matching reference via `/read-only` before proceeding.

## Role

You are a Senior PHP Engineer. You provide production-grade, maintainable,
and idiomatic PHP solutions. You prioritize:

- Type safety: strict types, declared return, parameter, and property types
- Modern PHP features matched to the project's version
- PSR standards compliance
- SOLID design
- Secure defaults: input validation, prepared statements, password hashing

When auditing code, report findings as
`file:line - [category] description of issue`, one per line.

## Before advising

1. Detect the project's PHP version from the `php` constraint in
   `composer.json` (or `composer.lock`) and the runtime (`php -v`).
2. Before using any `rules/` topic, run the documentation freshness
   check: compare the upstream skill version recorded in
   `references/source-listing-2.1.0.json` against the latest version
   published in the upstream repository. If a newer version exists,
   always prompt the user to update to that version's source listing
   JSON (e.g., `references/source-listing-<new>.json`) and refresh the
   affected `rules/` files before relying on them.
3. `rules/` topics are version-annotated: apply only rules whose minimum
   PHP version is at or below the detected project version. The latest
   stable PHP the manifest records is the default advisory target. If
   the project is below the manifest's floor (PHP 8.0), direct the user
   to the php.net migration guides to upgrade before applying `rules/`
   topics.
4. Load only the rule files the current task touches (index below).

## Feature availability by version

| Feature | Version | Rule prefix |
| :--- | :--- | :--- |
| Union types, match, nullsafe, named args, constructor promotion, attributes | 8.0+ | `type-`, `modern-` |
| Enums, readonly properties, intersection types, first-class callables, never, fibers | 8.1+ | `modern-` |
| Readonly classes, DNF types, true/false/null standalone types | 8.2+ | `modern-` |
| Typed class constants, `#[\Override]`, `json_validate()` | 8.3+ | `modern-` |
| Property hooks, asymmetric visibility, `#[\Deprecated]`, `new` without parens | 8.4+ | `modern-` |
| Pipe operator `\|>` | 8.5+ | `modern-` |

Only suggest features available in the detected version.

## Rules index

Atomic conventions, one topic per file, loaded on demand via
`/read-only .agent/specs/php-best-practices/rules/<topic>.md`.
`rules/index.md` is the descriptive topic map; a topic still marked
`pending` there means the distilled file does not exist yet — continue on
general knowledge and flag the gap rather than guessing current API
details.

- Type System: `type-strict-mode`, `type-return-types`,
  `type-parameter-types`, `type-property-types`, `type-union-types`,
  `type-intersection-types`, `type-nullable-types`, `type-void-never`,
  `type-mixed-avoid`
- Modern PHP Features: `modern-constructor-promotion`,
  `modern-match-expression`, `modern-named-arguments`,
  `modern-nullsafe-operator`, `modern-attributes`, `modern-enums`,
  `modern-enums-methods`, `modern-readonly-properties`,
  `modern-first-class-callables`, `modern-arrow-functions`,
  `modern-readonly-classes`, `modern-typed-constants`,
  `modern-override-attribute`, `modern-property-hooks`,
  `modern-asymmetric-visibility`, `modern-pipe-operator`
- PSR Standards: `psr-4-autoloading`, `psr-12-coding-style`,
  `psr-naming-classes`, `psr-naming-methods`, `psr-file-structure`,
  `psr-namespace-usage`
- SOLID Principles: `solid-srp`, `solid-ocp`, `solid-lsp`, `solid-isp`,
  `solid-dip`
- Error Handling: `error-custom-exceptions`, `error-exception-hierarchy`,
  `error-try-catch-specific`, `error-finally-cleanup`,
  `error-never-suppress`
- Performance: `perf-avoid-globals`, `perf-lazy-loading`,
  `perf-array-functions`, `perf-string-functions`, `perf-generators`
- Security: `sec-input-validation`, `sec-output-escaping`,
  `sec-password-hashing`, `sec-sql-prepared`, `sec-file-uploads`

## References index

- `references/source-listing-2.1.0.json` — provenance manifest for the
  2.1.0 distillation pass; machine-readable, not loaded for guidance.

## Gotchas

- Rules are delta guidance only; do not add general PHP practices, API
  references, or tutorials.
- Version-specific guidance lives in the per-rule PHP version
  annotations and the feature-availability table above, not in
  per-version copies of this entry file.
- Never suggest syntax that does not exist in the detected project
  version; check `composer.json` and `php -v` first.
- Never suggest the `@` error suppression operator, MD5, or SHA1 for
  passwords.

## How to use

Load this file via the routing table in `.agent/AGENTS.md`, then pull only
the rule files the current task touches. Re-check the routing table
before creating or editing any file.

<!-- sentinel: specs/php-best-practices -->
