# Environment Adaptations

The core loop (draft → test → review → improve → repeat) is the same
everywhere; the mechanics here are aider's, in this repository.

## Aider (this repository)

- No subagents: run each test case as a separate aider process
  (`aider --message "<eval prompt>"`) so with-skill and baseline runs
  stay independent; launch an iteration's runs together so they
  finish around the same time. In-session, one-at-a-time runs are the
  fallback when separate processes are impractical — less rigorous
  (you wrote the skill and you're running it), but a useful sanity
  check; the human review step compensates.
- Timing: nothing reports token counts or durations automatically —
  wrap each run (or read the runner's report) and record
  `total_tokens` and wall-clock duration to `timing.json` as each run
  completes.
- Eval viewer: run the upstream `generate_review.py` and open the
  HTML it writes; use `--static <output_path>` when no display is
  available. Feedback downloads as `feedback.json` — read it from the
  download for the next iteration.
- Description optimization: the upstream loop script expects its
  bundled CLI runner; drive the trigger test with the aider CLI
  instead (`aider --model <model> --message "<query>"`), or skip the
  step if no equivalent runner is available. Run it only after the
  skill is finished and the user agrees it's in good shape.
- Blind comparison: run the comparator and analyzer as separate aider
  processes (see `rules/blind-comparison.md`).
- Packaging works anywhere with Python and a filesystem.

## Updating an installed skill

- Preserve the original name: keep the directory name and `name`
  frontmatter unchanged, and package under the same name (not
  `-v2`).
- The installed path may be read-only: copy to a writable location
  (e.g., `/tmp/<skill-name>/`) before editing.
- When packaging manually, stage in `/tmp/` first, then copy to the
  output directory — direct writes may fail on permissions.

## Packaging

Package a skill folder into a `.skill` archive with the upstream
packaging script (see the pack Source note), then direct the user to
the resulting file path.
