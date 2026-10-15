<?php

/**
 * Step 5 tests for Story S1.5 — manifest ↔ lock file consistency
 * (composer-lock-consistency job).
 *
 * Verifies that the pipeline's composer-lock-consistency job detects
 * divergence between the dependency manifest and the lock file: the job
 * runs against src/ (where composer.json and composer.lock live) and its
 * `composer validate --strict` invocation deliberately enables the lock
 * check (no --no-check-lock), so Composer's local content-hash comparison
 * reports any divergence. The failure path ("any divergence fails the
 * pipeline rather than producing a warning") is asserted by a dedicated
 * test in this file.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiLockConsistencyTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		// The workflow file lives at the repository root, one level above
		// src/ (this file is src/tests/CiLockConsistencyTest.php).
		return dirname(dirname(__DIR__));
	}

	private function workflowFile()
	{
		return $this->repoDir() . '/.github/workflows/ci.yml';
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
	 * Must Support: "A check that detects divergence between the manifest
	 * and the lock file"
	 */
	public function testLockConsistencyJobDetectsManifestLockDivergence()
	{
		$job = $this->jobBlock(
			$this->workflowJobs($this->workflowContents()),
			'composer-lock-consistency'
		);

		// The manifest and lock file live in src/ (src/composer.json,
		// src/composer.lock), so the comparison must run there.
		$this->assertSame(
			1,
			preg_match('/^\s+working-directory:\s*src\s*$/m', $job),
			"Job 'composer-lock-consistency' does not run against src/ — the manifest and lock file live in src/"
		);

		// The validate invocation as implemented: --strict plus the
		// scoping flags, with the lock check deliberately ENABLED (no
		// --no-check-lock) so Composer compares the lock file's
		// content-hash against the manifest locally.
		$this->assertSame(
			1,
			preg_match(
				'/composer validate --strict --no-check-all --no-check-publish --no-check-version\b/',
				$job
			),
			"Job 'composer-lock-consistency' does not run the expected 'composer validate --strict' invocation with the lock check enabled"
		);

		// The lock check must not be disabled on the invocation line
		// itself. The guard is scoped to the command's own line because
		// the job's explanatory comment mentions --no-check-lock.
		$this->assertSame(
			0,
			preg_match(
				'/composer validate --strict --no-check-all --no-check-publish --no-check-version[^\n]*--no-check-lock/',
				$job
			),
			"Job 'composer-lock-consistency' disables the lock check on its validate invocation — divergence would go undetected"
		);
	}
}
