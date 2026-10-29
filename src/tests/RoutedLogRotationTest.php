<?php

class RoutedLogRotationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Story S1.8 Step 6 guard: rotation is defined for the routed
	 * container and application logs and happens automatically at its
	 * configured schedule without operator intervention, rotated
	 * archives are retained for a defined period and then removed, and
	 * the rotation duty complements the scheduled-task log rotation
	 * already in place from S1.7.
	 *
	 * Two complementary mechanisms:
	 * - stdout (all three services): the Compose json-file logging
	 *   block rotates size-capped segments automatically (max-size /
	 *   max-file) — the daemon prunes beyond the archive count.
	 * - the Elgg application log (ELGG_LOG_FILE, Step 1): a daily cron
	 *   entry copy-truncates the file into date-stamped gzip archives
	 *   and prunes archives older than the retention window — the same
	 *   mechanism and window as the S1.7 scheduled-task rotation.
	 */

	/** The S1.7 scheduled-task rotation script this duty complements. */
	const S1_7_SCRIPT = 'docker/cron/rotate-cron-log.sh';

	private function repoDir()
	{
		// src/tests/<this file> -> repository root
		return dirname(__DIR__, 2);
	}

	private function readFile($relativePath)
	{
		$path = $this->repoDir() . '/' . $relativePath;
		$this->assertFileExists($path, "$relativePath missing");
		$contents = file_get_contents($path);
		$this->assertNotFalse($contents, "$relativePath is not readable");
		return $contents;
	}

	/** Comment-stripped file contents (prose may say anything). */
	private function configWithoutComments($relativePath)
	{
		return preg_replace('/^\s*#.*$/m', '', $this->readFile($relativePath));
	}

	private function composeConfig()
	{
		return $this->configWithoutComments('compose.yml');
	}

	/**
	 * The cron service block, bounded by the top-level networks key
	 * (same bounding as CronLogRotationTest).
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

	private function crontabScheduleLines()
	{
		$scheduleLines = array();
		foreach (preg_split('/\r?\n/', $this->readFile('docker/cron/crontab')) as $line) {
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			$scheduleLines[] = $line;
		}
		return $scheduleLines;
	}

	private function retentionDays($scriptRelativePath)
	{
		$script = $this->readFile($scriptRelativePath);
		$this->assertSame(
			1,
			preg_match('/^RETENTION_DAYS=(\d+)$/m', $script, $m),
			"$scriptRelativePath must define the retention window as a numeric RETENTION_DAYS constant"
		);
		return (int) $m[1];
	}

	/**
	 * Must Support: "Rotation is defined for the routed container …
	 * logs and happens automatically at its configured size … without
	 * operator intervention"
	 *
	 * Every service carries the json-file logging block with size/count
	 * rotation: the daemon rotates at the size cap and keeps at most
	 * max-file archives (removing the oldest) — no operator action.
	 */
	public function testEveryServiceRotatesStdoutAutomatically()
	{
		$config = $this->composeConfig();

		$this->assertSame(
			3,
			preg_match_all('/^    logging:$/m', $config),
			'compose.yml must define a rotation logging block on every service (app, dbm, cron)'
		);
		$this->assertSame(
			3,
			preg_match_all('/^      driver: json-file$/m', $config),
			'Every logging block must pin the default json-file driver'
		);
		$this->assertSame(
			3,
			preg_match_all('/^        max-size: "5m"$/m', $config),
			'Every logging block must cap each stdout segment at 5 MB (quoted — unquoted values are misparsed by YAML)'
		);
		$this->assertSame(
			3,
			preg_match_all('/^        max-file: "5"$/m', $config),
			'Every logging block must retain at most 5 rotated stdout segments (quoted — unquoted values are misparsed by YAML)'
		);
	}

	/**
	 * Must Support: "Rotation is defined for the … application logs and
	 * happens automatically at its configured … schedule, without
	 * operator intervention"
	 *
	 * The application-log rotation script copy-truncates the Elgg log
	 * into a date-stamped gzip archive — the same copy-truncate
	 * mechanism as the S1.7 scheduled-task rotation (the app opens the
	 * log in append mode, so truncating after the copy never loses
	 * output).
	 */
	public function testElggLogScriptCopyTruncatesIntoGzipArchives()
	{
		$script = $this->readFile('docker/cron/rotate-elgg-log.sh');

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
			preg_match('/^cp "\$LOG_FILE" "\$ARCHIVE"$/m', $script),
			'Rotation script must copy the active log to the dated archive (copy half of copy-truncate)'
		);
		$this->assertSame(
			1,
			preg_match('/date \+%Y%m%d-%H%M%S/', $script),
			'Rotation script must stamp the archive with a date-time name'
		);

		// Truncate half: empty the active log after copying so the
		// app's append-mode writes never lose output
		$this->assertSame(
			1,
			preg_match('/^: > "\$LOG_FILE"$/m', $script),
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
	 * and then removed" (application-log half)
	 */
	public function testElggLogScriptPrunesArchivesBeyondDefinedRetention()
	{
		$script = $this->readFile('docker/cron/rotate-elgg-log.sh');

		$this->assertSame(
			1,
			preg_match('/^RETENTION_DAYS=\d+$/m', $script),
			'Rotation script must define the retention window as a numeric RETENTION_DAYS constant'
		);
		$this->assertSame(
			1,
			preg_match(
				'/^find "\$LOG_DIR" -name "\$\(basename "\$LOG_FILE"\)\.\*\.gz" -mtime \+"\$RETENTION_DAYS" -delete$/m',
				$script
			),
			'Rotation script must prune gzip archives older than the retention window'
		);
	}

	/**
	 * Developer Note: "Align retention with the window used by the S1.7
	 * scheduled-task rotation for a consistent diagnostics trail."
	 */
	public function testRetentionWindowMatchesTheS1ScheduledTaskRotation()
	{
		$this->assertSame(
			$this->retentionDays(self::S1_7_SCRIPT),
			$this->retentionDays('docker/cron/rotate-elgg-log.sh'),
			'The application-log retention window must equal the S1.7 scheduled-task retention window — one consistent diagnostics trail'
		);
	}

	/**
	 * Must Support: "…happens automatically at its configured …
	 * schedule, without operator intervention" — the rotation is itself
	 * a committed cron schedule, staggered after the S1.7 entry.
	 */
	public function testCrontabSchedulesElggLogRotationDailyExactlyOnce()
	{
		$scheduleLines = $this->crontabScheduleLines();

		$this->assertCount(
			1,
			preg_grep(
				'~^45 0 \* \* \* /usr/local/bin/rotate-elgg-log\.sh >> /var/log/cron/app-cron\.log 2>&1$~',
				$scheduleLines
			),
			'Crontab must schedule the application-log rotation exactly once daily (45 0 * * *), staggered after the S1.7 rotation entry, appending its output to the dedicated log'
		);
		$this->assertCount(
			1,
			preg_grep('/rotate-elgg-log\.sh/', $scheduleLines),
			'Crontab must reference the application-log rotation script on exactly one scheduled line'
		);
	}

	/**
	 * The production definition carries no repo bind mounts (Story
	 * S1.6), so the application-log rotation script must be baked into
	 * the cron image (same rationale as the S1.7 script).
	 */
	public function testCronImageBakesExecutableElggLogRotationScript()
	{
		$dockerfile = $this->readFile('docker/cron/Dockerfile');

		$this->assertSame(
			1,
			preg_match(
				'/^COPY\s+docker\/cron\/rotate-elgg-log\.sh\s+\/usr\/local\/bin\/rotate-elgg-log\.sh\s*$/m',
				$dockerfile
			),
			'Cron image must bake the application-log rotation script at /usr/local/bin/rotate-elgg-log.sh'
		);
		$this->assertSame(
			1,
			preg_match(
				'/^RUN chmod 755 \/usr\/local\/bin\/rotate-elgg-log\.sh\s*$/m',
				$dockerfile
			),
			'The baked application-log rotation script must be executable'
		);
	}

	/**
	 * The rotation script runs inside the cron container but rotates
	 * the app-written log: the cron service must mount the fs-dataroot
	 * volume (read-write) and receive the ELGG_LOG_FILE path (the same
	 * variable the app's settings.php reads, Step 1).
	 */
	public function testCronServiceCanReachTheApplicationLog()
	{
		$cronBlock = $this->cronServiceBlock();

		$this->assertSame(
			1,
			preg_match('/^      - fs-dataroot:\/var\/www\/dataroot$/m', $cronBlock),
			'Cron service must mount the fs-dataroot volume at /var/www/dataroot to reach the application log'
		);
		$this->assertSame(
			1,
			preg_match('/^      - ELGG_LOG_FILE=\$\{ELGG_LOG_FILE:-.+\}$/m', $cronBlock),
			'Cron service must receive ELGG_LOG_FILE (Compose interpolation with a baked default) so the rotation script targets the app-written log path'
		);
	}
}
