<?php

class ContainerLogRoutingTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Story S1.8 Step 5 guard: each container service (application,
	 * database, scheduled-task runner) routes its output to stdout
	 * and/or dedicated log files, logs are observable in both the
	 * development and production environments, and application-written
	 * log files land on persistent storage so they survive restarts.
	 *
	 * Observability mechanism per service:
	 * - app: the base image symlinks Apache's access/error logs to
	 *   /dev/stdout and /dev/stderr, and the Compose default logging
	 *   driver captures them for `docker compose logs app`; the Elgg
	 *   file log (ELGG_LOG_FILE, Step 1) is the dedicated file sink.
	 * - dbm: the MariaDB image logs to the console; the default driver
	 *   captures it for `docker compose logs dbm`.
	 * - cron: the entrypoint runs crond in the foreground with debug
	 *   logging on stderr (`docker compose logs cron`); job output
	 *   appends to the dedicated /var/log/cron/app-cron.log on the
	 *   fs-cronlog volume (persistence and rotation:
	 *   CronLogRotationTest).
	 */

	private function repoDir()
	{
		// src/tests/<this file> -> repository root
		return dirname(__DIR__, 2);
	}

	/** Comment-stripped file contents (prose may say anything). */
	private function configWithoutComments($relativePath)
	{
		$path = $this->repoDir() . '/' . $relativePath;
		$this->assertFileExists($path, "$relativePath missing");
		$contents = preg_replace('/^\s*#.*$/m', '', file_get_contents($path));
		$this->assertNotFalse($contents, "$relativePath is not readable");
		return $contents;
	}

	private function composeConfig()
	{
		return $this->configWithoutComments('compose.yml');
	}

	private function composeDevConfig()
	{
		return $this->configWithoutComments('compose.dev.yml');
	}

	private function appDockerfileContents()
	{
		return $this->configWithoutComments('docker/app/Dockerfile');
	}

	private function envTemplateContents()
	{
		$path = $this->repoDir() . '/.env.example';
		$this->assertFileExists($path, '.env.example missing at repository root');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, '.env.example is not readable');
		return $contents;
	}

	/**
	 * Must Support: "Each container service (application, database,
	 * scheduled-task runner) routes its output to stdout and/or
	 * dedicated log files"
	 *
	 * The Compose default logging driver captures each service's
	 * stdout/stderr for the container runtime's log view — but only if
	 * no service overrides the driver or disables logging. Neither
	 * compose file may carry a `logging:` key.
	 */
	public function testNoComposeServiceOverridesTheDefaultLogDriver()
	{
		$this->assertSame(
			0,
			preg_match('/^\s*logging:\s*$/m', $this->composeConfig()),
			'compose.yml must not override the default logging driver — stdout/stderr capture is what makes every service observable via `docker compose logs`'
		);
		$this->assertSame(
			0,
			preg_match('/^\s*logging:\s*$/m', $this->composeDevConfig()),
			'compose.dev.yml must not override the default logging driver'
		);
	}

	/**
	 * Must Support: "Each container service … routes its output to
	 * stdout and/or dedicated log files" (cron half)
	 *
	 * The cron entrypoint must run crond in the foreground with its
	 * debug log level on stderr, so scheduled execution is observable
	 * through `docker compose logs cron`.
	 */
	public function testCronDaemonRunsInForegroundOnStderr()
	{
		$this->assertSame(
			1,
			preg_match('/^        crond -f -d 8$/m', $this->composeConfig()),
			'The cron entrypoint must run crond in the foreground (-f) with debug logging (-d 8) on stderr so `docker compose logs cron` shows scheduled execution'
		);
	}

	/**
	 * Must Support: "Application-written log files land on persistent
	 * storage so they survive restarts"
	 *
	 * The dedicated Elgg log file destination (ELGG_LOG_FILE, Step 1)
	 * must be documented to live on the fs-dataroot persistent volume —
	 * .env.example is the committed documentation of that requirement.
	 * (The error_log wiring itself is guarded by LoggingEnvironmentTest;
	 * the cron log's persistence by
	 * CronLogRotationTest::testLogDirectoryPersistsOnANamedVolume.)
	 */
	public function testElggLogFileDestinationIsDocumentedPersistent()
	{
		$lines = preg_split('/\r?\n/', $this->envTemplateContents());

		$definitionIndex = null;
		foreach ($lines as $i => $line) {
			if (preg_match('/^ELGG_LOG_FILE=/', $line)) {
				$definitionIndex = $i;
				break;
			}
		}
		$this->assertNotNull(
			$definitionIndex,
			'.env.example must define ELGG_LOG_FILE'
		);

		// The documentation block naming the variable must state the
		// persistent-volume requirement (within the preceding 8 lines)
		$start = max(0, $definitionIndex - 8);
		$preceding = implode("\n", array_slice($lines, $start, $definitionIndex - $start));
		$this->assertSame(
			1,
			preg_match('/fs-dataroot/', $preceding),
			'.env.example must document that ELGG_LOG_FILE must live on the persistent fs-dataroot volume so logs survive restarts'
		);
	}

	/**
	 * Must Support: "Each container service … routes its output to
	 * stdout …" (app half)
	 *
	 * The base image symlinks Apache's logs to /dev/stdout and
	 * /dev/stderr; the app image must not break that route — no
	 * /dev/std* manipulation and no Apache logging reconfiguration that
	 * would send web-server logs to regular files lost on recreation.
	 */
	public function testAppImageLeavesTheBaseImageStdoutLogRouteUntouched()
	{
		$dockerfile = $this->appDockerfileContents();

		$this->assertSame(
			0,
			preg_match('/dev\/stdout|dev\/stderr/', $dockerfile),
			'The app Dockerfile must not touch /dev/stdout or /dev/stderr — the base image symlinks Apache\'s logs there for `docker compose logs app`'
		);
		$this->assertSame(
			0,
			preg_match('/apache2/i', $dockerfile),
			'The app Dockerfile must not reconfigure Apache logging — the base image\'s stdout/stderr symlinks are the sanctioned stdout route'
		);
	}

	/**
	 * Must Support: "Logs are observable in both the development and
	 * production environments"
	 *
	 * compose.dev.yml must not change how any service logs: no
	 * entrypoint/command override (which could drop crond's stderr
	 * flags or bypass the base image's stdout symlinks). Bind mounts
	 * only — the dev shape stays routing-identical to production.
	 */
	public function testDevOverlayDoesNotDivergeLogRouting()
	{
		$this->assertSame(
			0,
			preg_match('/^    (entrypoint|command):/m', $this->composeDevConfig()),
			'compose.dev.yml must not override any service entrypoint/command — dev log routing must stay identical to the production shape'
		);
	}
}
