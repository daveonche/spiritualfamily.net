<?php

/**
 * Step 3 tests for Story S1.2 — single-definition orchestration of web,
 * database, and scheduled-task services.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposeOrchestrationTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function composeFile()
	{
		return $this->repoDir() . '/compose.yml';
	}

	/** Comment-stripped compose.yml contents (prose may say anything). */
	private function composeConfig()
	{
		return preg_replace('/^\s*#.*$/m', '', file_get_contents($this->composeFile()));
	}

	/**
	 * Must Support: "One committed orchestration definition bringing up the
	 * web application, the database, and the scheduled-task service together"
	 */
	public function testSingleDefinitionBringsUpAppDatabaseAndCronTogether()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// All three services are defined in the one committed file
		foreach (array('app', 'dbm', 'cron') as $service) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$/m', $config),
				"compose.yml does not define the '$service' service"
			);
		}

		// Startup ordering: app waits for dbm to be really ready
		// (long-syntax depends_on with the health condition; dbm nests at
		// 6 spaces under the 4-space depends_on key)
		$this->assertSame(
			1,
			preg_match('/^  app:.*?^    depends_on:.*?^      dbm:.*?condition:\s*service_healthy/ms', $config),
			'compose.yml app service does not depend on dbm with condition service_healthy'
		);

		// cron starts alongside the others, after the app it targets
		$this->assertSame(
			1,
			preg_match('/^  cron:.*?^    depends_on:\s*$(?:\s+-\s+app\s*$)/ms', $config),
			'compose.yml cron service does not depend on the app service'
		);
	}
}
