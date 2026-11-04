<?php

/**
 * Guard test for the S1.7 Step 4 verification record: the account-validation
 * notification task must be verified end-to-end through the trigger path the
 * pinned core actually provides, with the no-cron-handler finding and the
 * per-upgrade re-check duty documented.
 */
class CronValidationVerificationTest extends \PHPUnit\Framework\TestCase
{
	private function recordFile()
	{
		return dirname(dirname(__DIR__)) . '/docs/verification/s1.7-uservalidation-verification.md';
	}

	private function recordContents()
	{
		$contents = file_get_contents($this->recordFile());
		$this->assertNotFalse(
			$contents,
			'S1.7 Step 4 verification record is missing at ' . $this->recordFile()
		);
		return $contents;
	}

	public function testRecordDocumentsNoCronHandlerFinding()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('registers no cron handler', $contents);
		$this->assertStringContainsString('uservalidationbyemail_disable_new_user', $contents);
		$this->assertStringContainsString('uservalidationbyemail_check_manual_login', $contents);
		$this->assertStringContainsString('uservalidationbyemail_request_validation', $contents);
		$this->assertStringContainsString('notify_user', $contents);
	}

	public function testRecordDocumentsEndToEndNotificationVerification()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('Seed a pending account', $contents);
		$this->assertStringContainsString('MailHog', $contents);
		$this->assertStringContainsString('signed validation link', $contents);
		$this->assertStringContainsString('uservalidationbyemail/confirm?u=', $contents);
	}

	public function testRecordDocumentsPerUpgradeDuty()
	{
		$contents = $this->recordContents();
		$this->assertStringContainsString('Per-upgrade duty', $contents);
		$this->assertStringContainsString('cron-driven', $contents);
		$this->assertStringContainsString('docs/analysis/S1.7-story-steps.md', $contents);
	}
}
