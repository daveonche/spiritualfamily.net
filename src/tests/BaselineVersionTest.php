<?php

/**
 * Step 1 tests for Story S1.1 — pinned Elgg core version.
 *
 * Verifies that the exact core version currently in use is determinable
 * from the existing codebase and matches the pinned baseline value
 * recorded in docs/baseline/baseline.md.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class BaselineVersionTest extends \PHPUnit_Framework_TestCase
{
	/** The single pinned Elgg core version (docs/baseline/baseline.md). */
	const PINNED_CORE_VERSION = '2.3.17';

	/** src/, resolved relative to this file so the suite runs from anywhere. */
	private function srcDir()
	{
		return dirname(__DIR__);
	}

	private function elggCoreDir()
	{
		return $this->srcDir() . '/vendor/elgg/elgg';
	}

	/** Repository-root docs/baseline/baseline.md, resolved from this file's location. */
	private function docsBaselineFile()
	{
		return dirname($this->srcDir()) . '/docs/baseline/baseline.md';
	}

	/**
	 * Must Support: "Determination of the exact core version currently in
	 * use, taken from the existing codebase"
	 */
	public function testCodebaseReportsPinnedCoreVersion()
	{
		// Vendored core's version.php defines $release — Elgg's own
		// human-readable version string.
		$versionFile = $this->elggCoreDir() . '/version.php';
		$this->assertFileExists($versionFile);

		$release = null;
		include $versionFile;
		$this->assertSame(
			self::PINNED_CORE_VERSION,
			$release,
			"version.php \$release does not match the pinned core version"
		);

		// Vendored core's composer.json "version" field.
		$composerFile = $this->elggCoreDir() . '/composer.json';
		$this->assertFileExists($composerFile);

		$composer = json_decode(file_get_contents($composerFile), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'vended composer.json is not valid JSON');
		$this->assertArrayHasKey('version', $composer);
		$this->assertSame(
			self::PINNED_CORE_VERSION,
			$composer['version'],
			"composer.json 'version' does not match the pinned core version"
		);
	}

	/**
	 * Must Support: "A single recorded, pinned version value that all
	 * later steps reference"
	 */
	public function testSinglePinnedCoreVersionInDocumentation()
	{
		$docFile = $this->docsBaselineFile();
		$this->assertFileExists($docFile);

		$doc = file_get_contents($docFile);

		// Exactly one pinned Elgg core row in the Pinned Versions table.
		$this->assertSame(
			1,
			preg_match_all('/^\|\s*Elgg core\s*\|\s*([^|]+?)\s*\|/m', $doc, $matches),
			'Expected exactly one "Elgg core" row in the Pinned Versions table'
		);

		$this->assertSame(
			self::PINNED_CORE_VERSION,
			trim($matches[1][0]),
			'Recorded pinned core version does not match the pinned value'
		);
	}
}
