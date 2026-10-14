<?php

/**
 * Step 2 tests for Story S1.5 — dependency manifest validation.
 *
 * Verifies that the CI pipeline validates the dependency manifest's
 * structure and declared metadata with `composer validate --strict`,
 * independent of installation (nothing is downloaded, no install step),
 * and that a failed validation marks the whole pipeline failed rather
 * than degrading into a warning. Lock-related checks are deliberately
 * out of scope here: manifest ↔ lock consistency is Step 5's dedicated
 * check.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiManifestValidationTest extends \PHPUnit_Framework_TestCase
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

	/** The job's container image reference, or null when it declares none. */
	private function jobContainerImage($jobBlock)
	{
		if (preg_match('/^\s+image:\s*(\S+)/m', $jobBlock, $match)) {
			return $match[1];
		}

		return null;
	}

	/** The named job's block, failing the test when the job is absent. */
	private function jobBlock($contents, $name)
	{
		$jobs = $this->workflowJobs($contents);
		$this->assertArrayHasKey($name, $jobs, "The CI workflow has no '$name' job");

		return $jobs[$name];
	}

	/**
	 * Must Support: "A check that validates the dependency manifest's
	 * structure and declared metadata"
	 */
	public function testComposerValidateJobValidatesManifestStructureAndMetadata()
	{
		$job = $this->jobBlock($this->workflowContents(), 'composer-validate');

		// Metadata-only Composer job: rides the era-compatible Composer
		// LTS line (it executes no project PHP).
		$image = $this->jobContainerImage($job);
		$this->assertNotNull($image, "Job 'composer-validate' declares no container image");
		$this->assertRegExp(
			'/^composer:' . self::COMPOSER_LTS_LINE . '\.\d+$/',
			$image,
			"Job 'composer-validate' does not run on the era-compatible Composer " . self::COMPOSER_LTS_LINE . " LTS line"
		);

		// The manifest lives in src/ (src/composer.json), not at the
		// checkout root.
		$this->assertSame(
			1,
			preg_match('/^\s+working-directory:\s*src\s*$/m', $job),
			"Job 'composer-validate' does not run against src/ — the manifest lives in src/composer.json"
		);

		// The validate invocation as implemented: --strict plus the
		// scoping flags that keep the check manifest-only (lock
		// consistency is Step 5's dedicated check; this repository is a
		// project, not a published package).
		$this->assertSame(
			1,
			preg_match(
				'/^\s+composer validate --strict --no-check-all --no-check-lock --no-check-publish --no-check-version/m',
				$job
			),
			"Job 'composer-validate' does not run the expected 'composer validate --strict' invocation with the manifest-only scoping flags"
		);

		// Independent of installation: nothing is downloaded or installed.
		$this->assertSame(
			0,
			preg_match('/composer\s+(install|update)\b/', $job),
			"Job 'composer-validate' downloads or installs dependencies — manifest validation must surface problems before anything is downloaded"
		);
	}

	/**
	 * Must Support: "A failed validation marks the whole pipeline failed"
	 */
	public function testFailedValidationFailsTheWholePipeline()
	{
		$job = $this->jobBlock($this->workflowContents(), 'composer-validate');

		// Fail-fast shell: a non-zero validate exit fails the step, the
		// step failure fails the job, and a failed job fails the pipeline.
		$this->assertSame(
			1,
			preg_match('/^\s+set -euo pipefail\s*$/m', $job),
			"Job 'composer-validate' does not run its steps with 'set -euo pipefail' — a failed validation could be swallowed"
		);

		// --strict turns every validation warning into a failure, so a
		// failed validation can never degrade into a warning.
		$this->assertSame(
			1,
			preg_match('/composer validate --strict\b/', $job),
			"Job 'composer-validate' does not run 'composer validate' with --strict — warnings would not fail the pipeline"
		);

		// The validate line ends at its flags: no '|| true'-style suffix
		// that would mask a failed validation's exit code.
		$this->assertSame(
			1,
			preg_match(
				'/^\s+composer validate --strict --no-check-all --no-check-lock --no-check-publish --no-check-version\s*$/m',
				$job
			),
			"Job 'composer-validate' does not run 'composer validate --strict' as a bare final command — a suppression suffix would mask a failed validation"
		);

		// No error tolerance: continue-on-error would let a failed
		// validation pass the pipeline.
		$this->assertSame(
			0,
			preg_match('/continue-on-error/', $job),
			"Job 'composer-validate' declares continue-on-error — a failed validation would not fail the pipeline"
		);
	}
}
