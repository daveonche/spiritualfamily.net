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

	/**
	 * Must Support: "Every base image reference recorded at an exact pinned
	 * version for the current stage"
	 */
	public function testEveryBaseImageReferenceIsPinnedAndRecordedInBaseline()
	{
		$dockerfile = $this->dockerfile();
		$this->assertFileExists($dockerfile);
		$contents = file_get_contents($dockerfile);

		// Collect every FROM line's image reference
		preg_match_all('/^FROM\s+(\S+)/m', $contents, $fromMatches);
		$fromRefs = $fromMatches[1];
		$this->assertNotEmpty(
			$fromRefs,
			'No FROM lines found in the Dockerfile'
		);

		foreach ($fromRefs as $ref) {
			// Every reference carries an explicit tag (no bare image name)
			$this->assertSame(
				1,
				preg_match('/:[^\/\s@]+$/', $ref),
				"Base image reference '$ref' has no explicit tag"
			);
			// No floating "latest" reference
			$this->assertNotRegExp(
				'/:latest$/',
				$ref,
				"Base image reference '$ref' floats the \"latest\" tag"
			);
		}

		// The upstream webserver base image is recorded in the baseline doc
		// at the pinned runtime tag
		$baselineFile = $this->repoDir() . '/docs/baseline/baseline.md';
		$this->assertFileExists($baselineFile);
		$baseline = file_get_contents($baselineFile);
		$this->assertSame(
			1,
			preg_match(
				'#coldtrick/elgg_webserver:' . preg_quote(self::PINNED_RUNTIME_DOCKER_TAG, '#') . '#',
				$baseline
			),
			'baseline.md does not record the upstream webserver base image at the pinned tag'
		);

		// The Composer base image's exact resolved version is recorded in
		// the baseline environment note (composer:2 is a major tag; the
		// exact version is the recorded pin for the current stage). The
		// doc wraps the phrase across a line break, so allow \s+.
		$this->assertSame(
			1,
			preg_match('/Composer 2\.x \(2\.10\.3 at\s+time of recording\)/', $baseline),
			'baseline.md does not record the exact resolved Composer version'
		);
	}
}
