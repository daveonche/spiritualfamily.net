<?php

/**
 * Step 7 tests for Story S1.5 — finalize the pipeline configuration to
 * repository conventions with validation-only scope.
 *
 * Verifies that the pipeline's workflow configuration lives in the
 * repository's conventional location (.github/workflows/, exactly the
 * ci.yml file), that it conforms to the machine-checkable subset of the
 * documented CI/CD conventions reference (.agent/specs/references/
 * ci-cd-best-practices.md — SHA-pinned uses references with version
 * comments, explicit workflow-level permissions with a contents: read
 * baseline, cache keys without github.run_id, fetch-depth: 0 only where
 * full history is genuinely required), and that the pipeline contains
 * build and validation jobs only — no deployment actions. actionlint
 * validation and SHA-vs-upstream-tag verification remain manual steps.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiConventionsTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		// The workflow file lives at the repository root, one level above
		// src/ (this file is src/tests/CiConventionsTest.php).
		return dirname(dirname(__DIR__));
	}

	private function workflowsDir()
	{
		return $this->repoDir() . '/.github/workflows';
	}

	private function workflowFile()
	{
		return $this->workflowsDir() . '/ci.yml';
	}

	private function workflowContents()
	{
		$contents = file_get_contents($this->workflowFile());
		$this->assertNotSame(false, $contents, '.github/workflows/ci.yml must be readable');
		return $contents;
	}

	/**
	 * Extracts a top-level YAML block: the "$key:" line at column zero
	 * through the line before the next top-level key (or EOF).
	 */
	private function topLevelBlock($contents, $key)
	{
		$lines = explode("\n", $contents);
		$block = array();
		$collecting = false;
		foreach ($lines as $line) {
			if (!$collecting) {
				if (preg_match('/^' . preg_quote($key, '/') . ':$/', $line)) {
					$collecting = true;
				}
				continue;
			}
			if (preg_match('/^[^\s#]/', $line)) {
				// The next top-level key ends the block.
				break;
			}
			$block[] = $line;
		}
		$this->assertTrue($collecting, "The workflow must declare a top-level '$key:' key");
		return implode("\n", $block);
	}

	private function workflowJobs($contents)
	{
		return $this->topLevelBlock($contents, 'jobs');
	}

	/**
	 * Extracts one job's block (its 2-space-indented key and body) from
	 * the jobs block.
	 */
	private function jobBlock($contents, $name)
	{
		$lines = explode("\n", $contents);
		$block = array();
		$collecting = false;
		foreach ($lines as $line) {
			if (!$collecting) {
				if (preg_match('/^  ' . preg_quote($name, '/') . ':$/', $line)) {
					$collecting = true;
				}
				continue;
			}
			if (preg_match('/^  \S/', $line)) {
				// The next job key ends the block.
				break;
			}
			$block[] = $line;
		}
		$this->assertTrue($collecting, "The workflow must declare a '$name' job");
		return implode("\n", $block);
	}

	/**
	 * Must Support: "Workflow configuration files committed in the
	 * repository's conventional location"
	 */
	public function testWorkflowConfigurationLivesInConventionalLocation()
	{
		// The conventional location for GitHub Actions workflow files.
		$this->assertTrue(
			is_dir($this->workflowsDir()),
			"The workflow configuration does not live in the conventional .github/workflows/ location"
		);

		// Exactly one workflow file — ci.yml. A second workflow file would
		// be an undeclared pipeline outside this story's single-pipeline
		// design.
		$workflows = glob($this->workflowsDir() . '/*.y*ml');
		$this->assertSame(
			array($this->workflowFile()),
			$workflows,
			"The conventional location must contain exactly the ci.yml workflow — an additional workflow file is an undeclared pipeline"
		);
		$this->assertFileExists($this->workflowFile());
	}

	/**
	 * Must Support: "Conformance with the repository's documented CI/CD
	 * conventions reference"
	 */
	public function testWorkflowConformsToConventionsReference()
	{
		$contents = $this->workflowContents();

		// Every uses: reference pinned to a full-length commit SHA with a
		// version comment. Tags and branches on actions are mutable; only
		// full-length commit SHAs are immutable.
		$this->assertSame(
			1,
			preg_match_all('/^\s+uses:\s*([^\s#]+)\s*#\s*(\S+)\s*$/m', $contents, $uses, PREG_SET_ORDER),
			'The workflow declares no pinned uses: reference — every action must be pinned'
		);
		foreach ($uses as $use) {
			$this->assertSame(
				1,
				preg_match('/^[0-9a-f]{40}$/', $use[1]),
				"The uses: reference '{$use[1]}' is not pinned to a full-length commit SHA — mutable references (@main, @latest, major tags) can be silently moved"
			);
			$this->assertSame(
				1,
				preg_match('/^v\d/', $use[2]),
				"The pinned uses: reference '{$use[1]}' carries no version comment — the pinned SHA's version must be documented"
			);
		}

		// Workflow-level permissions explicitly set with the contents: read
		// baseline — an unset permissions block is a security finding, not
		// a convenience.
		$permissions = $this->topLevelBlock($contents, 'permissions');
		$this->assertSame(
			1,
			preg_match('/^\s+contents:\s*read\s*$/m', $permissions),
			'The workflow-level permissions baseline is not "contents: read" — an unset permissions block is a security finding'
		);

		// Cache keys must change only when the cached content changes —
		// github.run_id (always fresh) would build a key that never hits.
		$this->assertSame(
			0,
			preg_match('/github\.run_id/', $contents),
			'The workflow uses github.run_id — a cache key containing it always misses; keys must change only when the cached content changes'
		);

		// fetch-depth: 0 only where full history is genuinely required: it
		// appears exactly once, in the commit-msg job, with the reason
		// documented inline.
		$this->assertSame(
			1,
			preg_match_all('/^\s+fetch-depth:\s*0\s*$/m', $contents),
			'fetch-depth: 0 must appear exactly once — only where full history is genuinely required'
		);
		$commitMsg = $this->jobBlock($contents, 'commit-msg');
		$this->assertSame(
			1,
			preg_match('/^\s+fetch-depth:\s*0\s*$/m', $commitMsg),
			"The single fetch-depth: 0 does not belong to the 'commit-msg' job — the only job whose push-range resolution genuinely requires full history"
		);
		$this->assertSame(
			1,
			preg_match('/Full history is genuinely required/', $commitMsg),
			"The commit-msg job's fetch-depth: 0 carries no documented reason — full history is justified only where genuinely required"
		);
	}
}
