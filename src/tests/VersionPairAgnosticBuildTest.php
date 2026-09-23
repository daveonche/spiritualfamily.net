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
}
