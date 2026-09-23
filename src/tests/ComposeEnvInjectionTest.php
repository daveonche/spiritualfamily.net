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

	/** Committed orchestration definition (repo-root compose.yml). */
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
	 * Strip PHP block comments and whole-line # and // comments from any
	 * text file — prose may say anything; only live code/config lines
	 * are scanned.
	 */
	private function stripComments($contents)
	{
		$contents = preg_replace('%/\*.*?\*/%s', '', $contents);
		return preg_replace('/^\s*(?:#|\/\/).*$/m', '', $contents);
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

	/**
	 * Must Support: "All environment-specific settings supplied as
	 * environment variables"
	 *
	 * compose.yml side: every service that needs settings or secrets
	 * receives them via env_file: .env or ${VAR} interpolation — no
	 * credential values are hard-coded in the definition. The one
	 * literal exception is dbm's non-secret MYSQL_HOST: localhost
	 * override, which keeps its healthcheck probe on the unix socket
	 * (documented in compose.yml).
	 */
	public function testComposeInjectsSettingsViaEnvironmentVariables()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// Service => regex bounding its block by the next service definition
		$serviceBlocks = array(
			'app' => '/^  app:\s*$(.*?)^  dbm:/ms',
			'dbm' => '/^  dbm:\s*$(.*?)^  pma:/ms',
			'pma' => '/^  pma:\s*$(.*?)^  cron:/ms',
		);
		$blocks = array();
		foreach ($serviceBlocks as $svc => $pattern) {
			$this->assertSame(
				1,
				preg_match($pattern, $config, $m),
				"compose.yml has no recognizable '$svc' service block"
			);
			$blocks[$svc] = $m[1];

			// Settings and secrets reach this service via env_file: .env
			$this->assertSame(
				1,
				preg_match('/^    env_file:\s*-\s+\.env\s*$/m', $m[1]),
				"compose.yml service $svc must receive settings via env_file: .env"
			);
		}

		// Credential variables never appear in the definition — they
		// reach containers only through .env
		$credentialVars = array(
			'MYSQL_ROOT_PASSWORD',
			'MYSQL_PASSWORD',
			'MYSQL_USER',
			'MYSQL_DATABASE',
		);
		foreach ($credentialVars as $var) {
			$this->assertSame(
				0,
				preg_match_all('/\b' . $var . '\b/', $config),
				"compose.yml must not reference $var — it flows through .env only"
			);
		}

		// The single allowed literal environment reference: dbm's
		// non-secret MYSQL_HOST override (healthcheck probe stays on the
		// unix socket — documented in compose.yml)
		$this->assertSame(
			1,
			preg_match_all('/\bMYSQL_HOST\b/', $config),
			'compose.yml must reference MYSQL_HOST exactly once'
		);
		$this->assertSame(
			1,
			preg_match('/^\s+MYSQL_HOST\s*:\s*localhost\s*$/m', $blocks['dbm']),
			'compose.yml dbm must pin MYSQL_HOST to localhost (healthcheck probe on the unix socket)'
		);

		// Every environment-driven value is ${VAR}-interpolated, never a
		// committed literal
		$interpolatedVars = array(
			'PHP_VERSION',
			'APP_CONTAINER_NAME',
			'MYSQL_CONTAINER_NAME',
			'PMA_CONTAINER_NAME',
			'CRON_CONTAINER_NAME',
			'APP_HOST',
			'APP_HOST_PORT',
			'PMA_HOST_PORT',
			'WWW_ROOT',
			'WWW_DATA',
			'WWW_CACHE',
			'WWW_ASSET',
			'WWW_DOCS',
			'APP_CRON_MINUTE',
			'APP_CRON_FIVEMINUTE',
			'APP_CRON_FIFTEENMIN',
			'APP_CRON_HALFHOUR',
			'APP_CRON_HOURLY',
			'APP_CRON_DAILY',
			'APP_CRON_WEEKLY',
			'APP_CRON_MONTHLY',
			'APP_CRON_YEARLY',
		);
		foreach ($interpolatedVars as $var) {
			$this->assertGreaterThanOrEqual(
				1,
				preg_match_all('/\$\{' . $var . '[:}]/', $config),
				"compose.yml must reference $var via interpolation, not a committed literal"
			);
		}
	}

	/**
	 * Must Support: "No credentials hard-coded in committed files"
	 *
	 * Automates the manual verification "Search committed files for
	 * hard-coded credentials and confirm none exist": every
	 * credential-plausible key (password/passwd/pwd/pass/secret/api key/
	 * private key) in the project-authored, container-reachable files
	 * must carry a safe value — getenv(...), ${VAR} interpolation,
	 * {{PLACEHOLDER}} tokens, <angle-bracket> placeholders, the changeme
	 * placeholder convention, an empty value, or a numeric literal —
	 * never a real secret literal.
	 *
	 * Scope: src/elgg-config/settings.php, compose.yml, .env.example,
	 * crontab, docker/app/Dockerfile. The vendored Elgg core
	 * (src/vendor, git-ignored) is out of scope — thousands of unrelated
	 * 'password' strings; php/*.ini are not mounted under /var/www in the
	 * container and carry no credentials. Comments are stripped first:
	 * prose may say anything.
	 */
	public function testNoHardcodedCredentialsInCommittedFiles()
	{
		$files = array(
			'src/elgg-config/settings.php' => $this->settingsFile(),
			'compose.yml'                  => $this->composeFile(),
			'.env.example'                 => $this->repoDir() . '/.env.example',
			'crontab'                      => $this->repoDir() . '/crontab',
			'docker/app/Dockerfile'        => $this->repoDir() . '/docker/app/Dockerfile',
		);

		// Credential-plausible key followed by an assignment/separator
		$keyPattern = '/(?i)(?:password|passwd|pwd|pass|secret|api[_-]?key|apikey|private[_-]?key)\w*\s*[:=]/';

		// Same match with the value captured (everything after the key)
		$linePattern = '/(?i)^(.*?(?:password|passwd|pwd|pass|secret|api[_-]?key|apikey|private[_-]?key)\w*\s*[:=])(.*)$/';

		// Safe values: changeme*, getenv(...), ${VAR}, {{PLACEHOLDER}},
		// <placeholder>, empty/quoted-empty, numeric
		$safeValuePattern = '/(?i)(changeme|getenv\s*\(|^\$\{|^\{\{|^<|^\d+[\s;]*$|^["\']{0,2}\s*;?\s*$)/';

		foreach ($files as $label => $path) {
			$this->assertFileExists($path, "committed file $label is missing");
			$stripped = $this->stripComments(file_get_contents($path));

			foreach (preg_split('/\r?\n/', $stripped) as $line) {
				if (!preg_match($keyPattern, $line)) {
					continue;
				}
				preg_match($linePattern, $line, $m);
				$value = trim($m[2]);
				$this->assertSame(
					1,
					preg_match($safeValuePattern, $value),
					"hard-coded credential-like value in $label: $line"
				);
			}
		}
	}
}
