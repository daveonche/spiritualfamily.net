<?php

class CronScheduleCoverageTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Story S1.7 Step 2 guard: every recurring interval the current platform
	 * version supports is scheduled at its defined frequency, and the
	 * schedule set is re-verifiable whenever the platform version in the
	 * upgrade sequence changes. The supported-interval list is derived from
	 * the vendored Elgg core itself (elgg_get_cron_periods() in
	 * engine/lib/cron.php) — never from the previous version's list — so a
	 * core upgrade that changes the supported intervals fails this suite
	 * until the crontab and the pinned schedule list are adjusted.
	 */

	private function repoDir()
	{
		// src/tests/<this file> -> repository root
		return dirname(__DIR__, 2);
	}

	private function crontabFile()
	{
		return $this->repoDir() . '/crontab';
	}

	private function crontabContents()
	{
		$path = $this->crontabFile();
		$this->assertFileExists($path, 'Committed crontab missing at repository root');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Committed crontab is not readable');
		return $contents;
	}

	private function coreCronLibraryContents()
	{
		// src/ resolved relative to this file (src/tests/…), per
		// BaselineVersionTest's pattern: works from the repo checkout and
		// inside the container, where src/ is mounted at /var/www/html
		$path = dirname(__DIR__) . '/vendor/elgg/elgg/engine/lib/cron.php';
		$this->assertFileExists(
			$path,
			'Vendored Elgg core cron library missing at src/vendor/elgg/elgg/engine/lib/cron.php'
		);
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Vendored Elgg core cron library is not readable');
		return $contents;
	}

	/**
	 * The intervals the vendored core itself supports, derived from the
	 * default period list inside elgg_get_cron_periods() — the same list the
	 * core's HTTP cron endpoint validates /cron/<interval> requests against.
	 */
	private function coreCronPeriods()
	{
		$contents = $this->coreCronLibraryContents();

		$this->assertSame(
			1,
			preg_match('~function elgg_get_cron_periods\(\)\s*\{(.*?)\n\}~s', $contents, $function),
			'elgg_get_cron_periods() not found in the vendored core cron library — the core changed its cron API; re-derive the supported intervals from the new core'
		);

		$this->assertSame(
			1,
			preg_match('~\$periods\s*=\s*(?:array\s*\(|\[)(.*?)(?:\)|\])~s', $function[1], $literal),
			'Default cron period list not found inside elgg_get_cron_periods() — re-derive the supported intervals from the new core'
		);

		$periods = array();
		if (preg_match_all("~'([a-z]+)'~", $literal[1], $strings)) {
			$periods = $strings[1];
		}
		$this->assertNotEmpty(
			$periods,
			'No cron periods could be derived from the vendored core — re-derive the supported intervals from the new core'
		);

		return $periods;
	}

	private function scheduledIntervals()
	{
		$intervals = array();
		foreach (preg_split('/\r?\n/', $this->crontabContents()) as $line) {
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			$this->assertSame(
				1,
				preg_match('~http://app:80/cron/([a-z]+)\?~', $line, $matches),
				"Crontab line does not target the core's sanctioned HTTP cron endpoint: {$line}"
			);
			$intervals[] = $matches[1];
		}
		return $intervals;
	}

	/**
	 * The pinned schedule list of the Step 1 guard test, read via reflection
	 * so there is exactly one source of truth for the pinned intervals.
	 */
	private function pinnedSchedules()
	{
		$guardFile = dirname(__DIR__) . '/CronSanctionedEntryPointTest.php';
		$this->assertFileExists($guardFile, 'Step 1 guard test (CronSanctionedEntryPointTest) missing');
		require_once $guardFile;

		$guard = new CronSanctionedEntryPointTest('testEveryScheduledLineTargetsSanctionedHttpCronEndpoint');
		$method = new ReflectionMethod('CronSanctionedEntryPointTest', 'expectedSchedules');
		$method->setAccessible(true);

		return $method->invoke($guard);
	}

	public function testCrontabScheduleSetMatchesVendoredCorePeriods()
	{
		$corePeriods = $this->coreCronPeriods();
		sort($corePeriods);

		$scheduled = $this->scheduledIntervals();
		sort($scheduled);

		$this->assertSame(
			$corePeriods,
			$scheduled,
			'The crontab schedule set must exactly match the intervals the vendored Elgg core supports (elgg_get_cron_periods()) — after a core upgrade, re-derive the interval list from the new core and adjust the crontab'
		);
	}

	public function testPinnedScheduleListMatchesVendoredCorePeriods()
	{
		$corePeriods = $this->coreCronPeriods();
		sort($corePeriods);

		$pinned = array_keys($this->pinnedSchedules());
		sort($pinned);

		$this->assertSame(
			$corePeriods,
			$pinned,
			'The pinned schedule list in CronSanctionedEntryPointTest must match the intervals the vendored Elgg core supports (elgg_get_cron_periods()) — after a core upgrade, re-derive the interval list from the new core and update the pinned list'
		);
	}
}
