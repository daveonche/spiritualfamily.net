<?php

/**
 * Step 3 tests for Story S1.5 — reproducible dependency installation from
 * the committed lock file.
 *
 * Verifies that the CI pipeline installs dependencies using exactly the
 * versions recorded in the committed lock file with no re-resolution,
 * and that a missing or unusable lock file fails the check before any
 * install runs. Manifest validation (Step 2) and manifest ↔ lock
 * consistency (Step 5) are separate dedicated checks.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiLockInstallTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function workflowFile()
	{
		return $this->repoDir() . '/.github/workflows/ci.yml';
	}

	private function workflowContents()
	{
		$file = $this->workflowFile();
		$this->assertFileExists($file);

		return file_get_contents($file);
	}

	/**
	 * Extract a top-level YAML block: the "key:" line at column zero
	 * through the next column-zero line, or the end of the file.
	 */
	private function topLevelBlock($contents, $key)
	{
		if (!preg_match('/^' . preg_quote($key, '/') . ':\s*$/m', $contents, $match, PREG_OFFSET_CAPTURE)) {
			return null;
		}
		$rest = substr($contents, $match[0][1] + strlen($match[0][0]));
		if (!preg_match('/^\S/m', $rest, $next, PREG_OFFSET_CAPTURE)) {
			return $rest;
		}

		return substr($rest, 0, $next[0][1]);
	}

	/**
	 * The workflow's jobs as a map of job name => job block body. Job keys
	 * sit at exactly two-space indent; every deeper line (job fields, step
	 * fields, run-block content) is indented further.
	 */
	private function workflowJobs($contents)
	{
		$jobsBlock = $this->topLevelBlock($contents, 'jobs');
		if ($jobsBlock === null) {
			return array();
		}

		if (!preg_match_all(
			'/^  ([a-z][a-z0-9-]*):\s*$(.*?)(?=^  [a-z][a-z0-9-]*:\s*$|\z)/ms',
			$jobsBlock,
			$matches,
			PREG_SET_ORDER
		)) {
			return array();
		}

		$jobs = array();
		foreach ($matches as $job) {
			$jobs[$job[1]] = $job[2];
		}

		return $jobs;
	}

	/** The named job's block, failing the test when the job is absent. */
	private function jobBlock($contents, $name)
	{
		$jobs = $this->workflowJobs($contents);
		$this->assertArrayHasKey($name, $jobs, "The CI workflow has no '$name' job");

		return $jobs[$name];
	}

	/**
	 * The named step's block within a job: the "- name: <name>" line
	 * through the next step's "- name:" line, or the end of the job.
	 * Step keys sit at exactly six-space indent.
	 */
	private function stepBlock($jobBlock, $name)
	{
		if (!preg_match(
			'/^      - name: ' . preg_quote($name, '/') . '\s*$(.*?)(?=^      - name: |\z)/ms',
			$jobBlock,
			$match
		)) {
			return null;
		}

		return $match[0];
	}

	/**
	 * Must Support: "Installation that uses exactly the versions recorded
	 * in the committed lock file, with no re-resolution"
	 */
	public function testInstallUsesExactlyTheLockedVersionsWithNoReResolution()
	{
		$job = $this->jobBlock($this->workflowContents(), 'composer-install');

		$install = $this->stepBlock($job, 'Install dependencies exactly as locked');
		$this->assertNotNull($install, "Job 'composer-install' has no 'Install dependencies exactly as locked' step");

		// The install runs against the committed lock in src/ (the
		// manifest and lock file live in src/, not at the checkout root).
		$this->assertSame(
			1,
			preg_match('/^\s+working-directory:\s*src\s*$/m', $install),
			"The install step does not run in src/ — the committed lock file lives at src/composer.lock"
		);

		// Fail-fast shell: a failed install must fail the step (and the
		// pipeline), never continue past it.
		$this->assertSame(
			1,
			preg_match('/^\s+set -euo pipefail\s*$/m', $install),
			"The install step does not run with 'set -euo pipefail' — a failed install could be swallowed"
		);

		// The exact lock-driven invocation: composer install (reads the
		// lock file) with no re-resolution flags appended.
		$this->assertSame(
			1,
			preg_match(
				'/^\s+composer install --no-interaction --no-progress 2>&1 \| tee \/tmp\/composer-install\.log\s*$/m',
				$install
			),
			"The install step does not run 'composer install --no-interaction --no-progress' with its output captured to a log"
		);

		// No re-resolution: composer update (fresh resolution) must not
		// appear anywhere in the job.
		$this->assertSame(
			0,
			preg_match('/composer\s+update\b/', $job),
			"Job 'composer-install' runs 'composer update' — installation must use exactly the locked versions, never re-resolve"
		);

		// The captured output is asserted to prove lock-file-driven
		// installation; a re-resolved dependency set fails the job.
		$this->assertSame(
			1,
			preg_match(
				'/if ! grep -q "from lock file" \/tmp\/composer-install\.log; then/',
				$install
			),
			"The install step does not assert its output shows lock-file-driven installation"
		);
		$this->assertSame(
			1,
			preg_match(
				'/does not show lock-file-driven installation.*?exit 1/s',
				$install
			),
			"A missing 'from lock file' marker does not fail the install step — a re-resolved dependency set would go unchallenged"
		);
	}

	/**
	 * Must Support: "A missing or unusable lock file fails the check"
	 */
	public function testMissingOrUnusableLockFileFailsTheCheck()
	{
		$job = $this->jobBlock($this->workflowContents(), 'composer-install');

		$guard = $this->stepBlock($job, 'Guard — committed lock file must exist and be usable');
		$this->assertNotNull($guard, "Job 'composer-install' has no lock-file guard step");

		// The guard targets the committed lock's actual location.
		$this->assertSame(
			1,
			preg_match('/^\s+working-directory:\s*src\s*$/m', $guard),
			"The lock-file guard does not run in src/ — it would not check the committed src/composer.lock"
		);

		// Missing lock file: composer install without a lock silently
		// re-resolves fresh versions, so a missing lock must fail the
		// guard before install runs.
		$this->assertSame(
			1,
			preg_match('/if \[ ! -f composer\.lock \]; then.*?exit 1/s', $guard),
			"The guard does not fail on a missing composer.lock"
		);

		// Unusable lock: invalid JSON must fail the guard.
		$this->assertSame(
			1,
			preg_match('/is not usable: invalid JSON.*?exit\(1\);/s', $guard),
			"The guard does not fail on an unusable (invalid JSON) composer.lock"
		);

		// Unusable lock: an empty package set must fail the guard.
		$this->assertSame(
			1,
			preg_match('/is not usable: empty package set.*?exit\(1\);/s', $guard),
			"The guard does not fail on an unusable (empty package set) composer.lock"
		);

		// Ordering: the guard must run before the install step, so the
		// install cannot execute without the guard passing.
		$guardPos = strpos($job, 'committed lock file must exist');
		$installPos = strpos($job, 'Install dependencies exactly as locked');
		$this->assertNotFalse($guardPos, "Job 'composer-install' has no lock-file guard step");
		$this->assertNotFalse($installPos, "Job 'composer-install' has no install step");
		$this->assertLessThan(
			$installPos,
			$guardPos,
			"The lock-file guard does not run before the install step — install could run without the guard passing"
		);
	}
}
