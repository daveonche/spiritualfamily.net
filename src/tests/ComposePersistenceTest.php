<?php

/**
 * Step 4 tests for Story S1.2 — persistent storage for database and
 * application data; extended by Story S1.3 Step 2 (data-directory
 * ownership on the persistent volumes).
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
	 * Must Support: "correct ownership/permissions for the web container
	 * user" (Story S1.3 Step 2) — the app image's base pre-creates the
	 * three persistent mount targets owned by www-data, so first-use
	 * named-volume initialization (Docker copies the image mount point's
	 * ownership into an empty named volume) inherits it. Verified at
	 * runtime (docker run --rm <image> ls -ln /var/www); statically this
	 * pins the base-image line providing the directories and the matching
	 * .env.example target defaults.
	 */
	public function testDataMountTargetsPreCreatedInAppImageWithWebUserOwnership()
	{
		$dockerfile = $this->repoDir() . '/docker/app/Dockerfile';
		$this->assertFileExists($dockerfile);
		$contents = file_get_contents($dockerfile);

		// The base-image FROM line: the elgg_webserver image that ships
		// /var/www/{dataroot,cacheroot,assetroot} owned by www-data (33:33)
		$this->assertSame(
			1,
			preg_match('/^FROM coldtrick\/elgg_webserver:\$\{PHP_VERSION\}\s*$/m', $contents),
			"docker/app/Dockerfile must build FROM the elgg_webserver base image that pre-creates the data mount targets"
		);

		// The ownership reliance is documented in the Dockerfile so a base
		// image change doesn't silently drop it
		$this->assertSame(
			1,
			preg_match('/www-data.*mount target|mount target.*www-data/is', $contents),
			"docker/app/Dockerfile must document that the base image pre-creates the mount targets owned by www-data"
		);

		// The .env.example defaults match the image-provisioned paths, so
		// the documented contract keeps volumes on owned directories
		$envExample = $this->repoDir() . '/.env.example';
		$this->assertFileExists($envExample);
		$env = file_get_contents($envExample);
		// WWW_DATA/WWW_CACHE stay documented (settings.php reads them via
		// getenv); assetroot has no env consumer — its path is pinned by
		// the literal compose.yml mount target
		foreach (array(
			'WWW_DATA'  => '/var/www/dataroot',
			'WWW_CACHE' => '/var/www/cacheroot',
		) as $var => $path) {
			$this->assertSame(
				1,
				preg_match('/^' . $var . '=' . preg_quote($path, '/') . '\s*$/m', $env),
				".env.example must default $var to the image-provisioned path $path"
			);
		}
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
			preg_match('/^  dbm:\s*$(.*?)^  cron:/ms', $config, $m),
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
	 *
	 * Story S1.8 Step 6 exception: the cron service mounts the dataroot
	 * read-write solely to rotate the application log the app writes
	 * there (rotate-elgg-log.sh targeting ELGG_LOG_FILE) — guarded below
	 * as the only dataroot reference permitted outside the app service.
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
		// anonymous volume; the target is the literal image-provisioned
		// path (Coolify rejects ${VAR} volume targets), which .env's
		// WWW_DATA — read via getenv in settings.php — must match
		$this->assertSame(
			1,
			preg_match('/^\s+- fs-dataroot:\/var\/www\/dataroot\s*$/m', $appBlock),
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

		// Dedicated: no service other than app uses the dataroot volume.
		// Exception (Story S1.8 Step 6): the cron service mounts the
		// dataroot read-write solely to rotate the application log the
		// app writes there (rotate-elgg-log.sh, ELGG_LOG_FILE) — its
		// dataroot reference must be exactly that one mount line; dbm
		// must reference the volume never.
		$boundaries = array(
			'dbm' => '^  cron:',
			'cron' => '^networks:',
		);
		foreach ($boundaries as $service => $boundary) {
			$this->assertSame(
				1,
				preg_match('/^  ' . $service . ':\s*$(.*?)' . $boundary . '/ms', $config, $m),
				"compose.yml has no recognizable '$service' service block"
			);
			if ($service === 'dbm') {
				$this->assertSame(
					0,
					preg_match('/fs-dataroot/', $m[1]),
					"compose.yml '$service' service references the app dataroot volume — application data storage is not dedicated"
				);
				continue;
			}

			// cron: exactly one dataroot reference, and it must be the
			// Step 6 rotation mount itself (the exact-line match also
			// pins read-write: no :ro suffix) — any other reference
			// fails the guard
			$this->assertSame(
				1,
				preg_match_all('/fs-dataroot/', $m[1]),
				"compose.yml 'cron' service may reference the app dataroot volume only for the S1.8 Step 6 rotation mount"
			);
			$this->assertSame(
				1,
				preg_match('/^      - fs-dataroot:\/var\/www\/dataroot$/m', $m[1]),
				"compose.yml 'cron' service's dataroot reference must be the S1.8 Step 6 rotation mount (- fs-dataroot:/var/www/dataroot)"
			);
		}
	}

	/**
	 * Must Support: "Both surviving service restarts and rebuilds"
	 *
	 * Static proxy: Compose-managed named volumes declared at the top
	 * level survive `docker compose restart`, container recreation, and
	 * image rebuilds — only `down -v` or explicit volume removal discards
	 * them. Runtime survival itself is the manual verification's job.
	 */
	public function testDataStorageSurvivesRestartsAndRebuilds()
	{
		$composeFile = $this->composeFile();
		$this->assertFileExists($composeFile);
		$config = $this->composeConfig();

		// Top-level volumes block: all four data volumes declared exactly
		// once and Compose-managed (not external), so the committed
		// definition provisions the persistent storage itself
		$this->assertSame(
			1,
			preg_match('/^volumes:\s*$(.*)$/ms', $config, $m),
			'compose.yml has no top-level volumes block'
		);
		$topVolumes = $m[1];
		foreach (array('fs-dbmdata', 'fs-dataroot', 'fs-cacheroot', 'fs-assetroot') as $name) {
			$this->assertSame(
				1,
				preg_match_all('/^  ' . $name . ':\s*$/m', $topVolumes),
				"compose.yml must declare the $name named volume exactly once in the top-level volumes block"
			);
		}
		$this->assertSame(
			0,
			preg_match('/external\s*:/', $topVolumes),
			'compose.yml data volumes must be Compose-managed, not external'
		);

		// dbm: exactly one mount — the named db data volume. No host bind
		// and no anonymous volume that would tie db data to the container
		// lifecycle
		$this->assertSame(
			1,
			preg_match('/^  dbm:\s*$(.*?)^  cron:/ms', $config, $m),
			"compose.yml has no recognizable 'dbm' service block"
		);
		$this->assertSame(
			1,
			preg_match('/^    volumes:\s*$(.*?)^    \S/ms', $m[1], $m2),
			"compose.yml dbm service has no volumes subsection"
		);
		$this->assertSame(
			1,
			preg_match_all('/^\s+-\s+\S+\s*$/m', $m2[1]),
			'compose.yml dbm service must mount exactly one volume (the db data volume)'
		);
		$this->assertSame(
			1,
			preg_match('/^      - fs-dbmdata:\/var\/lib\/mysql\s*$/m', $m2[1]),
			"compose.yml dbm data must be the fs-dbmdata named volume at /var/lib/mysql"
		);

		// app: every data-bearing mount (dataroot, cacheroot, assetroot) is
		// a named-volume mount at its literal image-provisioned target
		$this->assertSame(
			1,
			preg_match('/^  app:\s*$(.*?)^  dbm:/ms', $config, $m),
			"compose.yml has no recognizable 'app' service block"
		);
		$this->assertSame(
			1,
			preg_match('/^    volumes:\s*$(.*?)^    \S/ms', $m[1], $m2),
			"compose.yml app service has no volumes subsection"
		);
		$appVolumes = $m2[1];
		$dataMounts = array(
			'fs-dataroot'  => '\/var\/www\/dataroot',
			'fs-cacheroot' => '\/var\/www\/cacheroot',
			'fs-assetroot' => '\/var\/www\/assetroot',
		);
		foreach ($dataMounts as $volume => $target) {
			$this->assertSame(
				1,
				preg_match('/^      - ' . $volume . ':' . $target . '\s*$/m', $appVolumes),
				"compose.yml app service must mount the $volume named volume at its data target"
			);
		}

		// No anonymous volumes (target-only entries) — every mount pairs a
		// source with a target, so no data path depends on container-local
		// anonymous storage that a rebuild would discard
		$this->assertSame(
			0,
			preg_match('/^\s+-\s+[^:\s]+\s*$/m', $appVolumes),
			'compose.yml app service must not use anonymous volumes'
		);
	}
}
