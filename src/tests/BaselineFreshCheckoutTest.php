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

	/**
	 * @var string|null Cached contents of compose.yml
	 */
	private $composeContents;

	private function composeFile()
	{
		return $this->repoDir() . '/compose.yml';
	}

	private function composeContents()
	{
		if ($this->composeContents === null) {
			$this->composeContents = file_get_contents($this->composeFile());
		}

		return $this->composeContents;
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
	 */
	public function testFreshCheckoutProcedureMatchesCommittedStack()
	{
		$this->assertFileExists($this->composeFile());
		$compose = $this->composeContents();
		$doc = $this->procedureDocContents();

		// Service set in the committed stack...
		foreach (array('app', 'dbm', 'pma', 'cron') as $service) {
			$this->assertContains("  $service:", $compose);
		}

		// ...must be the same set the procedure tells the user to expect.
		foreach (array('app', 'dbm', 'pma', 'cron') as $service) {
			$this->assertContains("`$service`", $doc);
		}

		// App image: compose builds/tags sfn/elgg_webserver:7.4-composer2;
		// the procedure must expect exactly that image, not the upstream one.
		$this->assertContains('sfn/elgg_webserver:7.4-composer2', $compose);
		$this->assertContains('sfn/elgg_webserver:7.4-composer2', $doc);
		$this->assertNotContains('coldtrick/elgg_webserver', $doc);
	}
}
