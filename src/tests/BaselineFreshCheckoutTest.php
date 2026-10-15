<?php

/**
 * Story S1.1 Step 5 — fresh-checkout baseline procedure tests.
 *
 * Verifies the Step 5 deliverable docs/baseline/fresh_checkout.md documents
 * a repeatable clean-checkout-to-running-baseline procedure matching the
 * committed Compose stack, and that the executed verification result is
 * recorded in docs/baseline/baseline.md.
 */
class BaselineFreshCheckoutTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var string|null Cached contents of the procedure document
	 */
	private $procedureDocContents;

	/**
	 * @var string|null Cached contents of the baseline document
	 */
	private $baselineDocContents;

	private function srcDir()
	{
		return dirname(__DIR__);
	}

	private function repoDir()
	{
		return dirname($this->srcDir());
	}

	private function procedureDoc()
	{
		return $this->repoDir() . '/docs/baseline/fresh_checkout.md';
	}

	private function procedureDocContents()
	{
		if ($this->procedureDocContents === null) {
			$this->procedureDocContents = file_get_contents($this->procedureDoc());
		}

		return $this->procedureDocContents;
	}

	private function baselineDoc()
	{
		return $this->repoDir() . '/docs/baseline/baseline.md';
	}

	private function baselineDocContents()
	{
		if ($this->baselineDocContents === null) {
			$this->baselineDocContents = file_get_contents($this->baselineDoc());
		}

		return $this->baselineDocContents;
	}

	/**
	 * Must Support: "A repeatable procedure that takes a clean checkout to a
	 * running baseline" — the document must cover the full arc: clean
	 * checkout -> .env setup -> compose up -> install -> verify -> teardown.
	 */
	public function testFreshCheckoutProcedureIsDocumentedEndToEnd()
	{
		$this->assertFileExists($this->procedureDoc());
		$doc = $this->procedureDocContents();

		$this->assertContains('## Procedure', $doc);
		$this->assertContains('### 1. Clean checkout', $doc);
		$this->assertContains('git clone', $doc);
		$this->assertContains('### 2. Environment file', $doc);
		$this->assertContains('cp .env.example .env', $doc);
		$this->assertContains('### 3. Start the stack', $doc);
		$this->assertContains('docker compose up', $doc);
		$this->assertContains('### 6. Install Elgg', $doc);
		$this->assertContains('### 7. Verify the baseline', $doc);
		$this->assertContains('### 8. Tear down', $doc);
		$this->assertContains('docker compose down', $doc);
	}

	/**
	 * Must Support: "A repeatable procedure that takes a clean checkout to a
	 * running baseline" — the procedure's Step 3 expectations must match the
	 * committed Compose stack (service set and app image), otherwise a fresh
	 * checkout cannot be taken to a running baseline by following the doc.
	 *
	 * The committed stack is pinned here and the procedure doc is asserted
	 * against it. compose.yml lives in git at the repo root next to this
	 * test; any stack change must update both together.
	 */
	public function testFreshCheckoutProcedureMatchesCommittedStack()
	{
		$doc = $this->procedureDocContents();

		// Committed stack (compose.yml at this pin): services app, dbm,
		// cron; app image built/tagged sfn/elgg_webserver:7.4-composer2.
		// The dev-only pma service lives in compose.dev.yml (Story S1.6),
		// outside the production stack this procedure documents.
		$committedServices = array('app', 'dbm', 'cron');
		$committedAppImage = 'sfn/elgg_webserver:7.4-composer2';

		// The procedure must expect exactly the committed service set...
		foreach ($committedServices as $service) {
			$this->assertContains("`$service`", $doc);
		}

		// ...and exactly the committed app image, not the upstream one.
		$this->assertContains($committedAppImage, $doc);
		$this->assertNotContains('coldtrick/elgg_webserver', $doc);
	}

	/**
	 * Must Support: "Confirmation that the site loads and an administrator
	 * can log in from that fresh checkout" — the executed verification must
	 * be recorded as successful in the baseline documentation, dated and
	 * tied to the commit it verified.
	 */
	public function testFreshCheckoutVerificationRecordedInBaselineDoc()
	{
		$this->assertFileExists($this->baselineDoc());
		$doc = $this->baselineDocContents();

		// The verification section must exist and state success for the
		// front page load...
		$this->assertContains('## Fresh-Checkout Verification', $doc);
		$this->assertContains('Loaded at the site URL', $doc);

		// ...and for the admin login / administration area.
		$this->assertContains(
			'Logged in; administration area reachable and functional',
			$doc
		);

		// The run must be dated and tied to a commit for repeatability.
		$this->assertContains('Verification date', $doc);
		$this->assertContains('Commit verified', $doc);

		// Deviations must be explicitly recorded (None is a valid value).
		$this->assertContains('Deviations / findings', $doc);
	}
}
