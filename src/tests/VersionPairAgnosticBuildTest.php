<?php

/**
 * Step 2 tests for Story S1.2 — version-pair-agnostic build inputs.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class VersionPairAgnosticBuildTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function dockerfile()
	{
		return $this->repoDir() . '/docker/app/Dockerfile';
	}

	/**
	 * Must Support: "Build inputs that accept any combination of core
	 * version and runtime version"
	 *
	 * Developer Notes: "Keep the build recipe free of assumptions beyond
	 * the supplied version input"
	 */
	public function testBuildRecipeCarriesNoVersionAssumptionBeyondSuppliedInput()
	{
		$dockerfile = $this->dockerfile();
		$this->assertFileExists($dockerfile);
		$contents = file_get_contents($dockerfile);

		// Strip comment lines — the recipe's prose may cite versions; the
		// executable recipe may not.
		$recipe = preg_replace('/^\s*#.*$/m', '', $contents);

		// The only runtime-version literal is the ARG PHP_VERSION default:
		// remove that line, then no dotted version literal may remain
		$withoutArgLine = preg_replace('/^ARG\s+PHP_VERSION=\S+\s*$/m', '', $recipe);
		$this->assertSame(
			0,
			preg_match_all('/\d+\.\d+/', $withoutArgLine, $m),
			'Build recipe hard-codes a version literal beyond the ARG PHP_VERSION default: '
			. implode(', ', $m[0])
		);

		// The core version never enters the build recipe: no elgg/elgg
		// constraint and no 2.3.x literal outside comments — the core
		// arrives only via the mounted source tree
		$this->assertSame(
			0,
			preg_match('/elgg\/elgg|2\.3/', $recipe),
			'Build recipe references the core version; it must arrive only via the mounted source tree'
		);
	}

	/**
	 * Must Support: "Intermediate version combinations buildable from the
	 * same unedited files"
	 *
	 * Automates the static half of the manual verification "Confirm no
	 * committed file needed editing to accept the alternate version pair":
	 * every version reference in the app build chain must flow from the
	 * single PHP_VERSION environment input, so an alternate pair needs
	 * only an environment value — zero file edits.
	 */
	public function testSingleEnvVarDrivesAllVersionReferencesWithoutFileEdits()
	{
		$composeFile = $this->repoDir() . '/compose.yml';
		$this->assertFileExists($composeFile);
		$contents = file_get_contents($composeFile);

		// Strip comment lines — prose may cite versions; configuration may not
		$config = preg_replace('/^\s*#.*$/m', '', $contents);

		// Scope to the app service block: the dbm/cron image pins are
		// deliberate separate pins (Steps 5/3 scope), not the runtime input
		$this->assertSame(
			1,
			preg_match('/^  app:\s*$(.*?)^  dbm:/ms', $config, $block),
			'compose.yml has no recognizable app service block'
		);
		$appBlock = $block[1];

		// Exactly two interpolation sites consume PHP_VERSION: the build
		// arg and the image tag
		$this->assertSame(
			2,
			preg_match_all('/\$\{PHP_VERSION:-/', $appBlock),
			'compose.yml must consume PHP_VERSION at exactly the two build-chain sites (build.args + image tag)'
		);

		// Capture the committed default, remove every interpolation token,
		// then no dotted version literal may remain — anything left would
		// need a file edit to accept a different version
		$this->assertSame(
			1,
			preg_match('/\$\{PHP_VERSION:-([^\}]+)\}/', $appBlock, $m),
			'compose.yml does not interpolate PHP_VERSION with a committed default'
		);
		$default = $m[1];
		$withoutTokens = preg_replace('/\$\{PHP_VERSION:-[^\}]+\}/', '', $appBlock);
		$this->assertSame(
			0,
			preg_match_all('/\d+\.\d+/', $withoutTokens, $leftovers),
			'compose.yml app block hard-codes a version literal outside the PHP_VERSION interpolation: '
			. implode(', ', $leftovers[0])
		);

		// .env.example documents the same default as the build input
		$envExampleFile = $this->repoDir() . '/.env.example';
		$this->assertFileExists($envExampleFile);
		$envExample = file_get_contents($envExampleFile);
		$this->assertSame(
			1,
			preg_match('/^PHP_VERSION=' . preg_quote($default, '/') . '$/m', $envExample),
			'.env.example does not document the same PHP_VERSION default as compose.yml'
		);
	}
}
