<?php

/**
 * Step 3 tests for Story S1.2 — single-definition orchestration of web,
 * database, and scheduled-task services.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposeOrchestrationTest extends \PHPUnit_Framework_TestCase
{
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
	 * Must Support: "One committed orchestration definition bringing up the
	 * web application, the database, and the scheduled-task service together"
	 */
	public function testSingleDefinitionBringsUpAppDatabaseAndCronTogether()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// All three services are defined in the one committed file
		foreach (array('app', 'dbm', 'cron') as $service) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$/m', $config),
				"compose.yml does not define the '$service' service"
			);
		}

		// Startup ordering: app waits for dbm to be really ready
		// (long-syntax depends_on with the health condition; dbm nests at
		// 6 spaces under the 4-space depends_on key)
		$this->assertSame(
			1,
			preg_match('/^  app:.*?^    depends_on:.*?^      dbm:.*?condition:\s*service_healthy/ms', $config),
			'compose.yml app service does not depend on dbm with condition service_healthy'
		);

		// cron starts alongside the others, after the app it targets
		$this->assertSame(
			1,
			preg_match('/^  cron:.*?^    depends_on:\s*$(?:\s+-\s+app\s*$)/ms', $config),
			'compose.yml cron service does not depend on the app service'
		);
	}

	/**
	 * Must Support: "The scheduled-task service running alongside the others
	 * on its configured schedule"
	 *
	 * Developer Notes: "Keep schedules and commands in committed configuration"
	 */
	public function testCronScheduleAndCommandsLiveInCommittedConfiguration()
	{
		// --- Schedules and commands in the committed crontab ---
		$crontabFile = $this->repoDir() . '/docker/cron/crontab';
		$this->assertFileExists($crontabFile);
		$crontab = file_get_contents($crontabFile);

		// All nine Elgg cron intervals, each a wget against the app's cron
		// endpoint with the site host entering only via {{HOST_PLACEHOLDER}}
		// and the MAC token entering only via its own placeholder
		$intervals = array(
			'minute' => 'MINUTE',
			'fiveminute' => 'FIVEMINUTE',
			'fifteenmin' => 'FIFTEENMIN',
			'halfhour' => 'HALFHOUR',
			'hourly' => 'HOURLY',
			'daily' => 'DAILY',
			'weekly' => 'WEEKLY',
			'monthly' => 'MONTHLY',
			'yearly' => 'YEARLY',
		);
		foreach ($intervals as $interval => $token) {
			$this->assertSame(
				1,
				preg_match(
					'#^(\S+\s+){5}wget\s+-qO-\s+--header "Host: \{\{HOST_PLACEHOLDER\}\}"'
					. '\s+http://app:80/cron/' . $interval . '\?__elgg_mac=\{\{' . $token . '_PLACEHOLDER\}\}'
					. ' >> /var/log/cron/app-cron\.log 2>&1$#m',
					$crontab
				),
				"crontab does not schedule the '$interval' interval as a wget against the app's cron endpoint with placeholder host and MAC token, appending to the scheduled-task output log"
			);
		}

		// No scheduled commands beyond the nine intervals plus the Step 5
		// daily log-rotation entry (which invokes no wget — guarded by
		// CronLogRotationTest)
		$this->assertSame(
			9,
			preg_match_all('/wget/m', $crontab),
			'crontab must schedule exactly the nine Elgg cron intervals'
		);

		// --- The cron service runs the committed schedule ---
		$config = $this->composeConfig();
		$this->assertSame(
			1,
			preg_match('/^  cron:$(.*?)^networks:/ms', $config, $cronBlock),
			'compose.yml has no recognizable cron service block'
		);
		$cronBlock = $cronBlock[1];

		// The committed crontab is baked into the cron image (Story S1.6:
		// the production definition carries no repo bind mounts) at the
		// entrypoint's spool source path
		$cronDockerfile = $this->repoDir() . '/docker/cron/Dockerfile';
		$this->assertFileExists($cronDockerfile);
		$this->assertSame(
			1,
			preg_match('/^COPY\s+docker\/cron\/crontab\s+\/tmp\/app-cron\s*$/m', file_get_contents($cronDockerfile)),
			'docker/cron/Dockerfile must bake the committed crontab at the entrypoint source path /tmp/app-cron'
		);

		// The committed entrypoint replaces every crontab placeholder from
		// the environment (token values themselves are Step 6 scope)
		$placeholders = array(
			'HOST', 'MINUTE', 'FIVEMINUTE', 'FIFTEENMIN', 'HALFHOUR',
			'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY',
		);
		foreach ($placeholders as $placeholder) {
			$this->assertSame(
				1,
				preg_match('/s\|\{\{' . $placeholder . '_PLACEHOLDER\}\}\|/', $cronBlock),
				'compose.yml cron entrypoint does not replace the {{' . $placeholder . '_PLACEHOLDER}} token'
			);
		}

		// crond runs in the foreground so execution is observable in logs
		$this->assertSame(
			1,
			preg_match('/crond\s+-f\b/', $cronBlock),
			'compose.yml cron service does not run crond in the foreground'
		);
	}

	/**
	 * Must Support: "All services running in one shared, reproducible local
	 * environment"
	 */
	public function testAllServicesShareOneNetworkedReproducibleEnvironment()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// Each service block, bounded by the next service definition (the
		// cron block ends at the top-level networks key)
		$boundaries = array(
			'app' => '^  dbm:',
			'dbm' => '^  cron:',
			'cron' => '^networks:',
		);
		$blocks = array();
		foreach ($boundaries as $service => $boundary) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$(.*?)' . $boundary . '/ms', $config, $m),
				"compose.yml has no recognizable '$service' service block"
			);
			$blocks[$service] = $m[1];

			// Every service attaches to the one shared appnet network
			$this->assertSame(
				1,
				preg_match('/^    networks:\s*$(?:\s+-\s+appnet\s*$)/ms', $m[1]),
				"compose.yml '$service' service does not attach to the shared appnet network"
			);
		}

		// The shared network is defined once at the top level
		$this->assertSame(
			1,
			preg_match('/^networks:\s*$(?:\s+appnet:\s*$)/ms', $config),
			'compose.yml does not define the shared appnet network at the top level'
		);

		// Explicit, non-floating image references — the reproducibility
		// half of this Must Support item. The app reference is explicitly
		// tagged via its version interpolation + -composer2 suffix (the
		// exact default is pinned in Step 1's ComposeImageBuildTest); dbm
		// keeps its pinned upstream tag (exact value + baseline recording
		// asserted in ComposeDatabasePinTest); cron is the locally built
		// image with the crontab baked in (Story S1.6); pma's pin is
		// asserted against compose.dev.yml below.
		$this->assertSame(
			1,
			preg_match('/^    image:\s*"sfn\/elgg_webserver:\$\{PHP_VERSION:-[^}]+\}-composer2"/m', $blocks['app']),
			'compose.yml app service image is not an explicitly tagged reference'
		);

		// dbm: pinned upstream image
		$this->assertSame(
			1,
			preg_match('/^    image:\s*mariadb:([^\s"]+)$/m', $blocks['dbm'], $m),
			"compose.yml 'dbm' service image is not pinned to an explicit tag"
		);
		$this->assertNotSame(
			'latest',
			$m[1],
			"compose.yml 'dbm' service floats the \"latest\" tag"
		);

		// cron: locally built image (crontab baked — Story S1.6), tagged
		// with the pinned alpine base version; the base pin itself is
		// asserted on docker/cron/Dockerfile below
		$this->assertSame(
			1,
			preg_match('/^    image:\s*"sfn\/elgg_cron:([^\s"]+)"/m', $blocks['cron'], $m),
			"compose.yml 'cron' service image is not the pinned locally built tag"
		);
		$this->assertNotSame(
			'latest',
			$m[1],
			"compose.yml 'cron' service floats the \"latest\" tag"
		);
		$cronDockerfile = $this->repoDir() . '/docker/cron/Dockerfile';
		$this->assertFileExists($cronDockerfile);
		$this->assertSame(
			1,
			preg_match('/^FROM\s+alpine:3\.23\.3\s*$/m', file_get_contents($cronDockerfile)),
			"docker/cron/Dockerfile must pin its alpine base image"
		);

		// The dev-only pma service (compose.dev.yml, Story S1.6) keeps its
		// pinned image and appnet attachment
		$devFile = $this->repoDir() . '/compose.dev.yml';
		$this->assertFileExists($devFile);
		$devConfig = preg_replace('/^\s*#.*$/m', '', file_get_contents($devFile));
		$this->assertSame(
			1,
			preg_match('/^  pma:\s*$(.*)$/ms', $devConfig, $m),
			'compose.dev.yml has no recognizable pma service block'
		);
		$this->assertSame(
			1,
			preg_match('/^    image:\s*phpmyadmin\/phpmyadmin:([^\s"]+)$/m', $m[1], $m2),
			"compose.dev.yml 'pma' service image is not pinned to an explicit tag"
		);
		$this->assertNotSame(
			'latest',
			$m2[1],
			"compose.dev.yml 'pma' service floats the \"latest\" tag"
		);
		$this->assertSame(
			1,
			preg_match('/^    networks:\s*$(?:\s+-\s+appnet\s*$)/ms', $m[1]),
			"compose.dev.yml 'pma' service does not attach to the shared appnet network"
		);
	}
}
