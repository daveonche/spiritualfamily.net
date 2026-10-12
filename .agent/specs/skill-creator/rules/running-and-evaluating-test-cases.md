# Running and Evaluating Test Cases

One continuous sequence: spawn runs, draft assertions while they
execute, capture timing, grade, aggregate, present the viewer.

## Workspace layout

Results live in `<skill-name>-workspace/` as a sibling of the skill
directory, organized by iteration (`iteration-1/`, `iteration-2/`,
...) and per test case (`eval-0/`, `eval-1/`, ...). Create directories
as you go, not upfront. Give each eval a descriptive name reflecting
what it tests — not just `eval-0` — and use it for the directory too.

## Spawn all runs in the same turn

For each test case, spawn two runs at once — with-skill AND baseline.
Launching everything together means they finish around the same time;
never spawn with-skill first and come back for baselines later.

- With-skill run prompt: skill path, task, input files, save-outputs
  directory (`<workspace>/iteration-<N>/eval-<ID>/with_skill/outputs/`),
  and which outputs to save.
- Baseline: for a new skill, no skill at all (`without_skill/`);
  for an improved skill, the old version — snapshot it first
  (`cp -r <skill-path> <workspace>/skill-snapshot/`) and point the
  baseline at the snapshot (`old_skill/`).

Write `eval_metadata.json` per test case (assertions may start
empty):

```json
{
  "eval_id": 0,
  "eval_name": "descriptive-name-here",
  "prompt": "The user's task prompt",
  "assertions": []
}
```

## Draft assertions while runs execute

Don't idle waiting for runs. Draft quantitative assertions per test
case, explain them to the user, and update `eval_metadata.json` and
`evals/evals.json`. Good assertions are objectively verifiable with
descriptive names that read clearly at a glance. Subjective outputs
(writing style, design) are better evaluated qualitatively — don't
force assertions onto them.

## Capture timing as each run completes

Record each run's `total_tokens` and wall-clock duration to
`timing.json` in the run directory as the run finishes — wrap each
run in a timing wrapper or read the runner's report; nothing else
persists this data. Capture it as each run completes rather than
batching at the end.

```json
{
  "total_tokens": 84852,
  "duration_ms": 23332,
  "total_duration_seconds": 23.3
}
```

## Grade, aggregate, analyze

1. Grade each run against its assertions; save `grading.json` in the
   run directory. The expectations array uses exactly the fields
   `text`, `passed`, `evidence` — downstream tooling depends on these
   names. Check assertions programmatically with a script where
   possible; scripts are faster and reusable.
2. Aggregate into a benchmark with the upstream aggregation script
   (see the pack Source note): it produces `benchmark.json` and
   `benchmark.md` with pass_rate, time, and tokens per configuration
   (mean ± stddev, delta), each with_skill entry before its baseline
   counterpart.
3. Analyst pass: read the benchmark for patterns aggregates hide —
   assertions that always pass regardless of skill
   (non-discriminating), high-variance evals (possibly flaky),
   time/token tradeoffs.

## Present for human review

Generate the eval viewer before reviewing outputs yourself — get
examples in front of the human first. Use the upstream viewer
generator, never bespoke HTML: pass the workspace iteration
directory, `--skill-name`, `--benchmark <...>/benchmark.json`, and
for iteration 2+ `--previous-workspace <previous iteration>`.
Without a browser or display, use `--static <output_path>` for a
standalone HTML file; feedback downloads as `feedback.json`, which
the next iteration picks up from the workspace.

Tell the user what they'll see: an Outputs tab (prompt, output,
previous output/feedback, grades, auto-saving feedback box) and a
Benchmark tab (pass rates, timing, tokens, analyst observations).
When they're done, read `feedback.json` — empty feedback means
approval; focus improvements on specific complaints. Kill the viewer
server when finished with it.
