<?php

/**
 * Step 4 tests for Story S1.2 — persistent storage for database and
 * application data.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class ComposePersistenceTest extends \PHPUnit_Framework_TestCase
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
	 * Must Support: "Dedicated persistent storage for the database data"
	 */
	public function testDatabaseDataUsesDedicatedNamedVolume()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// The dbm service block, bounded by the next service definition
		$this->assertSame(
			1,
			preg_match('/^  dbm:\s*$(.*?)^  pma:/ms', $config, $m),
			"compose.yml has no recognizable 'dbm' service block"
		);
		$dbmBlock = $m[1];

		// The database data directory is a named-volume mount at MySQL's
		// data directory — not a host bind and not an anonymous volume
		$this->assertSame(
			1,
			preg_match('/^\s+- fs-dbmdata:\/var\/lib\/mysql\s*$/m', $dbmBlock),
			"compose.yml dbm service does not mount the fs-dbmdata named volume at /var/lib/mysql"
		);

		// The named volume is declared exactly once in the top-level
		// volumes block (volumes is the final top-level key)
		$this->assertSame(
			1,
			preg_match('/^volumes:\s*$(.*)$/ms', $config, $m),
			'compose.yml has no top-level volumes block'
		);
		$this->assertSame(
			1,
			preg_match_all('/^  fs-dbmdata:\s*$/m', $m[1]),
			'compose.yml must declare the fs-dbmdata named volume exactly once in the top-level volumes block'
		);

		// Dedicated: no service other than dbm references the db data volume
		$boundaries = array(
			'app' => '^  dbm:',
			'pma' => '^  cron:',
			'cron' => '^networks:',
		);
		foreach ($boundaries as $service => $boundary) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$(.*?)' . $boundary . '/ms', $config, $m),
				"compose.yml has no recognizable '$service' service block"
			);
			$this->assertSame(
				0,
				preg_match('/fs-dbmdata/', $m[1]),
				"compose.yml '$service' service references the dbm data volume — database storage is not dedicated"
			);
		}
	}

	/**
	 * Must Support: "Dedicated persistent storage for the application data
	 * directory"
	 */
	public function testApplicationDataDirectoryUsesDedicatedNamedVolume()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// The app service block, bounded by the next service definition
		$this->assertSame(
			1,
			preg_match('/^  app:\s*$(.*?)^  dbm:/ms', $config, $m),
			"compose.yml has no recognizable 'app' service block"
		);
		$appBlock = $m[1];

		// The application data directory (Elgg dataroot) is a named-volume
		// mount at the dataroot target — not a host bind and not an
		// anonymous volume; the target enters via the WWW_DATA variable
		// with the dataroot default (documented in .env.example)
		$this->assertSame(
			1,
			preg_match('/^\s+- fs-dataroot:\$\{WWW_DATA:-\/var\/www\/dataroot\}\s*$/m', $appBlock),
			"compose.yml app service does not mount the fs-dataroot named volume at the dataroot target"
		);

		// The named volume is declared exactly once in the top-level
		// volumes block (volumes is the final top-level key)
		$this->assertSame(
			1,
			preg_match('/^volumes:\s*$(.*)$/ms', $config, $m),
			'compose.yml has no top-level volumes block'
		);
		$this->assertSame(
			1,
			preg_match_all('/^  fs-dataroot:\s*$/m', $m[1]),
			'compose.yml must declare the fs-dataroot named volume exactly once in the top-level volumes block'
		);

		// Dedicated: no service other than app references the dataroot volume
		$boundaries = array(
			'dbm' => '^  pma:',
			'pma' => '^  cron:',
			'cron' => '^networks:',
		);
		foreach ($boundaries as $service => $boundary) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$(.*?)' . $boundary . '/ms', $config, $m),
				"compose.yml has no recognizable '$service' service block"
			);
			$this->assertSame(
				0,
				preg_match('/fs-dataroot/', $m[1]),
				"compose.yml '$service' service references the app dataroot volume — application data storage is not dedicated"
			);
		}
	}
}
