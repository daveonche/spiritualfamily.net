# Blind Comparison (Optional)

For a more rigorous comparison between two skill versions (e.g., "is
the new version actually better?") than human review provides: give
two outputs to an independent agent without telling it which skill
produced which, let it judge quality, then analyze why the winner
won. Requires independent agent runs (separate aider processes);
most users don't need it — the human review loop is usually
sufficient.

## Comparator pass (blind)

Run a comparator agent in a separate aider process with the upstream
`agents/comparator.md` brief (pinned at dbd4588f), giving it: output
A path, output B path, the eval prompt, and the expectations list
(optional). The brief instructs it to:

- Judge purely on output quality — never try to infer which skill
  produced which output.
- Build a rubric from the task: content criteria (correctness,
  completeness, accuracy) and structure criteria (organization,
  formatting, usability), each scored 1-5; dimension averages combine
  into an overall score scaled 1-10.
- Check provided expectations against both outputs as secondary
  evidence (pass rates, per-expectation details).
- Pick a winner by priority: overall rubric score first, expectation
  pass rates second, TIE only if genuinely equal — be decisive; if
  both fail, pick the one that fails less badly.
- Write `comparison.json`: winner, reasoning, full rubric scores,
  per-output strengths/weaknesses, and expectation results (omit
  `expectation_results` entirely when no expectations were given).

## Analyzer pass (unblind)

Run an analyzer agent in a separate aider process with the upstream
`agents/analyzer.md` brief, giving it: the winning side (A/B), both
skill paths and transcript paths, the comparison result, and an
output path. The brief instructs it to:

- Read both skills and both transcripts; compare instruction
  following (score 1-10 with specific issues), tool usage, divergence
  from optimal behavior, and error recovery attempts.
- Extract winner strengths and loser weaknesses, quoting skills and
  transcripts — specific evidence, not "instructions were unclear".
- Generate improvement suggestions prioritized by outcome impact
  (high/medium/low) in categories: instructions, tools, examples,
  error_handling, structure, references.
- Extract and verify claims from the outputs (factual, process,
  quality), flagging unverifiable ones; consider whether the skill
  weakness actually caused the worse output or is incidental.
- Write the structured analysis JSON to the output path.

## Benchmark-notes mode

The same analyzer brief has a second mode for benchmark results: it
reviews all run results and writes freeform notes (a JSON array of
strings) surfacing patterns the aggregate metrics hide — assertions
that always pass or fail in both configurations, with-skill-only
flips, high-variance evals, time/token costs. It reports
observations only: no skill-improvement suggestions and no
subjective quality judgments.
