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

	/** Committed environment template (repo-root .env.example). */
	private function envTemplateFile()
	{
		return $this->repoDir() . '/.env.example';
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

		// The data and cache directories are likewise environment-injected,
		// with the trailing slash Elgg expects appended here so the .env
		// values stay clean mount-target paths
		$slashSettings = array(
			'dataroot'  => 'WWW_DATA',
			'cacheroot' => 'WWW_CACHE',
		);
		foreach ($slashSettings as $key => $envVar) {
			$this->assertSame(
				1,
				preg_match_all('/^\$CONFIG->' . $key . '\s*=/m', $contents),
				"settings.php must assign \$CONFIG->$key exactly once"
			);
			$this->assertSame(
				1,
				preg_match(
					'/^\$CONFIG->' . $key . '\s*=\s*getenv\("' . $envVar . '"\)\s*\.\s*"\/";\s*$/m',
					$contents
				),
				"settings.php must assign \$CONFIG->$key via getenv(\"$envVar\") with a trailing slash"
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

		// The dev-only override file — pma lives there since Story S1.6;
		// mounted read-only into the app container for test verification
		$devFile = $this->repoDir() . '/compose.dev.yml';
		$this->assertFileExists($devFile);
		$devConfig = preg_replace('/^\s*#.*$/m', '', file_get_contents($devFile));

		// Service => regex bounding its block by the next service definition
		// (pma is verified against compose.dev.yml below)
		$serviceBlocks = array(
			'app' => '/^  app:\s*$(.*?)^  dbm:/ms',
			'dbm' => '/^  dbm:\s*$(.*?)^  cron:/ms',
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

		// Credential variables never appear in the definitions — they
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
				preg_match_all('/\b' . $var . '\b/', $config . "\n" . $devConfig),
				"the Compose definitions must not reference $var — it flows through .env only"
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
			'CRON_CONTAINER_NAME',
			'APP_HOST',
			'APP_HOST_PORT',
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

		// Volume targets must be literal absolute paths: Coolify's compose
		// parser validates storage paths and rejects ${VAR} interpolation
		// in the target position
		$this->assertSame(
			0,
			preg_match('/^\s+-\s+\S+:\$\{/m', $blocks['app'] . "\n" . $blocks['dbm']),
			'compose.yml volume targets must be literal paths — Coolify rejects interpolated volume targets'
		);

		// The dev-only pma service follows the same injection rules:
		// settings via env_file: .env, values via ${VAR} interpolation
		$this->assertSame(
			1,
			preg_match('/^  pma:\s*$(.*)$/ms', $devConfig, $m),
			'compose.dev.yml has no recognizable pma service block'
		);
		$this->assertSame(
			1,
			preg_match('/^    env_file:\s*-\s+\.env\s*$/m', $m[1]),
			'compose.dev.yml service pma must receive settings via env_file: .env'
		);
		foreach (array('PMA_CONTAINER_NAME', 'PMA_HOST_PORT') as $var) {
			$this->assertGreaterThanOrEqual(
				1,
				preg_match_all('/\$\{' . $var . '[:}]/', $devConfig),
				"compose.dev.yml must reference $var via interpolation, not a committed literal"
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

	/**
	 * Must Support: "A documented template listing every required variable
	 * without real secret values"
	 *
	 * .env.example must exist, document the copy procedure, and carry only
	 * placeholder or documented non-secret default values — never real
	 * secrets. The four credential variables must be exactly "changeme".
	 * Developer Note "Real secrets never enter the repository": .gitignore
	 * must ignore .env while keeping the template itself committed (the
	 * repo-root .gitignore is mounted read-only into the app container
	 * alongside the other test-verification mounts).
	 */
	public function testEnvTemplateExistsWithPlaceholderValuesOnly()
	{
		$templateFile = $this->envTemplateFile();
		$this->assertFileExists($templateFile);
		$contents = file_get_contents($templateFile);

		// The header documents the copy procedure
		$this->assertSame(
			1,
			preg_match('/cp\s+\.env\.example\s+\.env/', $contents),
			'.env.example must document the copy procedure (cp .env.example .env)'
		);

		// Documented non-secret defaults (key => exact value); any other
		// non-changeme value must be allowlisted here first
		$documentedDefaults = array(
			'PHP_VERSION'   => '7.4',
			'APP_HOST_PORT' => '3000',
			'PMA_HOST_PORT' => '3001',
			'WWW_DATA'      => '/var/www/dataroot',
			'WWW_CACHE'     => '/var/www/cacheroot',
			'MYSQL_HOST'    => 'dbm',
			'PMA_HOST'      => 'dbm',
			'ELGG_TIMEZONE' => 'UTC',
			'MAIL_SMTP_HOST'=> 'host.docker.internal',
			'MAIL_SMTP_PORT'=> '1025',
			// Empty = plaintext development behaviour (MailHog); the wrapper
			// adds auth/TLS only when this is "on" (Story S1.6 Step 5)
			'MAIL_SMTP_AUTH'=> '',
		);

		// Credential variables must be exactly the changeme placeholder
		$credentialVars = array(
			'MYSQL_ROOT_PASSWORD',
			'MYSQL_DATABASE',
			'MYSQL_USER',
			'MYSQL_PASSWORD',
		);

		// Every active (non-comment) line must be KEY=VALUE with a safe
		// value: a changeme* placeholder or an exact documented default
		$activeLines = preg_grep('/^[A-Z][A-Z0-9_]*=/', preg_split('/\r?\n/', $contents));
		$this->assertNotEmpty($activeLines, '.env.example must define variables');

		$seenKeys = array();
		foreach ($activeLines as $line) {
			$this->assertSame(
				1,
				preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', trim($line), $m),
				".env.example line is not KEY=VALUE: $line"
			);
			$key = $m[1];
			$value = $m[2];

			// No duplicate definitions — each variable is listed once
			$this->assertArrayNotHasKey(
				$key,
				$seenKeys,
				".env.example defines $key more than once"
			);
			$seenKeys[$key] = true;

			if (in_array($key, $credentialVars, true)) {
				// Real secrets never enter the repository: credentials are
				// exactly the changeme placeholder
				$this->assertSame(
					'changeme',
					$value,
					".env.example credential $key must be exactly the changeme placeholder"
				);
			} elseif (isset($documentedDefaults[$key])) {
				$this->assertSame(
					$documentedDefaults[$key],
					$value,
					".env.example default for $key does not match the documented value"
				);
			} else {
				$this->assertSame(
					1,
					preg_match('/^changeme/', $value),
					".env.example value for $key is neither changeme* nor a documented default: $value"
				);
			}
		}

		// All four credential variables are present in the template
		foreach ($credentialVars as $var) {
			$this->assertArrayHasKey(
				$var,
				$seenKeys,
				".env.example must define the credential variable $var"
			);
		}

		// Developer Note "Real secrets never enter the repository":
		// .gitignore ignores .env and keeps the template committed
		$gitignoreFile = $this->repoDir() . '/.gitignore';
		$this->assertFileExists($gitignoreFile);
		$gitignore = file_get_contents($gitignoreFile);
		$this->assertSame(
			1,
			preg_match('/^\.env\s*$/m', $gitignore),
			'.gitignore must ignore .env'
		);
		$this->assertSame(
			1,
			preg_match('/^!\.env\.example\s*$/m', $gitignore),
			'.gitignore must keep .env.example committed (!.env.example)'
		);
	}

	/**
	 * Must Support: "A documented template listing every required variable"
	 *
	 * Developer Note: "Keep the template complete so a fresh environment
	 * needs no undocumented values."
	 *
	 * The required-variable set is derived dynamically from the stack
	 * itself — every ${VAR} reference in compose.yml plus every getenv()
	 * read in settings.php — and completed with the image-consumed
	 * env_file variables no committed file statically references (the
	 * MariaDB entrypoint's MYSQL_ROOT_PASSWORD, the phpMyAdmin image's
	 * PMA_HOST, and the msmtp-sendmail wrapper's MAIL_SMTP_AUTH/
	 * MAIL_SMTP_USER/MAIL_SMTP_PASSWORD — all documented in .env.example
	 * sections). Forward
	 * completeness: every required variable is an active KEY= line in
	 * .env.example. Reverse drift guard: every active .env.example key is
	 * required by the stack, so template entries the stack no longer uses
	 * are caught (optional variables such as COMPOSE_PROJECT_NAME stay
	 * commented out and are exempt).
	 */
	public function testEnvTemplateListsEveryRequiredVariable()
	{
		$templateFile = $this->envTemplateFile();
		$this->assertFileExists($templateFile);
		$template = file_get_contents($templateFile);

		// 1. Every ${VAR} reference in comment-stripped compose.yml
		//    (the cron entrypoint's $$NAME shell escapes carry no brace
		//    and are correctly not compose interpolation)
		$compose = $this->composeConfig();
		preg_match_all('/\$\{([A-Z][A-Z0-9_]*)/', $compose, $m);
		$required = $m[1];

		// 1b. Every ${VAR} reference in comment-stripped compose.dev.yml —
		//    the dev-only override (pma lives there since Story S1.6) is
		//    part of the stack the template must serve
		$devFile = $this->repoDir() . '/compose.dev.yml';
		$this->assertFileExists($devFile);
		$devConfig = preg_replace('/^\s*#.*$/m', '', file_get_contents($devFile));
		preg_match_all('/\$\{([A-Z][A-Z0-9_]*)/', $devConfig, $mDev);
		$required = array_merge($required, $mDev[1]);

		// 2. Every getenv("VAR") read in comment-stripped settings.php
		$settings = $this->stripComments(file_get_contents($this->settingsFile()));
		preg_match_all('/getenv\("([A-Z][A-Z0-9_]*)"\)/', $settings, $m2);
		$required = array_merge($required, $m2[1]);

		// 3. Image-consumed env_file variables not statically derivable
		//    from committed files
		$required = array_merge($required, array(
			'MYSQL_ROOT_PASSWORD',
			'PMA_HOST',
			// msmtp-sendmail wrapper (docker/app/msmtp-sendmail.sh), consumed
			// from the container environment via env_file: .env — Story S1.6
			// Step 5 production relay
			'MAIL_SMTP_AUTH',
			'MAIL_SMTP_USER',
			'MAIL_SMTP_PASSWORD',
		));

		$required = array_values(array_unique($required));

		// Active KEY= lines in the template (commented examples exempt)
		$templateKeys = array();
		foreach (preg_split('/\r?\n/', $template) as $line) {
			if (preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $m3)) {
				$templateKeys[] = $m3[1];
			}
		}

		// Sanity: a broken extraction regex or an emptied template would
		// otherwise let the set comparisons pass vacuously
		$this->assertNotEmpty($required, 'required-variable extraction found nothing');
		$this->assertNotEmpty($templateKeys, '.env.example has no active KEY= lines');

		// Forward completeness: every required variable is documented
		$missing = array_diff($required, $templateKeys);
		$this->assertSame(
			array(),
			$missing,
			'.env.example is missing required variables: ' . implode(', ', $missing)
		);

		// Reverse drift guard: every documented variable is required
		$unrequired = array_diff($templateKeys, $required);
		$this->assertSame(
			array(),
			$unrequired,
			'.env.example lists variables the stack does not require: ' . implode(', ', $unrequired)
		);
	}
}
