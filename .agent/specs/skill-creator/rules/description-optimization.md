# Description Optimization

The `description` frontmatter field is the primary mechanism that
determines whether a model invokes a skill. After creating or
improving a skill, offer to optimize it for triggering accuracy.

## How triggering works

Skills appear in the model's available-skills list by name +
description; the model consults a skill based on that description,
and only for tasks it can't easily handle alone. Simple one-step
queries may not trigger a skill even with a perfect description.
Eval queries must therefore be substantive enough that consulting
the skill would genuinely help.

## Generate trigger eval queries

Create 20 queries — a mix of should-trigger and should-not-trigger —
saved as JSON:

```json
[
  {"query": "the user prompt", "should_trigger": true},
  {"query": "another prompt", "should_trigger": false}
]
```

Queries must be realistic — concrete, specific, detailed: file
paths, personal context, column names, company names, URLs, a little
backstory; some lowercase, abbreviated, typo-ridden, or casual;
mixed lengths; edge cases over clear-cut cases.

- Should-trigger (8-10): different phrasings of the same intent,
  formal and casual; cases that never name the skill or file type
  but clearly need it; uncommon use cases; cases competing with
  another skill where this one should win.
- Should-not-trigger (8-10): the near-misses are the valuable ones —
  adjacent domains, keyword overlap where a naive match would fire
  but shouldn't, ambiguous phrasing needing a different tool. Never
  obviously irrelevant prompts; those test nothing.

Bad: "Format this data". Good: a detailed realistic request with
file names, column positions, and backstory.

## Review with the user

Present the eval set for editing before running anything — bad eval
queries lead to bad descriptions. Use the upstream eval-review HTML
template (see the pack Source note): replace its placeholders with
the eval JSON, skill name, and current description; the user edits
queries, toggles should-trigger, adds/removes entries, and exports
`eval_set.json` (check the Downloads folder for the most recent
version).

## Run the optimization loop

Warn the user it takes time; run in the background and check
periodically, reporting iteration and scores. The upstream loop
script (see the pack Source note) takes the eval set, skill path,
model ID, and max iterations; use the model identifier of the
user's aider session so the trigger test matches what the user
actually experiences. It splits 60/40 train/test, evaluates the current
description (3 runs per query for a reliable trigger rate), proposes
improvements from failures, and re-evaluates each new description on
both sets, up to the iteration cap. It returns JSON with
`best_description`, selected by test score to avoid overfitting.

## Apply the result

Update the skill's frontmatter with `best_description`; show the
user before/after and report the scores.
