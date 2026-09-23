<?php

/**
 * Step 1 tests for Story S1.2 — version-parameterized web application image build.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposeImageBuildTest extends \PHPUnit_Framework_TestCase
{
	/** Docker-tag form of the stage's pinned PHP runtime (docs/baseline/baseline.md). */
	const PINNED_RUNTIME_DOCKER_TAG = '7.4';

	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function dockerfile()
	{
		return $this->repoDir() . '/docker/app/Dockerfile';
	}

	/**
	 * Must Support: "Building the web application image with the runtime
	 * version supplied as a build-time input"
	 */
	public function testDockerfileDeclaresAndConsumesPhpVersionBuildArg()
	{
		$dockerfile = $this->dockerfile();
		$this->assertFileExists($dockerfile);
		$contents = file_get_contents($dockerfile);

		// ARG PHP_VERSION declared with the stage's pinned runtime tag as default
		$this->assertSame(
			1,
			preg_match('/^ARG\s+PHP_VERSION=(\S+)/m', $contents, $m),
			'Dockerfile does not declare ARG PHP_VERSION with a default value'
		);
		$this->assertSame(
			self::PINNED_RUNTIME_DOCKER_TAG,
			$m[1],
			'ARG PHP_VERSION default does not match the pinned runtime Docker tag'
		);

		// The runtime FROM line consumes the build arg (no hard-coded tag)
		$this->assertSame(
			1,
			preg_match('/^FROM\s+coldtrick\/elgg_webserver:\$\{PHP_VERSION\}/m', $contents),
			'The runtime FROM line does not consume the PHP_VERSION build arg'
		);
	}
}
