<?php
/**
 * Guard test for the S1.7 Step 5 log rotation verification record.
 *
 * Pins the verification record for the scheduled-task output log
 * rotation: the record must exist and name the rotation script, its
 * daily schedule, the copy-truncate mechanism, the retention seed, and
 * the observed evidence.
 */

class CronLogRotationVerificationTest extends \PHPUnit\Framework\TestCase
{
	private function recordFile()
	{
		return dirname(dirname(__DIR__)) . '/docs/verification/s1.7-logrotation-verification.md';
	}

	private function recordContents()
	{
		return file_get_contents($this->recordFile());
	}

	public function testVerificationRecordExists()
	{
		$this->assertFileExists($this->recordFile());
	}

	public function testRecordPinsRotationScriptAndSchedule()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('rotate-cron-log.sh', $contents);
		$this->assertStringContainsString('30 0 * * *', $contents);
	}

	public function testRecordPinsCopyTruncateMechanism()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('copy-truncate', $contents);
		$this->assertStringContainsString('app-cron.log', $contents);
		$this->assertStringContainsString('gzip', $contents);
	}

	public function testRecordPinsRetentionSeed()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('20200101-000000', $contents);
		$this->assertStringContainsString('touch -t', $contents);
	}

	public function testRecordPinsObservedEvidence()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('20261026-030538', $contents);
		$this->assertStringContainsString('fs-cronlog', $contents);
	}
}
