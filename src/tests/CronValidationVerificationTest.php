<?php

/**
 * Guard test for the S1.7 Step 4 verification record: the account-validation
 * notification task must be verified end-to-end through the trigger path the
 * pinned core actually provides, with the no-cron-handler finding and the
 * per-upgrade re-check duty documented.
 */
class CronValidationVerificationTest extends \PHPUnit_Framework_TestCase
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
		$this->assertContains('registers no cron handler', $contents);
		$this->assertContains('uservalidationbyemail_disable_new_user', $contents);
		$this->assertContains('uservalidationbyemail_check_manual_login', $contents);
		$this->assertContains('uservalidationbyemail_request_validation', $contents);
		$this->assertContains('notify_user', $contents);
	}

	public function testRecordDocumentsEndToEndNotificationVerification()
	{
		$contents = $this->recordContents();
		$this->assertContains('Seed a pending account', $contents);
		$this->assertContains('MailHog', $contents);
		$this->assertContains('signed validation link', $contents);
		$this->assertContains('uservalidationbyemail/confirm?u=', $contents);
	}

	public function testRecordDocumentsPerUpgradeDuty()
	{
		$contents = $this->recordContents();
		$this->assertContains('Per-upgrade duty', $contents);
		$this->assertContains('cron-driven', $contents);
		$this->assertContains('docs/analysis/S1.7-story-steps.md', $contents);
	}
}
