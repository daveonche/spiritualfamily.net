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
		$crontabFile = $this->repoDir() . '/crontab';
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
					. '\s+http://app:80/cron/' . $interval . '\?__elgg_mac=\{\{' . $token . '_PLACEHOLDER\}\}\s*$#m',
					$crontab
				),
				"crontab does not schedule the '$interval' interval as a wget against the app's cron endpoint with placeholder host and MAC token"
			);
		}

		// No scheduled commands beyond the nine intervals
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

		// The committed crontab is mounted read-only into the service
		$this->assertSame(
			1,
			preg_match('/- \.\/crontab:\/tmp\/app-cron:ro/', $cronBlock),
			'compose.yml cron service does not mount the committed crontab read-only'
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
}
