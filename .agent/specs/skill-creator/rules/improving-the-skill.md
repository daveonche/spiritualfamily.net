# Improving the Skill

The heart of the loop: runs are done, the user reviewed, now make the
skill better.

## How to think about improvements

1. Generalize from feedback. Test examples are for fast iteration;
   the skill must work across many prompts. Prefer branching out —
   different metaphors, different working patterns — over fiddly
   overfitty changes or oppressive constrictions.
2. Keep the prompt lean. Remove what isn't pulling its weight. Read
   the transcripts, not just final outputs: if the skill makes the
   model waste time on unproductive work, cut the parts causing it.
3. Explain the why. Models are smart and respond to reasoning; even
   terse or frustrated feedback deserves a real understanding of what
   the user needs, transmitted into the instructions. ALL-CAPS
   ALWAYS/NEVER and super-rigid structures are yellow flags —
   reframe with reasoning.
4. Look for repeated work across test cases. If every run
   independently wrote a similar helper script or took the same
   multi-step approach, bundle it: write the script once, put it in
   `scripts/`, and tell the skill to use it.

Take time to mull: draft a revision, then look at it anew and
improve it. Get into the user's head.

## The iteration loop

1. Apply improvements to the skill.
2. Rerun all test cases into `iteration-<N+1>/`, including baselines.
   New skill: baseline is always `without_skill`. Improved skill:
   baseline is the original version or the previous iteration — your
   judgment.
3. Relaunch the viewer with `--previous-workspace` pointing at the
   previous iteration.
4. Wait for the user to review and say they're done.
5. Read the new feedback, improve again, repeat.

Stop when the user says they're happy, the feedback is all empty, or
progress stalls.
