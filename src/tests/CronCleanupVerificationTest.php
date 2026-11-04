<?php
/**
 * Guard test for the S1.7 Step 3 cron cleanup verification record.
 *
 * Pins the verification record for the garbagecollector cron run: the
 * record must exist and name the plugin, its interval, the trigger
 * endpoint, the seed marker, and the evidence keys.
 */

class CronCleanupVerificationTest extends \PHPUnit\Framework\TestCase
{
	private function recordFile()
	{
		return dirname(dirname(__DIR__)) . '/docs/verification/s1.7-cron-verification.md';
	}

	private function recordContents()
	{
		return file_get_contents($this->recordFile());
	}

	public function testVerificationRecordExists()
	{
		$this->assertFileExists($this->recordFile());
	}

	public function testRecordPinsPluginAndInterval()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('garbagecollector', $contents);
		$this->assertStringContainsString('`period` setting', $contents);
		$this->assertStringContainsString('monthly', $contents);
	}

	public function testRecordPinsTriggerEndpoint()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('/cron/', $contents);
		$this->assertStringContainsString('__elgg_mac', $contents);
	}

	public function testRecordPinsSeedMarker()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('s1.7-orphan-seed', $contents);
		$this->assertStringContainsString('metastrings', $contents);
	}

	public function testRecordPinsEvidenceKeys()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('cron_latest:', $contents);
		$this->assertStringContainsString('garbagecollector:gc:metastrings', $contents);
		$this->assertStringContainsString('garbagecollector:done', $contents);
	}
}
