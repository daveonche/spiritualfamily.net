<?php

/**
 * Step 5 tests for Story S1.2 — exact database server version pinning.
 *
 * Verifies that the dbm service image is pinned to an exact database
 * server version (no floating reference) and that the pin is recorded in
 * the project documentation alongside the Story S1.1 version pair.
 *
 * The live local-vs-production version comparison is manual verification
 * scope (see docs/analysis/S1.2-story-steps.md Step 5); baseline.md
 * records the production-reported version string as the static proxy.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposeDatabasePinTest extends \PHPUnit_Framework_TestCase
{
	/** The single pinned database server version (docs/baseline/baseline.md). */
	const PINNED_DB_VERSION = '10.11.18';

	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function composeFile()
	{
		return $this->repoDir() . '/compose.yml';
	}

	/** Comment-stripped compose.yml contents (prose may say anything). */
	private function composeConfig()
	{
		return preg_replace('/^\s*#.*$/m', '', file_get_contents($this->composeFile()));
	}

	/**
	 * Must Support: "An exact database server version replacing any
	 * floating 'latest' reference"
	 */
	public function testDbmServiceImageIsPinnedToExactDatabaseVersion()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// The dbm service block, bounded by the next service definition
		$this->assertSame(
			1,
			preg_match('/^  dbm:\s*$(.*?)^  pma:/ms', $config, $m),
			"compose.yml has no recognizable 'dbm' service block"
		);
		$dbmBlock = $m[1];

		// Exact pin: image: mariadb:<exact version> — no variable-driven tag
		$this->assertSame(
			1,
			preg_match('/^    image:\s*mariadb:([^\s"#]+)\s*$/m', $dbmBlock, $m2),
			"compose.yml dbm service image is not a pinned mariadb reference"
		);
		$this->assertSame(
			self::PINNED_DB_VERSION,
			$m2[1],
			'compose.yml dbm image tag does not match the pinned database version'
		);

		// No floating reference: reject "latest" and any ${...} interpolation
		$this->assertNotSame('latest', $m2[1], 'dbm image floats the "latest" tag');
		$this->assertSame(
			0,
			preg_match('/\$\{/', $m2[1]),
			'dbm image tag must not be variable-driven'
		);
	}
}
