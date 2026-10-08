<?php

/**
 * Step 2 tests for Story S1.4 — legacy theme stack drop.
 *
 * Verifies that every plugin recorded "Removed in Step 2" in
 * docs/dropped_plugins.md is absent from src/mod — no directory, no
 * plugin marker (manifest.xml or elgg-plugin.php).
 *
 * Whether the site renders on default bundled views after removal is the
 * story's manual verification and is not asserted here. Removals recorded
 * for Steps 3-4 are out of scope for this suite.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class LegacyThemeStackRemovalTest extends \PHPUnit_Framework_TestCase
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

	/**
	 * Returns the body of the "## <title>" section of a document, up to
	 * the next same-level heading or end of file; '' when absent.
	 */
	private function docSection($doc, $title)
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
		return $this->docSection($doc, 'Dispositions');
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
	 * Returns the plugin IDs whose disposition rows record "Removed in
	 * Step 2" (the legacy theme stack), parsed from the
	 * "Permanently dropped — no replacement" category table.
	 */
	private function stepTwoRemovals()
	{
		$body = $this->categoryBody(
			$this->dispositionDoc(),
			'Permanently dropped — no replacement'
		);
		$ids = array();
		foreach ($this->tableRows($body) as $row) {
			if (isset($row[1]) && trim($row[1]) === 'Step 2') {
				$ids[] = $row[0];
			}
		}
		return $ids;
	}

	/**
	 * Returns the Step 2 removal rows as plugin ID => Notes cell, parsed
	 * from the "Permanently dropped — no replacement" category table.
	 */
	private function stepTwoRemovalNotes()
	{
		$body = $this->categoryBody(
			$this->dispositionDoc(),
			'Permanently dropped — no replacement'
		);
		$notes = array();
		foreach ($this->tableRows($body) as $row) {
			if (isset($row[1]) && trim($row[1]) === 'Step 2') {
				$notes[$row[0]] = isset($row[2]) ? $row[2] : '';
			}
		}
		return $notes;
	}

	/** The S1.4 story block of docs/sprints/sprint_1_stories.md. */
	private function sprintStorySection()
	{
		$doc = file_get_contents($this->repoDir() . '/docs/sprints/sprint_1_stories.md');
		$start = strpos($doc, 'Story S1.4:');
		if ($start === false) {
			return '';
		}
		$next = strpos($doc, 'Story S1.5:', $start + 1);
		if ($next === false) {
			return substr($doc, $start);
		}
		return substr($doc, $start, $next - $start);
	}

	/**
	 * Must Support: "Remove the legacy theme plugins identified in the
	 * disposition list"
	 */
	public function testStepTwoRemovalsAreAbsentFromMod()
	{
		$this->assertFileExists($this->dispositionFile());

		$removals = $this->stepTwoRemovals();
		$this->assertNotEmpty(
			$removals,
			'No "Removed in Step 2" rows found in the disposition list'
		);

		foreach ($removals as $pluginId) {
			$this->assertFalse(
				is_dir($this->modDir() . '/' . $pluginId),
				"Legacy theme plugin directory $pluginId still present in src/mod"
			);
			$this->assertFileNotExists(
				$this->modDir() . '/' . $pluginId . '/manifest.xml'
			);
			$this->assertFileNotExists(
				$this->modDir() . '/' . $pluginId . '/elgg-plugin.php'
			);
		}
	}

	/**
	 * Must Support: "Remove the legacy theme plugins identified in the
	 * disposition list" — the Step 2 removal set must be exactly the
	 * legacy theme stack, and every row must cite REQ-FR-TR-1.
	 */
	public function testStepTwoRemovalSetIsExactlyLegacyThemeStack()
	{
		$notes = $this->stepTwoRemovalNotes();
		$this->assertNotEmpty(
			$notes,
			'No "Removed in Step 2" rows found in the disposition list'
		);

		// The Step 2 removal set must be exactly the legacy theme stack.
		$removals = array_keys($notes);
		sort($removals);
		$this->assertSame(
			array('aalborg_theme', 'l1', 'mrdave_aalborg', 'paul_override'),
			$removals,
			'The "Removed in Step 2" set does not match the legacy theme stack'
		);

		// Every Step 2 row must cite the legacy theme requirement.
		foreach ($notes as $pluginId => $note) {
			$this->assertContains(
				'REQ-FR-TR-1',
				$note,
				"Step 2 removal row for $pluginId does not cite REQ-FR-TR-1"
			);
		}

		// The sprint story's legacy-theme-stack criterion must name the
		// stack and cite the requirement.
		$story = $this->sprintStorySection();
		$this->assertNotSame('', $story, 'S1.4 story not found in the sprint stories document');
		$this->assertContains('Legacy theme stack dropped:', $story);
		$this->assertContains('aalborg_theme', $story);
		$this->assertContains('aalborg extras/L1', $story);
		$this->assertContains('paul_override', $story);
		$this->assertContains('REQ-FR-TR-1', $story);

		// Coverage: each story-named stack member must be covered by the
		// Step 2 removal set — "aalborg extras/L1" via l1, whose notes
		// record it.
		$this->assertArrayHasKey('aalborg_theme', $notes);
		$this->assertArrayHasKey('paul_override', $notes);
		$this->assertArrayHasKey('l1', $notes);
		$this->assertContains('aalborg extras/L1', $notes['l1']);
	}
}
