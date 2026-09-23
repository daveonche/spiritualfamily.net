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

	/**
	 * Must Support: "Building the web application image with the runtime
	 * version supplied as a build-time input" (Compose side)
	 */
	public function testComposePassesPhpVersionBuildArgAndTagsImageWithIt()
	{
		$composeFile = $this->repoDir() . '/compose.yml';
		$this->assertFileExists($composeFile);
		$contents = file_get_contents($composeFile);

		// build.args.PHP_VERSION interpolates the environment with the
		// stage's pinned default
		$this->assertSame(
			1,
			preg_match('/PHP_VERSION:\s*"\$\{PHP_VERSION:-7\.4\}"/', $contents),
			'compose.yml does not pass PHP_VERSION as a build arg with the pinned default'
		);

		// The built image is tagged with the same interpolated version so
		// each PHP version builds under its own tag
		$this->assertSame(
			1,
			preg_match('/image:\s*"sfn\/elgg_webserver:\$\{PHP_VERSION:-7\.4\}-composer2"/', $contents),
			'compose.yml does not tag the built image with the interpolated PHP version'
		);
	}
}
