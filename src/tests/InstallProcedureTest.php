<?php

/**
 * Story S1.3 Step 3 — reproducible Elgg 2.3 install procedure tests.
 *
 * Elgg 2.3 provides no CLI installer, so the committed procedure is the
 * pre-flight script plus the documented web installer steps. Verifies
 * the committed pre-flight script checks everything the web installer
 * depends on (environment, database, directories, pinned core) and that
 * the procedure documentation wires the script in before the manual
 * web installer steps.
 */
class InstallProcedureTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var string|null Cached contents of the pre-flight script
	 */
	private $scriptContents;

	/**
	 * @var string|null Cached contents of the procedure document
	 */
	private $procedureDocContents;

	private function srcDir()
	{
		return dirname(__DIR__);
	}

	private function repoDir()
	{
		return dirname($this->srcDir());
	}

	private function scriptPath()
	{
		return $this->srcDir() . '/bin/install_preflight.sh';
	}

	private function scriptContents()
	{
		if ($this->scriptContents === null) {
			$this->scriptContents = file_get_contents($this->scriptPath());
		}

		return $this->scriptContents;
	}

	private function procedureDoc()
	{
		return $this->repoDir() . '/docs/baseline/fresh_checkout.md';
	}

	private function procedureDocContents()
	{
		if ($this->procedureDocContents === null) {
			$this->procedureDocContents = file_get_contents($this->procedureDoc());
		}

		return $this->procedureDocContents;
	}

	/**
	 * Must Support: "The install is reproducible: committed
	 * CLI/provisioning commands preferred" — the committed pre-flight
	 * script must exist and follow the repository's bash conventions
	 * (plain shebang, strict mode baseline).
	 */
	public function testPreflightScriptIsCommittedWithStrictMode()
	{
		$this->assertFileExists($this->scriptPath());
		$script = $this->scriptContents();

		$this->assertContains('#!/usr/bin/env bash', $script);
		$this->assertContains('set -euo pipefail', $script);
	}

	/**
	 * Must Support: the installer runs against the MySQL service with
	 * values from the environment — the script must verify every
	 * environment variable the settings surface and Compose stack
	 * require before the web installer runs.
	 */
	public function testPreflightScriptChecksRequiredEnvironmentVariables()
	{
		$script = $this->scriptContents();

		$required = [
			'MYSQL_USER',
			'MYSQL_PASSWORD',
			'MYSQL_DATABASE',
			'MYSQL_HOST',
			'ELGG_DB_PREFIX',
			'ELGG_TIMEZONE',
			'WWW_DATA',
			'WWW_CACHE',
		];
		foreach ($required as $var) {
			$this->assertContains($var, $script);
		}
	}

	/**
	 * Must Support: "The pinned Elgg 2.3 installs against the MySQL
	 * service and the schema installs cleanly" — the script must prove
	 * the database is reachable and fresh (no Elgg tables yet) so the
	 * schema install starts from a clean slate.
	 */
	public function testPreflightScriptChecksDatabaseReachabilityAndFreshness()
	{
		$script = $this->scriptContents();

		$this->assertContains('PDO', $script);
		$this->assertContains('SHOW TABLES', $script);
	}

	/**
	 * Must Support: the data directory is ready for the installer — the
	 * script must verify the data and cache directories are writable by
	 * the web SAPI user before the install runs.
	 */
	public function testPreflightScriptChecksDataAndCacheDirectoryWritability()
	{
		$script = $this->scriptContents();

		$this->assertContains('check_writable_dir "${WWW_DATA}"', $script);
		$this->assertContains('check_writable_dir "${WWW_CACHE}"', $script);
		$this->assertContains('www-data', $script);
	}

	/**
	 * Developer note: "Confirm the pinned Elgg 2.3 + PHP pair ... before
	 * installing" — the script must verify the vendored core matches the
	 * pinned version recorded in the baseline documentation.
	 */
	public function testPreflightScriptVerifiesPinnedCoreVersion()
	{
		$script = $this->scriptContents();

		$this->assertContains("PINNED_ELGG_VERSION='2.3.17'", $script);
	}

	/**
	 * Must Support: "documented manual steps acceptable where 2.3-era
	 * tooling requires them" — the procedure documentation must wire the
	 * committed pre-flight script in before the web installer steps and
	 * record why the web installer remains manual.
	 */
	public function testProcedureDocRunsPreflightBeforeWebInstaller()
	{
		$doc = $this->procedureDocContents();

		$this->assertContains('install_preflight.sh', $doc);
		$this->assertContains(
			'docker compose exec app /var/www/html/bin/install_preflight.sh',
			$doc
		);
		$this->assertContains('no CLI installer', $doc);
	}
}
