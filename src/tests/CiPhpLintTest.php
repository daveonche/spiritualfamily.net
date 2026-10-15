<?php

/**
 * Step 4 tests for Story S1.5 — codebase-wide syntax linting (php-lint job).
 *
 * Verifies that the pipeline's php-lint job scans the project's own source
 * (src/, excluding vendored third-party code under src/vendor/) for PHP
 * syntax errors with `php -l`, and that the scan is non-vacuous. The
 * failure path ("any syntax error fails the pipeline") is asserted by a
 * dedicated test in this file.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiPhpLintTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		// The workflow file lives at the repository root, one level above
		// src/ (this file is src/tests/CiPhpLintTest.php).
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
	 * Extracts one step's block (its "- name:" entry and body) from a job
	 * block. Returns null when the step is absent.
	 */
	private function stepBlock($jobBlock, $name)
	{
		$lines = explode("\n", $jobBlock);
		$block = array();
		$collecting = false;
		foreach ($lines as $line) {
			if ($collecting && preg_match('/^      - /', $line)) {
				// The next step entry ends the block.
				break;
			}
			if (!$collecting) {
				if (preg_match('/^      - name: ' . preg_quote($name, '/') . '$/', $line)) {
					$collecting = true;
					$block[] = $line;
				}
				continue;
			}
			$block[] = $line;
		}
		if (!$collecting) {
			return null;
		}
		return implode("\n", $block);
	}

	/**
	 * Must Support: "A check that scans the project's source files for
	 * syntax errors"
	 */
	public function testLintJobScansProjectSourceExcludingVendoredCode()
	{
		$job = $this->jobBlock(
			$this->workflowJobs($this->workflowContents()),
			'php-lint'
		);
		$lint = $this->stepBlock($job, 'Lint project source for syntax errors');
		$this->assertNotNull(
			$lint,
			"The php-lint job must declare a 'Lint project source for syntax errors' step"
		);

		// Scan scope: the project's own source (src/), PHP files only,
		// vendored third-party code (src/vendor/) excluded.
		$this->assertSame(
			1,
			preg_match(
				'/find src -type f -name \x27\*\.php\x27 -not -path \x27src\/vendor\/\*\x27 -print0/',
				$lint
			),
			'The lint scan must cover src/ PHP files excluding src/vendor/'
		);

		// Every discovered file is linted with php -l.
		$this->assertSame(
			1,
			preg_match('/php -l "\$file"/', $lint),
			'Discovered files must be linted with php -l'
		);

		// Non-vacuous scan: an empty file set fails the step instead of
		// passing silently.
		$this->assertSame(
			1,
			preg_match('/no PHP files found under src\/.*?exit 1/s', $lint),
			'An empty scan scope must fail the step, not pass vacuously'
		);
	}

	/**
	 * Must Support: "Any syntax error fails the pipeline"
	 */
	public function testAnySyntaxErrorFailsThePipeline()
	{
		$job = $this->jobBlock(
			$this->workflowJobs($this->workflowContents()),
			'php-lint'
		);
		$lint = $this->stepBlock($job, 'Lint project source for syntax errors');
		$this->assertNotNull(
			$lint,
			"The php-lint job must declare a 'Lint project source for syntax errors' step"
		);

		// Fail-fast shell: the step aborts on any unexpected error.
		$this->assertSame(
			1,
			preg_match('/set -euo pipefail/', $lint),
			'The lint step must run under a fail-fast shell (set -euo pipefail)'
		);

		// php -l failures are captured per file, the offending file's own
		// file+line message is echoed, and each failure is counted.
		$this->assertSame(
			1,
			preg_match(
				'/if ! output="\$\(php -l "\$file" 2>&1\)"; then\s+echo "\$output"\s+failures=\$\(\(failures \+ 1\)\)/',
				$lint
			),
			'php -l failures must be captured, echoed with the offending file name, and counted'
		);

		// Any counted failure exits 1 — the pipeline fails.
		$this->assertSame(
			1,
			preg_match(
				'/if \[ "\$failures" -gt 0 \]; then\s+echo "::error::\$failures of \$\{\#files\[@\]\} PHP files failed the syntax check"\s+exit 1/',
				$lint
			),
			'Any counted syntax failure must exit 1 and fail the pipeline'
		);
	}
}
