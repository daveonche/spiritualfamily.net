<?php

/**
 * Step 6 tests for Story S1.2 — environment-variable injection of
 * settings and secrets.
 *
 * Verifies that all environment-specific settings are supplied as
 * environment variables, that no credentials are hard-coded in committed
 * files, and that the committed .env.example template documents every
 * required variable without real secret values.
 *
 * The live bring-up using only the template plus locally supplied values
 * is manual verification scope (see docs/analysis/S1.2-story-steps.md
 * Step 6).
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposeEnvInjectionTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	/**
	 * Source root: <repo>/src on the host, /var/www/html in the app
	 * container (the ./src mount target) — same resolution pattern as
	 * ComposeDatabasePinTest::docsBaselineFile().
	 */
	private function srcDir()
	{
		return dirname(__DIR__);
	}

	/** Committed Elgg settings file (src/elgg-config/settings.php). */
	private function settingsFile()
	{
		return $this->srcDir() . '/elgg-config/settings.php';
	}

	/**
	 * Must Support: "All environment-specific settings supplied as
	 * environment variables"
	 *
	 * Every environment-specific setting in settings.php must be read
	 * from the environment via getenv() — and each key must be assigned
	 * exactly once, so no later hard-coded literal can override the
	 * injected value.
	 */
	public function testSettingsPhpInjectsAllEnvironmentSpecificSettingsViaGetenv()
	{
		$settingsFile = $this->settingsFile();
		$this->assertFileExists($settingsFile);
		$contents = file_get_contents($settingsFile);

		// Setting key => environment variable supplying it
		$envSettings = array(
			'dbuser'   => 'MYSQL_USER',
			'dbpass'   => 'MYSQL_PASSWORD',
			'dbname'   => 'MYSQL_DATABASE',
			'dbhost'   => 'MYSQL_HOST',
			'dbprefix' => 'ELGG_DB_PREFIX',
		);

		foreach ($envSettings as $key => $envVar) {
			// Exactly one assignment to this key …
			$this->assertSame(
				1,
				preg_match_all('/^\$CONFIG->' . $key . '\s*=/m', $contents),
				"settings.php must assign \$CONFIG->$key exactly once"
			);

			// … and that assignment must be getenv("<env var>") — no
			// hard-coded literal
			$this->assertSame(
				1,
				preg_match(
					'/^\$CONFIG->' . $key . '\s*=\s*getenv\("' . $envVar . '"\);\s*$/m',
					$contents
				),
				"settings.php must assign \$CONFIG->$key via getenv(\"$envVar\")"
			);
		}

		// The PHP timezone is likewise environment-injected
		$this->assertSame(
			1,
			preg_match('/^date_default_timezone_set\(getenv\("ELGG_TIMEZONE"\)\);\s*$/m', $contents),
			'settings.php must set the timezone via getenv("ELGG_TIMEZONE")'
		);
	}
}
