<?php

class CronSanctionedEntryPointTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Story S1.7 Step 1 guard: scheduled jobs must reach the Elgg core
	 * through the core's sanctioned entry point for cron-driven processing.
	 * For the pinned Elgg 2.3.17 core that is the core's HTTP cron endpoint
	 * (/cron/<interval>?__elgg_mac=<token>), triggered by the S1.2 wget
	 * crontab baked into the cron image.
	 */

	private function repoDir()
	{
		// src/tests/<this file> -> repository root
		return dirname(__DIR__, 2);
	}

	private function crontabFile()
	{
		return $this->repoDir() . '/docker/cron/crontab';
	}

	private function crontabContents()
	{
		$path = $this->crontabFile();
		$this->assertFileExists($path, 'Committed crontab missing at docker/cron/crontab');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Committed crontab is not readable');
		return $contents;
	}

	private function crontabLines()
	{
		return preg_split('/\r?\n/', $this->crontabContents());
	}

	private function cronDockerfileContents()
	{
		$path = $this->repoDir() . '/docker/cron/Dockerfile';
		$this->assertFileExists($path, 'Cron Dockerfile missing');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Cron Dockerfile is not readable');
		return $contents;
	}

	private function composeContents()
	{
		$path = $this->repoDir() . '/compose.yml';
		$this->assertFileExists($path, 'compose.yml missing at repository root');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'compose.yml is not readable');
		return $contents;
	}

	private function expectedSchedules()
	{
		// All recurring intervals supported by Elgg 2.3.17, at their defined
		// frequencies (standard cron five-field schedules)
		return array(
			'minute' => '* * * * *',
			'fiveminute' => '*/5 * * * *',
			'fifteenmin' => '*/15 * * * *',
			'halfhour' => '*/30 * * * *',
			'hourly' => '0 * * * *',
			'daily' => '0 0 * * *',
			'weekly' => '0 0 * * 0',
			'monthly' => '0 0 1 * *',
			'yearly' => '0 0 1 1 *',
		);
	}

	private function intervalToPlaceholder()
	{
		// Cron URL MAC tokens, matching the compose cron service environment
		return array(
			'minute' => 'MINUTE_PLACEHOLDER',
			'fiveminute' => 'FIVEMINUTE_PLACEHOLDER',
			'fifteenmin' => 'FIFTEENMIN_PLACEHOLDER',
			'halfhour' => 'HALFHOUR_PLACEHOLDER',
			'hourly' => 'HOURLY_PLACEHOLDER',
			'daily' => 'DAILY_PLACEHOLDER',
			'weekly' => 'WEEKLY_PLACEHOLDER',
			'monthly' => 'MONTHLY_PLACEHOLDER',
			'yearly' => 'YEARLY_PLACEHOLDER',
		);
	}

	private function cronLinePattern($interval)
	{
		// A scheduled line must hit the core's sanctioned HTTP cron endpoint:
		// wget with the Host header override, /cron/<interval>, and the
		// __elgg_mac MAC token placeholder — exactly as the 2.3.17 core
		// documents for cron-driven processing
		$schedule = preg_quote($this->expectedSchedules()[$interval], '~');
		return '~^' . $schedule
			. ' wget -qO- --header "Host: \{\{HOST_PLACEHOLDER\}\}" '
			. 'http://app:80/cron/' . $interval
			. '\?__elgg_mac=\{\{[A-Z]+_PLACEHOLDER\}\}$~';
	}

	private function exactScheduleLinePattern($interval, $token)
	{
		$schedule = preg_quote($this->expectedSchedules()[$interval], '~');
		$token = preg_quote($token, '~');
		return '~^' . $schedule
			. ' wget -qO- --header "Host: \{\{HOST_PLACEHOLDER\}\}" '
			. 'http://app:80/cron/' . preg_quote($interval, '~')
			. '\?__elgg_mac=\{\{' . $token . '\}\}$~';
	}

	public function supportedIntervalProvider()
	{
		$data = array();
		foreach ($this->intervalToPlaceholder() as $interval => $token) {
			$data[] = array($interval, $token);
		}
		return $data;
	}

	public function testEveryScheduledLineTargetsSanctionedHttpCronEndpoint()
	{
		$scheduledIntervals = array();
		foreach ($this->crontabLines() as $line) {
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			$matchedInterval = null;
			foreach (array_keys($this->expectedSchedules()) as $interval) {
				if (preg_match($this->cronLinePattern($interval), $line)) {
					$matchedInterval = $interval;
					break;
				}
			}
			$this->assertNotNull(
				$matchedInterval,
				"Crontab line does not target the core's sanctioned HTTP cron endpoint (/cron/<interval>?__elgg_mac=<token> with Host header override): {$line}"
			);
			$scheduledIntervals[] = $matchedInterval;
		}
		$this->assertCount(
			count($this->expectedSchedules()),
			$scheduledIntervals,
			'The crontab must schedule exactly one line per supported interval — nothing more, nothing less'
		);
	}

	/**
	 * @dataProvider supportedIntervalProvider
	 */
	public function testIntervalScheduledExactlyOnceAtDefinedFrequency($interval, $token)
	{
		$schedule = $this->expectedSchedules()[$interval];
		$matches = preg_grep(
			$this->exactScheduleLinePattern($interval, $token),
			$this->crontabLines()
		);
		$this->assertCount(
			1,
			$matches,
			"Interval '{$interval}' must be scheduled exactly once (no duplicated, no missed runs) at its defined frequency '{$schedule}'"
		);
	}

	public function testCronImageBakesCommittedCrontab()
	{
		$this->assertContains(
			'COPY docker/cron/crontab /tmp/app-cron',
			$this->cronDockerfileContents(),
			'The cron image must bake the committed crontab as the entrypoint spool source'
		);
	}

	public function testCronDockerfilePinsParserDirective()
	{
		$lines = preg_split('/\r?\n/', $this->cronDockerfileContents());
		$this->assertSame(
			'# syntax=docker/dockerfile:1',
			$lines[0],
			'The cron Dockerfile must pin the BuildKit frontend with the syntax parser directive as its first line'
		);
	}

	public function testCronEntrypointWritesSingleSpoolFileAndRunsCrondInForeground()
	{
		$compose = $this->composeContents();
		$this->assertContains(
			'/var/spool/cron/crontabs/root',
			$compose,
			'The cron entrypoint must expand tokens into the single active BusyBox spool location'
		);
		$this->assertContains(
			'chmod 600 /var/spool/cron/crontabs/root',
			$compose,
			'The expanded spool file must carry the strict permissions BusyBox cron requires'
		);
		$this->assertContains(
			'touch /var/spool/cron/crontabs/cron.update',
			$compose,
			'The entrypoint must force the daemon to reload the expanded schedule immediately'
		);
		$this->assertContains(
			'crond -f -d 8',
			$compose,
			'crond must run in foreground with debug logging so scheduled execution is observable via compose logs'
		);
	}
}
