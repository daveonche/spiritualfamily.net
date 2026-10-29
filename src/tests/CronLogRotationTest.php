<?php

class CronLogRotationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Story S1.7 Step 5 guard: the scheduled-task output log rotates
	 * automatically at its configured schedule without operator
	 * intervention, rotated archives are retained for a defined period
	 * and then removed, and the logging duty formerly covered by the
	 * dropped legacy log-rotation plugin (logrotate, REQ-FR-PU-7) is
	 * preserved by this configuration.
	 *
	 * The active log is /var/log/cron/app-cron.log inside the cron
	 * container: every Elgg interval line in the committed crontab
	 * appends its output there, and a daily rotation entry copy-truncates
	 * the log into date-stamped gzip archives and prunes archives older
	 * than the retention window. The log directory sits on a named
	 * volume so archives survive container recreation.
	 */

	/**
	 * The active scheduled-task output log inside the cron container —
	 * the file the interval lines append to and the rotation script
	 * rotates.
	 */
	const ACTIVE_LOG_PATH = '/var/log/cron/app-cron.log';

	private function repoDir()
	{
		// src/tests/<this file> -> repository root
		return dirname(__DIR__, 2);
	}

	private function rotationScriptFile()
	{
		return $this->repoDir() . '/docker/cron/rotate-cron-log.sh';
	}

	private function rotationScriptContents()
	{
		$path = $this->rotationScriptFile();
		$this->assertFileExists($path, 'Rotation script missing at docker/cron/rotate-cron-log.sh');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Rotation script is not readable');
		return $contents;
	}

	private function crontabContents()
	{
		$path = $this->repoDir() . '/docker/cron/crontab';
		$this->assertFileExists($path, 'Committed crontab missing at docker/cron/crontab');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Committed crontab is not readable');
		return $contents;
	}

	private function cronDockerfileContents()
	{
		$path = $this->repoDir() . '/docker/cron/Dockerfile';
		$this->assertFileExists($path, 'Cron Dockerfile missing');
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, 'Cron Dockerfile is not readable');
		return $contents;
	}

	/** Comment-stripped compose.yml contents (prose may say anything). */
	private function composeConfig()
	{
		$path = $this->repoDir() . '/compose.yml';
		$this->assertFileExists($path, 'compose.yml missing at repository root');
		$config = preg_replace('/^\s*#.*$/m', '', file_get_contents($path));
		$this->assertNotFalse($config, 'compose.yml is not readable');
		return $config;
	}

	/**
	 * The cron service block, bounded by the top-level networks key
	 * (same bounding as ComposeOrchestrationTest).
	 */
	private function cronServiceBlock()
	{
		$config = $this->composeConfig();
		$this->assertSame(
			1,
			preg_match('/^  cron:$(.*?)^networks:/ms', $config, $m),
			'compose.yml has no recognizable cron service block'
		);
		return $m[1];
	}

	/**
	 * Must Support: "The scheduled-task output log rotates automatically
	 * at its configured size or schedule, without operator intervention"
	 *
	 * The rotation script copy-truncates the active log into a
	 * date-stamped gzip archive: the copy preserves the output, the
	 * truncate keeps the appending cron jobs writing without loss (each
	 * job opens the log in append mode), and the crontab schedule drives
	 * it — no operator action.
	 */
	public function testRotationScriptCopyTruncatesIntoGzipArchives()
	{
		$script = $this->rotationScriptContents();

		// BusyBox-compatible baseline: the alpine cron image ships no
		// bash and no logrotate — POSIX sh only, aborting on errors
		$this->assertSame(
			1,
			preg_match('/^#!\/bin\/sh$/m', $script),
			'Rotation script must run under the cron image\'s POSIX sh (/bin/sh) — the alpine image ships no bash'
		);
		$this->assertSame(
			1,
			preg_match('/^set -eu$/m', $script),
			'Rotation script must abort on unset variables and failed commands (set -eu)'
		);

		// Copy half: archive a copy of the active log under a dated name
		$this->assertSame(
			1,
			preg_match('/^cp "\$ACTIVE_LOG" "\$ARCHIVE"$/m', $script),
			'Rotation script must copy the active log to the dated archive (copy half of copy-truncate)'
		);
		$this->assertSame(
			1,
			preg_match('/date \+%Y%m%d-%H%M%S/', $script),
			'Rotation script must stamp the archive with a date-time name'
		);

		// Truncate half: empty the active log after copying so the
		// appending cron jobs never lose output
		$this->assertSame(
			1,
			preg_match('/^: > "\$ACTIVE_LOG"$/m', $script),
			'Rotation script must truncate the active log after copying (truncate half of copy-truncate)'
		);

		// Archives are gzip-compressed
		$this->assertSame(
			1,
			preg_match('/^gzip -f "\$ARCHIVE"$/m', $script),
			'Rotation script must gzip the dated archive'
		);
	}

	/**
	 * Must Support: "Rotated archives are retained for a defined period
	 * and then removed"
	 */
	public function testRotationScriptPrunesArchivesBeyondDefinedRetention()
	{
		$script = $this->rotationScriptContents();

		// The retention window is a defined numeric constant
		$this->assertSame(
			1,
			preg_match('/^RETENTION_DAYS=\d+$/m', $script),
			'Rotation script must define the retention window as a numeric RETENTION_DAYS constant'
		);

		// Archives beyond the window are removed automatically
		$this->assertSame(
			1,
			preg_match(
				'/^find "\$LOG_DIR" -name \'app-cron\.log\.\*\.gz\' -mtime \+"\$RETENTION_DAYS" -delete$/m',
				$script
			),
			'Rotation script must prune gzip archives older than the retention window'
		);
	}

	/**
	 * Must Support: "…rotates automatically … without operator
	 * intervention" — the rotation is itself a committed cron schedule.
	 */
	public function testCrontabSchedulesRotationDailyExactlyOnce()
	{
		$scheduleLines = array();
		foreach (preg_split('/\r?\n/', $this->crontabContents()) as $line) {
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			$scheduleLines[] = $line;
		}

		// Exactly one daily rotation entry, after the midnight Elgg jobs,
		// its own output appended to the dedicated log (the image ships
		// no sendmail, so unredirected job output would be lost)
		$this->assertCount(
			1,
			preg_grep(
				'~^30 0 \* \* \* /usr/local/bin/rotate-cron-log\.sh >> /var/log/cron/app-cron\.log 2>&1$~',
				$scheduleLines
			),
			'Crontab must schedule the rotation script exactly once daily (30 0 * * *), after the midnight Elgg jobs, appending its output to the dedicated log'
		);

		// And the rotation script appears on no other scheduled line
		$this->assertCount(
			1,
			preg_grep('/rotate-cron-log\.sh/', $scheduleLines),
			'Crontab must reference the rotation script on exactly one scheduled line'
		);
	}

	/**
	 * The rotated file must be the scheduled-task output log itself:
	 * every Elgg interval line appends its output to the active log the
	 * rotation script rotates.
	 */
	public function testEveryIntervalLineAppendsToTheRotatedActiveLog()
	{
		$intervalLines = preg_grep(
			'~^(\S+\s+){5}wget\s+-qO-~',
			preg_split('/\r?\n/', $this->crontabContents())
		);

		$this->assertCount(
			9,
			$intervalLines,
			'Crontab must schedule exactly the nine Elgg interval lines'
		);
		foreach ($intervalLines as $line) {
			$this->assertSame(
				1,
				preg_match('~ >> ' . preg_quote(self::ACTIVE_LOG_PATH, '~') . ' 2>&1$~', $line),
				"Interval line must append its output to the rotated active log: $line"
			);
		}
	}

	/**
	 * The production definition carries no repo bind mounts (Story
	 * S1.6), so the rotation script must be baked into the cron image.
	 */
	public function testCronImageBakesExecutableRotationScript()
	{
		$dockerfile = $this->cronDockerfileContents();

		$this->assertSame(
			1,
			preg_match(
				'/^COPY\s+docker\/cron\/rotate-cron-log\.sh\s+\/usr\/local\/bin\/rotate-cron-log\.sh\s*$/m',
				$dockerfile
			),
			'Cron image must bake the rotation script at /usr/local/bin/rotate-cron-log.sh'
		);
		$this->assertSame(
			1,
			preg_match(
				'/^RUN chmod 755 \/usr\/local\/bin\/rotate-cron-log\.sh\s*$/m',
				$dockerfile
			),
			'The baked rotation script must be executable'
		);
	}

	/**
	 * Retention must span container recreation: the log directory lives
	 * on a named volume, not the container's ephemeral filesystem.
	 */
	public function testLogDirectoryPersistsOnANamedVolume()
	{
		$this->assertSame(
			1,
			preg_match('/^      - fs-cronlog:\/var\/log\/cron$/m', $this->cronServiceBlock()),
			'Cron service must mount the fs-cronlog named volume at /var/log/cron'
		);
		$this->assertSame(
			1,
			preg_match('/^  fs-cronlog:\s*$/m', $this->composeConfig()),
			'compose.yml must declare the fs-cronlog named volume at the top level'
		);
	}
}
