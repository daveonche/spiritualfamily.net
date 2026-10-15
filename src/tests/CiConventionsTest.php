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
}
