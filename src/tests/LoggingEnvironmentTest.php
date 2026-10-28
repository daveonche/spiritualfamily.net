<?php

/**
 * Step 1 and Step 2 tests for Story S1.8 — per-environment application
 * logging configuration and the error/exception handler baseline.
 *
 * Verifies that log verbosity and destination are configurable per
 * environment through the committed environment-variable mechanism
 * (never hard-coded), and that the committed template default surfaces
 * deprecation notices — the early-warning signal for the next
 * major-version upgrade.
 *
 * Mechanism confirmed against the vendored Elgg 2.3.17 core:
 * - Elgg\Logger accepts OFF | INFO | NOTICE | WARNING | ERROR (built-in
 *   default ERROR); BootService applies $CONFIG->debug only when
 *   non-empty.
 * - DeprecationService::sendNotice() logs via Logger::warn() (severity
 *   300) — dropped at the core ERROR default, surfaced at WARNING.
 * - Every log sink writes through PHP's error_log() (exception handler
 *   elgglib.php:877, PHP error handler :980-1002, default dump path
 *   :1580) — the destination is the error_log ini setting.
 * - The sanctioned handlers are registered by core itself:
 *   Elgg\BootService calls set_error_handler('_elgg_php_error_handler')
 *   and set_exception_handler('_elgg_php_exception_handler') during
 *   boot (BootService.php:36-37; ElggInstaller.php:91-92 for the
 *   installer path) — settings.php configures the PHP runtime around
 *   them and registers no handlers of its own.
 * - The PHP error handler gates on error_reporting() and logs at
 *   WARNING severity (elgglib.php:993) — E_ALL feeds it everything;
 *   ELGG_DEBUG_LEVEL keeps severity filtering per environment.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class LoggingEnvironmentTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function srcDir()
	{
		return dirname(__DIR__);
	}

	private function settingsFile()
	{
		return $this->srcDir() . '/elgg-config/settings.php';
	}

	private function envTemplateFile()
	{
		return $this->repoDir() . '/.env.example';
	}

	private function settingsContents()
	{
		return file_get_contents($this->settingsFile());
	}

	private function templateContents()
	{
		return file_get_contents($this->envTemplateFile());
	}

	/**
	 * Must Support: "The application's log verbosity ... configurable per
	 * environment ... without code changes" + "supplied through the
	 * committed environment-variable mechanism, never hard-coded"
	 */
	public function testSettingsPhpWiresDebugLevelFromEnvironment()
	{
		$this->assertFileExists($this->settingsFile());
		$contents = $this->settingsContents();

		// The verbosity knob is read from the environment
		$this->assertSame(
			1,
			preg_match('/getenv\("ELGG_DEBUG_LEVEL"\)/', $contents),
			'settings.php must read the log verbosity from getenv("ELGG_DEBUG_LEVEL")'
		);

		// Applied to $CONFIG->debug exactly once
		$this->assertSame(
			1,
			preg_match_all('/^\s*\$CONFIG->debug\s*=/m', $contents),
			'settings.php must assign $CONFIG->debug exactly once'
		);

		// Only the five core logger level names are accepted — the
		// validation list guards against a typo silently disabling logging
		$this->assertSame(
			1,
			preg_match(
				"/in_array\(\s*\\\$elggDebugLevel\s*,\s*array\(\s*'(?:OFF|INFO|NOTICE|WARNING|ERROR)'(?:\s*,\s*'(?:OFF|INFO|NOTICE|WARNING|ERROR)'){4}\s*,?\s*\)\s*,\s*true\s*\)/",
				$contents
			),
			'settings.php must validate ELGG_DEBUG_LEVEL against the five Elgg 2.3 logger level names'
		);

		// No hard-coded per-environment default: $CONFIG->debug must never
		// be assigned a string literal directly
		$this->assertSame(
			0,
			preg_match('/^\s*\$CONFIG->debug\s*=\s*[\'"][A-Z]+[\'"]\s*;/m', $contents),
			'settings.php must not hard-code a log level — the value flows from the environment'
		);
	}

	/**
	 * Must Support: "The application's log ... destinations are
	 * configurable per environment ... without code changes"
	 */
	public function testSettingsPhpWiresLogFileDestinationFromEnvironment()
	{
		$this->assertFileExists($this->settingsFile());
		$contents = $this->settingsContents();

		// The destination knob is read from the environment
		$this->assertSame(
			1,
			preg_match('/getenv\("ELGG_LOG_FILE"\)/', $contents),
			'settings.php must read the log destination from getenv("ELGG_LOG_FILE")'
		);

		// Elgg 2.3 writes every log event through PHP's error_log() — the
		// destination is the error_log ini setting
		$this->assertSame(
			1,
			preg_match("/ini_set\(\s*['\"]error_log['\"]\s*,\s*\\\$elggLogFile\s*\)\s*;/", $contents),
			'settings.php must route the log destination through ini_set("error_log", ...)'
		);

		// No hard-coded per-environment path
		$this->assertSame(
			0,
			preg_match("/ini_set\(\s*['\"]error_log['\"]\s*,\s*['\"][^'\"]+['\"]\s*\)/", $contents),
			'settings.php must not hard-code a log file path — the value flows from the environment'
		);
	}

	/**
	 * Must Support: "Deprecation notices are surfaced visibly in the log
	 * so upcoming breakage at the next platform version is signaled"
	 *
	 * DeprecationService::sendNotice() logs at WARNING severity: at the
	 * core's built-in ERROR default deprecation notices are silently
	 * dropped. The committed template default must therefore be WARNING
	 * or lower (but not OFF) so deprecations surface out of the box.
	 */
	public function testEnvTemplateDefaultSurfacesDeprecationNotices()
	{
		$this->assertFileExists($this->envTemplateFile());
		$template = $this->templateContents();

		$this->assertSame(
			1,
			preg_match('/^ELGG_DEBUG_LEVEL=(WARNING|NOTICE|INFO)$/m', $template),
			'.env.example must default ELGG_DEBUG_LEVEL to WARNING (or lower) so deprecation notices surface — the core ERROR default drops them'
		);
	}

	/**
	 * Must Support (plan): ".env.example needs ELGG_DEBUG_LEVEL and
	 * ELGG_LOG_FILE entries with placeholder values and per-variable
	 * documentation comments"
	 */
	public function testEnvTemplateDocumentsBothLoggingVariables()
	{
		$this->assertFileExists($this->envTemplateFile());
		$lines = preg_split('/\r?\n/', $this->templateContents());

		foreach (array('ELGG_DEBUG_LEVEL', 'ELGG_LOG_FILE') as $var) {
			$definitionIndex = null;
			foreach ($lines as $i => $line) {
				if (preg_match('/^' . $var . '=/', $line)) {
					$definitionIndex = $i;
					break;
				}
			}
			$this->assertNotNull(
				$definitionIndex,
				".env.example must define $var"
			);

			// A comment block naming the variable must precede the
			// definition (within the preceding 8 lines)
			$start = max(0, $definitionIndex - 8);
			$preceding = implode("\n", array_slice($lines, $start, $definitionIndex - $start));
			$this->assertSame(
				1,
				preg_match('/^#.*' . $var . '/m', $preceding),
				".env.example must document $var's purpose in a comment alongside its definition"
			);
		}
	}

	/**
	 * Must Support: "Uncaught errors and exceptions are captured into the
	 * application log through handlers registered via the platform's
	 * sanctioned mechanism"
	 *
	 * The sanctioned mechanism is Elgg core's own: Elgg\BootService
	 * registers _elgg_php_error_handler / _elgg_php_exception_handler
	 * during boot (vendored 2.3.17 BootService.php:36-37). settings.php
	 * must not register ad-hoc handlers of its own — it must only feed
	 * the core handlers a complete PHP error baseline.
	 */
	public function testSettingsPhpReliesOnCoreSanctionedHandlers()
	{
		$this->assertFileExists($this->settingsFile());
		$contents = $this->settingsContents();

		// settings.php registers no handlers of its own — the core
		// BootService registration is the sanctioned mechanism
		$this->assertSame(
			0,
			preg_match('/set_(?:error|exception)_handler\s*\(/', $contents),
			'settings.php must not register ad-hoc error/exception handlers — Elgg\BootService registers the sanctioned core handlers'
		);

		// The PHP runtime feeds the core handlers every diagnostic,
		// including deprecation notices
		$this->assertSame(
			1,
			preg_match('/^error_reporting\(E_ALL\);\s*$/m', $contents),
			'settings.php must set error_reporting(E_ALL) so every diagnostic — including deprecation notices — reaches the core handler'
		);
	}

	/**
	 * Must Support: "Handler output follows the per-environment logging
	 * configuration from Step 1" + manual verification "Confirm
	 * production logging does not expose more internal detail than
	 * intended"
	 *
	 * display_errors must be off in the committed baseline — the log is
	 * the only observable channel in both environment shapes — and
	 * log_errors on, so PHP-level errors outside the core handlers
	 * (early bootstrap, fatal shutdowns) still land in the configured
	 * log (whose destination is the Step 1 ELGG_LOG_FILE wiring).
	 */
	public function testSettingsPhpRoutesHandlerOutputToTheLogOnly()
	{
		$this->assertFileExists($this->settingsFile());
		$contents = $this->settingsContents();

		$this->assertSame(
			1,
			preg_match("/ini_set\(\s*['\"]display_errors['\"]\s*,\s*['\"]0['\"]\s*\)\s*;/", $contents),
			'settings.php must disable display_errors — internal detail is never exposed in responses in either environment shape'
		);

		$this->assertSame(
			1,
			preg_match("/ini_set\(\s*['\"]log_errors['\"]\s*,\s*['\"]1['\"]\s*\)\s*;/", $contents),
			'settings.php must enable log_errors — PHP-level errors outside the core handlers still land in the configured log'
		);
	}
}
