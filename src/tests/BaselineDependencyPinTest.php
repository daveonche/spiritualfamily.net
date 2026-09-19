<?php

/**
 * Step 3 tests for Story S1.1 — dependency manifest and committed lock file.
 *
 * Verifies that the dependency manifest declares the pinned core and
 * runtime versions, that a lock file generated from that manifest is
 * present, and that the lock stays consistent with the manifest.
 *
 * The "committed to the repository" aspect of the lock file is verified
 * manually (the app container mounts only src/ and docs/, not git).
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class BaselineDependencyPinTest extends \PHPUnit_Framework_TestCase
{
	/** The single pinned Elgg core version (docs/baseline/baseline.md). */
	const PINNED_CORE_VERSION = '2.3.17';

	/** The single pinned PHP runtime version (docs/baseline/baseline.md). */
	const PINNED_RUNTIME_VERSION = '7.4.27';

	private function srcDir()
	{
		return dirname(__DIR__);
	}

	private function composerFile()
	{
		return $this->srcDir() . '/composer.json';
	}

	private function lockFile()
	{
		return $this->srcDir() . '/composer.lock';
	}

	/**
	 * Must Support: "A dependency manifest declaring the pinned core
	 * version and the pinned runtime platform version"
	 */
	public function testManifestDeclaresExactPinnedCoreVersion()
	{
		$composerFile = $this->composerFile();
		$this->assertFileExists($composerFile);

		$composer = json_decode(file_get_contents($composerFile), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.json is not valid JSON');
		$this->assertArrayHasKey('require', $composer);
		$this->assertArrayHasKey('elgg/elgg', $composer['require']);
		$this->assertSame(
			self::PINNED_CORE_VERSION,
			$composer['require']['elgg/elgg'],
			'The manifest does not declare elgg/elgg at exactly the pinned core version'
		);

		// No open-ended ranges: reject comparison operators, wildcards,
		// and range separators in the pinned constraint.
		$this->assertNotRegExp(
			'/[<>*~^|,]/',
			$composer['require']['elgg/elgg'],
			'The elgg/elgg constraint must be an exact pin with no open-ended range'
		);
	}

	/**
	 * Must Support: "A dependency manifest declaring the pinned core
	 * version and the pinned runtime platform version"
	 */
	public function testManifestPinsRuntimePlatformVersion()
	{
		$composer = json_decode(file_get_contents($this->composerFile()), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.json is not valid JSON');
		$this->assertArrayHasKey('config', $composer);
		$this->assertArrayHasKey('platform', $composer['config']);
		$this->assertArrayHasKey('php', $composer['config']['platform']);
		$this->assertSame(
			self::PINNED_RUNTIME_VERSION,
			$composer['config']['platform']['php'],
			'The manifest does not pin the runtime platform version via config.platform.php'
		);
	}

	/**
	 * Must Support: "A lock file generated from that manifest and
	 * committed to the repository"
	 */
	public function testLockFileExistsAndIsValidJson()
	{
		$lockFile = $this->lockFile();
		$this->assertFileExists($lockFile);

		json_decode(file_get_contents($lockFile), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.lock is not valid JSON');
	}

	/**
	 * Must Support: "A lock file generated from that manifest and
	 * committed to the repository"
	 */
	public function testLockRecordsPinnedCoreAndRuntimeVersions()
	{
		$lock = json_decode(file_get_contents($this->lockFile()), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.lock is not valid JSON');

		// The pinned core package must be recorded at exactly the pinned
		// version in the lock's package set.
		$packages = array();
		if (isset($lock['packages']) && is_array($lock['packages'])) {
			$packages = array_merge($packages, $lock['packages']);
		}
		if (isset($lock['packages-dev']) && is_array($lock['packages-dev'])) {
			$packages = array_merge($packages, $lock['packages-dev']);
		}

		$elggVersions = array();
		foreach ($packages as $package) {
			if (isset($package['name']) && $package['name'] === 'elgg/elgg') {
				$elggVersions[] = $package['version'];
			}
		}

		$this->assertSame(
			array(self::PINNED_CORE_VERSION),
			$elggVersions,
			'composer.lock must record elgg/elgg at exactly the pinned core version'
		);

		// The locked platform override must match the pinned runtime.
		$this->assertArrayHasKey('platform-overrides', $lock);
		$this->assertArrayHasKey('php', $lock['platform-overrides']);
		$this->assertSame(
			self::PINNED_RUNTIME_VERSION,
			$lock['platform-overrides']['php'],
			'composer.lock platform-overrides.php does not match the pinned runtime version'
		);
	}

	/**
	 * Must Support: "Ongoing consistency between the manifest and the
	 * lock file"
	 */
	public function testLockContentHashMatchesManifest()
	{
		$lock = json_decode(file_get_contents($this->lockFile()), true);
		$this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.lock is not valid JSON');
		$this->assertArrayHasKey('content-hash', $lock);

		$this->assertSame(
			$this->computeContentHash(file_get_contents($this->composerFile())),
			$lock['content-hash'],
			'composer.lock content-hash does not match composer.json; the lock is out of sync with the manifest'
		);
	}

	/** Local re-implementation of Composer's Locker::getContentHash. */
	private function computeContentHash($composerFileContents)
	{
		$content = json_decode($composerFileContents, true);

		$relevantKeys = array(
			'name', 'version', 'require', 'require-dev', 'conflict',
			'replace', 'provide', 'minimum-stability', 'prefer-stable',
			'prefer-lowest', 'repositories', 'extra',
		);

		$relevantContent = array();
		foreach (array_intersect($relevantKeys, array_keys($content)) as $key) {
			$relevantContent[$key] = $content[$key];
		}
		if (isset($content['config']['platform'])) {
			$relevantContent['config']['platform'] = $content['config']['platform'];
		}

		ksort($relevantContent);

		return md5(json_encode($relevantContent));
	}
}
