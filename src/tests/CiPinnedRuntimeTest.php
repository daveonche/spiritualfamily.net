<?php

/**
 * Step 1 tests for Story S1.5 — automated pipeline execution on repository
 * changes.
 *
 * Verifies that the CI workflow starts automatically on pushes and on
 * changes proposed for review, and that every check executes on the single
 * pinned runtime version recorded in docs/baseline/baseline.md. The
 * runtime contract as implemented: jobs that execute PHP project code
 * (runtime, composer-install, php-lint) run in the php:<pinned>-cli
 * container and assert the actual runtime version against the pinned
 * value; the metadata-only Composer jobs ride the Composer 2.2 LTS line
 * (the era-compatible line for the pinned PHP baseline — they execute no
 * project PHP); runner-hosted jobs invoke no php binary. No check may run
 * on an arbitrary runtime.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiPinnedRuntimeTest extends \PHPUnit_Framework_TestCase
{
	/** The Composer LTS line compatible with the pinned PHP baseline era (metadata-only Composer jobs in ci.yml). */
	const COMPOSER_LTS_LINE = '2.2';

	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function workflowFile()
	{
		return $this->repoDir() . '/.github/workflows/ci.yml';
	}

	private function docsBaselineFile()
	{
		return $this->repoDir() . '/docs/baseline/baseline.md';
	}

	private function workflowContents()
	{
		$file = $this->workflowFile();
		$this->assertFileExists($file);

		return file_get_contents($file);
	}

	/**
	 * The single pinned PHP runtime version, read from the baseline doc's
	 * Pinned Versions table — the single recorded source.
	 */
	private function pinnedRuntimeVersion()
	{
		$file = $this->docsBaselineFile();
		$this->assertFileExists($file);
		$doc = file_get_contents($file);

		$this->assertSame(
			1,
			preg_match_all('/^\|\s*PHP runtime\s*\|\s*([^|]+?)\s*\|/m', $doc, $matches),
			'Expected exactly one "PHP runtime" row in the baseline Pinned Versions table'
		);

		return trim($matches[1][0]);
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

	/** The job's container image reference, or null when it declares none. */
	private function jobContainerImage($jobBlock)
	{
		if (preg_match('/^\s+image:\s*(\S+)/m', $jobBlock, $match)) {
			return $match[1];
		}

		return null;
	}

	/**
	 * Must Support: "The pipeline starts automatically whenever changes are
	 * pushed or proposed for review"
	 */
	public function testWorkflowTriggersOnPushAndPullRequest()
	{
		$on = $this->topLevelBlock($this->workflowContents(), 'on');
		$this->assertNotNull($on, 'The CI workflow declares no "on:" trigger block');

		$this->assertSame(
			1,
			preg_match('/^  push:\s*$/m', $on),
			'The pipeline does not start automatically on pushed changes — the workflow declares no "push" trigger'
		);
		$this->assertSame(
			1,
			preg_match('/^  pull_request:\s*$/m', $on),
			'The pipeline does not start automatically on changes proposed for review — the workflow declares no "pull_request" trigger'
		);
	}

	/**
	 * Must Support: "All checks execute on the single pinned runtime version
	 * recorded in the baseline documentation"
	 */
	public function testEveryJobRunsOnThePinnedRuntime()
	{
		$pinned = $this->pinnedRuntimeVersion();
		$jobs = $this->workflowJobs($this->workflowContents());
		$this->assertNotEmpty($jobs, 'No jobs found in the CI workflow');

		// Jobs that execute PHP project code must run in the
		// pinned-runtime container and assert the actual runtime version
		// against the pinned value (guards against a drifted image tag).
		foreach (array('runtime', 'composer-install', 'php-lint') as $name) {
			$this->assertArrayHasKey($name, $jobs, "The CI workflow has no '$name' job");
			$this->assertSame(
				'php:' . $pinned . '-cli',
				$this->jobContainerImage($jobs[$name]),
				"Job '$name' does not run in the pinned-runtime container"
			);
			$this->assertSame(
				1,
				preg_match('/echo PHP_VERSION/', $jobs[$name]),
				"Job '$name' does not read the actual runtime version for comparison"
			);
			$this->assertSame(
				1,
				preg_match('/^.*expected="' . preg_quote($pinned, '/') . '"\s*$/m', $jobs[$name]),
				"Job '$name' does not assert the runtime against the pinned version $pinned"
			);
		}

		// No check may run on an arbitrary runtime: every container image
		// is either the pinned PHP runtime or the era-compatible Composer
		// LTS line (those jobs execute no project PHP), and jobs without
		// a container invoke no php binary at all.
		foreach ($jobs as $name => $block) {
			$image = $this->jobContainerImage($block);
			if ($image === null) {
				$this->assertSame(
					0,
					preg_match('/\bphp\s+-[a-zA-Z]/', $block),
					"Job '$name' invokes the php binary without a pinned-runtime container"
				);
				continue;
			}
			if (strpos($image, 'php:') === 0) {
				$this->assertSame(
					'php:' . $pinned . '-cli',
					$image,
					"Job '$name' runs on a PHP image other than the pinned runtime"
				);
			} else {
				$this->assertSame(
					1,
					preg_match('/^composer:' . self::COMPOSER_LTS_LINE . '\.\d+$/', $image),
					"Job '$name' runs on container image '$image' — neither the pinned runtime nor the era-compatible Composer " . self::COMPOSER_LTS_LINE . " LTS line"
				);
			}
		}
	}
}
