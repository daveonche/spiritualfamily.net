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

	/** Baseline documentation file (docs mounted read-only as a sibling of WWW_ROOT). */
	private function docsBaselineFile()
	{
		return dirname(__DIR__) . '/../docs/baseline/baseline.md';
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

	/**
	 * Must Support: "A pinned version matching the production database
	 * server so local and production environments stay in sync"
	 *
	 * Static proxy: baseline.md records the production-reported version
	 * string; the live local-vs-production comparison is manual
	 * verification scope (see docs/analysis/S1.2-story-steps.md Step 5).
	 */
	public function testPinnedVersionMatchesProductionVersionRecordedInBaseline()
	{
		$baselineFile = $this->docsBaselineFile();
		$this->assertFileExists($baselineFile);
		$contents = file_get_contents($baselineFile);

		// Production-reported version string recorded in the baseline doc
		// (10.11.18-MariaDB-log via phpMyAdmin server info)
		$this->assertSame(
			1,
			preg_match('/production reports\s+`?([0-9]+\.[0-9]+\.[0-9]+)-MariaDB-log/', $contents, $m),
			'baseline.md does not record the production-reported MariaDB version'
		);

		// The pinned tag must match the production version's numeric core
		$this->assertSame(
			self::PINNED_DB_VERSION,
			$m[1],
			'pinned dbm tag does not match the production-reported version'
		);
	}

	/**
	 * Must Support: "A version choice that remains compatible across the
	 * journey or can be upgraded alongside it"
	 *
	 * Static proxy: baseline.md records the upgrade policy for the
	 * database pin — bump the mariadb tag deliberately alongside the
	 * upgrade journey, never float. The compatibility judgment itself
	 * remains a human decision at each journey stage.
	 */
	public function testDatabaseUpgradePolicyRecordedInBaseline()
	{
		$baselineFile = $this->docsBaselineFile();
		$this->assertFileExists($baselineFile);
		$contents = file_get_contents($baselineFile);

		// The deliberate-bump / never-float policy is recorded for the
		// database pin; \s+ tolerates the doc's hard line wraps (the
		// sentence wraps mid-phrase in baseline.md)
		$this->assertSame(
			1,
			preg_match('/Bump\s+the\s+`mariadb:`\s+tag\s+deliberately\s+alongside\s+the\s+upgrade\s+journey,\s+never\s+float/', $contents),
			'baseline.md does not record the database upgrade policy (bump deliberately alongside the journey, never float)'
		);
	}

	/**
	 * Must Support: "The pinned version recorded in project documentation"
	 */
	public function testSinglePinnedDatabaseVersionInDocumentation()
	{
		$baselineFile = $this->docsBaselineFile();
		$this->assertFileExists($baselineFile);
		$contents = file_get_contents($baselineFile);

		// Exactly one "Database server" row in the Pinned Versions table
		// (section delimited by the next "## " heading)
		$this->assertSame(
			1,
			preg_match('/^## Pinned Versions\s*$(.*?)^## /ms', $contents, $m),
			'baseline.md has no Pinned Versions section'
		);
		$this->assertSame(
			1,
			preg_match_all('/^\|\s*Database server\s*\|/m', $m[1]),
			'baseline.md must record the database server pin exactly once in the Pinned Versions table'
		);

		// The recorded pin matches the pinned version constant
		$this->assertSame(
			1,
			preg_match('/^\|\s*Database server\s*\|\s*MariaDB\s+([0-9]+\.[0-9]+\.[0-9]+)\s*\|/m', $m[1], $m2),
			'baseline.md does not record an exact MariaDB version in the Database server row'
		);
		$this->assertSame(
			self::PINNED_DB_VERSION,
			$m2[1],
			'baseline.md records a different database version than the pinned dbm image tag'
		);
	}

	/**
	 * Developer Notes: "Record the pin alongside the version pair
	 * documented in Story S1.1"
	 *
	 * Static proxy: the Pinned Versions table in baseline.md carries the
	 * database server row in the same table as the Elgg core and PHP
	 * runtime rows (the Story S1.1 pair), and the database-pin paragraph
	 * explicitly cites the story step.
	 */
	public function testDatabasePinRecordedAlongsideCoreRuntimePair()
	{
		$baselineFile = $this->docsBaselineFile();
		$this->assertFileExists($baselineFile);
		$contents = file_get_contents($baselineFile);

		// Same Pinned Versions table carries all three rows: the Story
		// S1.1 core/runtime pair plus the database server pin
		$this->assertSame(
			1,
			preg_match('/^## Pinned Versions\s*$(.*?)^## /ms', $contents, $m),
			'baseline.md has no Pinned Versions section'
		);
		foreach (array('Elgg core', 'PHP runtime', 'Database server') as $row) {
			$this->assertSame(
				1,
				preg_match_all('/^\|\s*' . $row . '\s*\|/m', $m[1]),
				"baseline.md Pinned Versions table must contain exactly one '$row' row alongside the Story S1.1 pair"
			);
		}

		// The database-pin paragraph explicitly cites Story S1.2 Step 5
		// (the story context for the pin)
		$this->assertSame(
			1,
			preg_match('/database server pin \(Story S1\.2 Step 5\)/', $contents),
			'baseline.md does not cite Story S1.2 Step 5 for the database server pin'
		);
	}
}
