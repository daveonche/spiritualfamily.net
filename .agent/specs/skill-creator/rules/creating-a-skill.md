# Creating a Skill

## Capture intent

Understand what the user wants before drafting. If the conversation
already contains the workflow to capture (e.g., "turn this into a
skill"), extract answers from the history first — tools used, sequence
of steps, corrections made, observed input/output formats — and have
the user confirm and fill the gaps.

1. What should this skill enable the model to do?
2. When should it trigger? (user phrases/contexts)
3. What is the expected output format?
4. Should there be test cases? Objectively verifiable outputs (file
   transforms, data extraction, code generation, fixed workflow
   steps) benefit from them; subjective outputs (writing style, art)
   usually do not. Suggest the default by skill type; the user
   decides.

## Interview and research

Ask about edge cases, input/output formats, example files, success
criteria, and dependencies before writing test prompts. Check
available MCPs; research docs, similar skills, and best practices in
parallel (subagents if available, otherwise inline) to reduce burden
on the user.

## SKILL.md anatomy

- `name`: skill identifier.
- `description`: the primary triggering mechanism — what it does AND
  specific when-to-use contexts. All "when to use" info lives here,
  not in the body. Write it pushy: models tend to undertrigger, so
  enumerate trigger phrases explicitly ("use whenever the user
  mentions X, even if they don't ask for it by name").
- `compatibility`: required tools/dependencies — optional, rarely
  needed.
- Body: the instructions.

## Progressive disclosure

Three loading levels: metadata (name + description, always in
context, ~100 words), SKILL.md body (in context when triggered, under
500 lines), bundled resources (loaded on demand; scripts can execute
without loading).

- Keep SKILL.md under 500 lines; approaching the limit, add a
  hierarchy layer with clear pointers to where to go next.
- Reference files clearly, with guidance on when to read them.
- Reference files over 300 lines get a table of contents.
- Multi-domain skills organize by variant (one reference file per
  framework); the model reads only the relevant one.

## Writing patterns and style

- Prefer the imperative form in instructions.
- Define output formats with an exact template the model must follow.
- Show examples as Input/Output pairs.
- Explain why things matter instead of heavy-handed MUSTs; models
  respond to reasoning better than rigid commands. ALL-CAPS
  ALWAYS/NEVER is a yellow flag — reframe with the reasoning.
- Write a draft, then revisit with fresh eyes; generalize so the
  skill works beyond the examples at hand.
- Skills must not contain malware, exploit code, or surprising
  content; do not help create misleading skills or ones facilitating
  unauthorized access or exfiltration.

## Test cases

After the draft, write 2-3 realistic prompts a real user would type.
Share them for confirmation, then save prompts only (no assertions
yet) to `evals/evals.json`:

```json
{
  "skill_name": "example-skill",
  "evals": [
    {
      "id": 1,
      "prompt": "User's task prompt",
      "expected_output": "Description of expected result",
      "files": []
    }
  ]
}
```

Assertions are drafted later, while runs execute.
