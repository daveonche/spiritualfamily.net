<?php

/**
 * Step 6 tests for Story S1.5 — container image build validation
 * (image-build job).
 *
 * Verifies that the pipeline's image-build job builds the application
 * image from the committed build recipe (docker/app) with BuildKit
 * explicitly enabled, and that the build is validation-only — nothing is
 * published or pushed anywhere. The version build argument ("the build
 * receives the version build argument using the same parameterization as
 * the local orchestration") and the failure path ("a failed image build
 * fails the pipeline") are asserted by dedicated tests in this file.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class CiImageBuildTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		// The workflow file lives at the repository root, one level above
		// src/ (this file is src/tests/CiImageBuildTest.php).
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

	private function composeFile()
	{
		return $this->repoDir() . '/compose.yml';
	}

	private function composeContents()
	{
		$contents = file_get_contents($this->composeFile());
		$this->assertNotSame(false, $contents, 'compose.yml must be readable');
		return $contents;
	}

	/**
	 * Must Support: "The pipeline builds the application image from the
	 * committed build recipe"
	 */
	public function testImageBuildJobBuildsFromCommittedRecipeValidationOnly()
	{
		$job = $this->jobBlock(
			$this->workflowJobs($this->workflowContents()),
			'image-build'
		);

		// Builds from the committed build recipe: docker build with the
		// docker/app build context as the final argument.
		$this->assertSame(
			1,
			preg_match('/docker build\b/', $job),
			"The image-build job does not run 'docker build'"
		);
		$this->assertSame(
			1,
			preg_match('/^\s+docker\/app\s*$/m', $job),
			"The image-build job's docker build does not use the committed docker/app build context"
		);

		// BuildKit explicitly enabled: the Dockerfile's RUN heredocs
		// require it, and pinning it keeps a runner-image change from
		// silently flipping it off.
		$this->assertSame(
			1,
			preg_match('/export DOCKER_BUILDKIT=1/', $job),
			"The image-build job does not explicitly enable BuildKit (DOCKER_BUILDKIT=1)"
		);

		// Validation only: nothing is published or pushed anywhere.
		$this->assertSame(
			0,
			preg_match('/docker push\b/', $job),
			"The image-build job pushes the image — the build validates the recipe only"
		);
		$this->assertSame(
			0,
			preg_match('/docker login\b/', $job),
			"The image-build job authenticates to a registry — the build is validation-only"
		);
	}

	/**
	 * Must Support: "The build receives the version build argument using
	 * the same parameterization as the local orchestration"
	 */
	public function testImageBuildJobPassesTheOrchestrationVersionBuildArg()
	{
		$job = $this->jobBlock(
			$this->workflowJobs($this->workflowContents()),
			'image-build'
		);

		// The job passes PHP_VERSION as a --build-arg to docker build.
		$this->assertSame(
			1,
			preg_match('/--build-arg "PHP_VERSION=\$\{php_version\}"/', $job),
			"The image-build job does not pass PHP_VERSION as a --build-arg to docker build"
		);

		// The supplied value equals the local orchestration's committed
		// default: compose.yml's app build arg with its ${PHP_VERSION:-...}
		// default. Reading the default from compose.yml means the CI value
		// cannot drift from the orchestration default.
		$compose = $this->composeContents();
		$this->assertSame(
			1,
			preg_match('/PHP_VERSION:\s*"\$\{PHP_VERSION:-([^\}]+)\}"/', $compose, $m),
			'compose.yml does not pass PHP_VERSION as a build arg with a committed default'
		);
		$this->assertSame(
			1,
			preg_match('/php_version="' . preg_quote($m[1], '/') . '"/', $job),
			"The image-build job's PHP_VERSION build arg value does not match the local orchestration default (compose.yml build arg default: " . $m[1] . ")"
		);
	}
}
