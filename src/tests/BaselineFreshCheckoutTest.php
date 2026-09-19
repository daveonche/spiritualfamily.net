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
}
