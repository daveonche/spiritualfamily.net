<?php

/**
 * Step 1 tests for Story S1.8 — per-environment application logging
 * configuration.
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
}
