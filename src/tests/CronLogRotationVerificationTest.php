<?php
/**
 * Guard test for the S1.7 Step 5 log rotation verification record.
 *
 * Pins the verification record for the scheduled-task output log
 * rotation: the record must exist and name the rotation script, its
 * daily schedule, the copy-truncate mechanism, the retention seed, and
 * the observed evidence.
 */

class CronLogRotationVerificationTest extends \PHPUnit_Framework_TestCase
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
		$this->assertContains('rotate-cron-log.sh', $contents);
		$this->assertContains('30 0 * * *', $contents);
	}

	public function testRecordPinsCopyTruncateMechanism()
	{
		$contents = $this->recordContents();
		$this->assertContains('copy-truncate', $contents);
		$this->assertContains('app-cron.log', $contents);
		$this->assertContains('gzip', $contents);
	}

	public function testRecordPinsRetentionSeed()
	{
		$contents = $this->recordContents();
		$this->assertContains('20200101-000000', $contents);
		$this->assertContains('touch -t', $contents);
	}

	public function testRecordPinsObservedEvidence()
	{
		$contents = $this->recordContents();
		$this->assertContains('20261026-030538', $contents);
		$this->assertContains('fs-cronlog', $contents);
	}
}
