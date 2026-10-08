<?php

/**
 * Step 1 tests for Story S1.4 — dropped-plugins disposition list.
 *
 * Verifies that docs/dropped_plugins.md identifies every plugin directory
 * present in the codebase (including inactive ones), assigns exactly one
 * disposition per plugin, and reconciles its declared counts.
 *
 * Whether the removals recorded in the list have actually been executed is
 * the scope of Steps 2-4 (and their own tests), not Step 1; these tests
 * assert the list itself is complete, unambiguous, and internally
 * consistent.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class DroppedPluginsDispositionTest extends \PHPUnit_Framework_TestCase
{
	private function srcDir()
	{
		return dirname(__DIR__);
	}

	/** Repository root, resolved from this file's location. */
	private function repoDir()
	{
		return dirname($this->srcDir());
	}

	/** The plugin directory the disposition list must cover. */
	private function modDir()
	{
		return $this->srcDir() . '/mod';
	}

	/** Repository-root docs/dropped_plugins.md, resolved from this file's location. */
	private function dispositionFile()
	{
		return $this->repoDir() . '/docs/dropped_plugins.md';
	}

	private function dispositionDoc()
	{
		return file_get_contents($this->dispositionFile());
	}

	/** Repository-root docs/tech_stack.md, resolved from this file's location. */
	private function techStackDoc()
	{
		return file_get_contents($this->repoDir() . '/docs/tech_stack.md');
	}

	/**
	 * Returns the body of the "## <title>" section of docs/tech_stack.md,
	 * up to the next same-level heading or end of file; '' when absent.
	 */
	private function techStackSection($doc, $title)
	{
		$start = strpos($doc, '## ' . $title);
		if ($start === false) {
			return '';
		}
		$next = strpos($doc, "\n## ", $start + 1);
		if ($next === false) {
			return substr($doc, $start);
		}
		return substr($doc, $start, $next - $start);
	}

	/**
	 * Returns the body of the "## Dispositions" section, up to the next
	 * same-level heading or end of file; '' when absent.
	 */
	private function dispositionsSection($doc)
	{
		$start = strpos($doc, '## Dispositions');
		if ($start === false) {
			return '';
		}
		$next = strpos($doc, "\n## ", $start + 1);
		if ($next === false) {
			return substr($doc, $start);
		}
		return substr($doc, $start, $next - $start);
	}

	/**
	 * Returns each "### <title> (N)" category within the dispositions
	 * section as title => array('count' => N, 'ids' => plugin IDs).
	 */
	private function dispositionCategories($doc)
	{
		$section = $this->dispositionsSection($doc);
		$categories = array();

		preg_match_all('/^### (.+?) \((\d+)\)\s*$/m', $section, $headings, PREG_OFFSET_CAPTURE);
		foreach ($headings[1] as $i => $titleMatch) {
			$bodyStart = $headings[0][$i][1] + strlen($headings[0][$i][0]);
			$bodyEnd = isset($headings[0][$i + 1])
				? $headings[0][$i + 1][1]
				: strlen($section);
			$categories[trim($titleMatch[0])] = array(
				'count' => (int)$headings[2][$i][0],
				'ids' => $this->pluginIdsInCategory(
					substr($section, $bodyStart, $bodyEnd - $bodyStart)
				),
			);
		}

		return $categories;
	}

	/**
	 * Returns the plugin IDs recorded in a category body: first-column
	 * table entries (header and separator rows skipped), or the
	 * comma-separated list when the category records no table.
	 */
	private function pluginIdsInCategory($body)
	{
		$ids = array();

		if (preg_match_all('/^\|(.+)\|\s*$/m', $body, $rows)) {
			foreach ($rows[1] as $row) {
				$cells = explode('|', $row);
				$first = trim($cells[0]);
				if ($first === '' || $first === 'Plugin' || strpos($first, '---') === 0) {
					continue;
				}
				$ids[] = $first;
			}
		}

		if ($ids) {
			return $ids;
		}

		// No table: the kept category records a comma-separated list.
		$listLine = '';
		foreach (preg_split('/\r?\n/', $body) as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '|') {
				continue;
			}
			if (strpos($line, ',') !== false && strlen($line) > strlen($listLine)) {
				$listLine = $line;
			}
		}
		foreach (explode(',', $listLine) as $id) {
			$id = trim($id);
			if ($id !== '') {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Returns the raw body of one "### <title> (N)" category within the
	 * dispositions section, up to the next category heading; '' when
	 * absent.
	 */
	private function categoryBody($doc, $title)
	{
		$section = $this->dispositionsSection($doc);
		$start = strpos($section, '### ' . $title);
		if ($start === false) {
			return '';
		}
		$next = strpos($section, "\n### ", $start + 1);
		if ($next === false) {
			return substr($section, $start);
		}
		return substr($section, $start, $next - $start);
	}

	/**
	 * Returns the data rows of a category table as arrays of trimmed cell
	 * values; header and separator rows are skipped.
	 */
	private function tableRows($body)
	{
		$rows = array();
		if (!preg_match_all('/^\|(.+)\|\s*$/m', $body, $matches)) {
			return $rows;
		}
		foreach ($matches[1] as $row) {
			$cells = array_map('trim', explode('|', $row));
			$first = $cells[0];
			if ($first === '' || $first === 'Plugin' || strpos($first, '---') === 0) {
				continue;
			}
			$rows[] = $cells;
		}

		return $rows;
	}

	/**
	 * Returns the plugin directories currently present in src/mod: every
	 * subdirectory carrying a plugin marker (manifest.xml or
	 * elgg-plugin.php).
	 */
	private function currentPluginDirs()
	{
		$dirs = array();
		foreach (scandir($this->modDir()) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $this->modDir() . '/' . $entry;
			if (!is_dir($path)) {
				continue;
			}
			if (file_exists($path . '/manifest.xml') || file_exists($path . '/elgg-plugin.php')) {
				$dirs[] = $entry;
			}
		}
		sort($dirs);

		return $dirs;
	}

	/**
	 * Must Support: "Identify every third-party and legacy plugin present
	 * in the codebase, including inactive ones"
	 */
	public function testEveryPluginDirectoryIsIdentifiedInDispositionList()
	{
		$this->assertFileExists($this->dispositionFile());

		$doc = $this->dispositionDoc();
		$categories = $this->dispositionCategories($doc);
		$this->assertNotEmpty($categories, 'No disposition categories found');

		// The declared src/mod scope total must equal the sum of the
		// per-category declared counts.
		$this->assertSame(
			1,
			preg_match('/`src\/mod` \((\d+) total/', $doc, $scope),
			'Scope line does not declare a src/mod plugin total'
		);
		$declaredTotal = 0;
		foreach ($categories as $category) {
			$declaredTotal += $category['count'];
		}
		$this->assertSame(
			(int)$scope[1],
			$declaredTotal,
			'Declared src/mod total does not equal the sum of the category counts'
		);

		// Exactly one identification per plugin: the IDs across all
		// disposition tables plus the kept list must equal the declared
		// total, with no plugin identified twice.
		$ids = array();
		foreach ($categories as $category) {
			foreach ($category['ids'] as $id) {
				$ids[] = $id;
			}
		}
		$this->assertSame(
			(int)$scope[1],
			count($ids),
			'Number of identified plugins does not match the declared total'
		);
		$this->assertSame(
			$ids,
			array_values(array_unique($ids)),
			'A plugin is identified in more than one disposition category'
		);

		// Every plugin directory currently present in the codebase must be
		// identified in the list. Listed plugins already removed by Steps
		// 2-4 are legitimately absent from src/mod, so this check runs in
		// this direction only.
		$missing = array_diff($this->currentPluginDirs(), array_unique($ids));
		$this->assertSame(
			array(),
			array_values($missing),
			'Plugin directories present in src/mod but missing from the disposition list'
		);
	}

	/**
	 * Must Support: "Record, for each plugin, whether an upgraded
	 * replacement is provisioned through the project's dependency
	 * mechanism or it must be installed manually in a later sprint"
	 */
	public function testDispositionsMatchRecordedReplacementDecisions()
	{
		$doc = $this->dispositionDoc();
		$techStack = $this->techStackDoc();

		// Composer-replacement rows: the recorded package must be named in
		// docs/tech_stack.md. A "not yet recorded" package is permitted
		// only when the row defers identification to a recorded trigger.
		$composerBody = $this->categoryBody($doc, 'Composer replacement at Elgg 7');
		$this->assertNotSame('', $composerBody, 'No "Composer replacement at Elgg 7" category found');

		foreach ($this->tableRows($composerBody) as $row) {
			$pluginId = $row[0];
			$this->assertArrayHasKey(2, $row, "Row for $pluginId lacks a replacement-package cell");

			$package = trim($row[2], '` ');
			if ($package === 'not yet recorded') {
				$this->assertArrayHasKey(3, $row, "Row for $pluginId lacks a Notes cell");
				$this->assertContains(
					'S1.14',
					$row[3],
					"$pluginId records no replacement package and no deferred-identification trigger"
				);
				continue;
			}

			// Strip the version suffix: "vendor/package v1.2" -> "vendor/package".
			$packageName = preg_replace('/\s+v?\S+$/', '', $package);
			$this->assertContains(
				$packageName,
				$techStack,
				"Replacement package for $pluginId is not recorded in docs/tech_stack.md"
			);
		}

		// Manual-reinstall rows: every plugin must appear in the tech
		// stack's Manual Installs section.
		$manualBody = $this->categoryBody($doc, 'Manual reinstall in a later sprint');
		$this->assertNotSame('', $manualBody, 'No "Manual reinstall in a later sprint" category found');

		$manualSection = $this->techStackSection($techStack, 'Manual Installs');
		$this->assertNotSame('', $manualSection, 'docs/tech_stack.md lacks a "Manual Installs" section');

		foreach ($this->tableRows($manualBody) as $row) {
			$pluginId = $row[0];
			$this->assertContains(
				$pluginId,
				$manualSection,
				"Manual-reinstall plugin $pluginId is not recorded in the tech stack's Manual Installs section"
			);
		}
	}
}
