<?php

/**
 * Step 2 tests for Story S1.1 — pinned PHP runtime version.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class BaselineRuntimeVersionTest extends \PHPUnit_Framework_TestCase
{
	/** The single pinned PHP runtime version (docs/baseline/baseline.md). */
	const PINNED_RUNTIME_VERSION = '7.4.27';

	/** Minimum PHP required by the pinned Elgg 2.3.17 core. */
	const MIN_PHP_REQUIRED_BY_CORE = '5.6';

	private function srcDir()
	{
		return dirname(__DIR__);
	}

	/**
	 * Must Support: "Determination of the runtime language version required
	 * by the pinned core version"
	 */
	public function testPlatformPhpPinSatisfiesCoreConstraint()
	{
		// src/composer.json pins config.platform.php to the runtime version.
		$composer = json_decode(
			file_get_contents($this->srcDir() . '/composer.json'),
			true
		);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.json is not valid JSON');
		$this->assertArrayHasKey('config', $composer);
		$this->assertArrayHasKey('platform', $composer['config']);
		$this->assertArrayHasKey('php', $composer['config']['platform']);
		$this->assertSame(
			self::PINNED_RUNTIME_VERSION,
			$composer['config']['platform']['php'],
			'config.platform.php does not match the pinned runtime version'
		);

		// The pinned core declares its acceptable runtime constraint.
		$coreComposer = json_decode(
			file_get_contents($this->srcDir() . '/vendor/elgg/elgg/composer.json'),
			true
		);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'vendored composer.json is not valid JSON');
		$this->assertArrayHasKey('require', $coreComposer);
		$this->assertArrayHasKey('php', $coreComposer['require']);
		$this->assertSame(
			'>=5.6',
			$coreComposer['require']['php'],
			'Pinned core no longer declares the expected PHP constraint'
		);

		// The pinned runtime satisfies the core's constraint.
		$this->assertTrue(
			version_compare(self::PINNED_RUNTIME_VERSION, self::MIN_PHP_REQUIRED_BY_CORE, '>='),
			'Pinned runtime version does not satisfy the constraint declared by the pinned core'
		);
	}
}
