<?php

/**
 * Step 4 tests for Story S1.1 — production inventory.
 *
 * Verifies that docs/baseline/inventory.md stores the captured production
 * database size, active plugin set, key settings, and data directory
 * summary as the migration starting point.
 *
 * Whether the captured values are still true of live production is the
 * story's manual verification (and the S1.3/S1.4 cross-checks), not unit
 * scope; these tests assert structure, completeness, and internal
 * consistency of the stored inventory.
 *
 * PHPUnit 4.8: class name must match the file name and extend the
 * un-namespaced \PHPUnit_Framework_TestCase.
 */
class BaselineInventoryTest extends \PHPUnit_Framework_TestCase
{
	private function srcDir()
	{
		return dirname(__DIR__);
	}

	/** Repository-root docs/baseline/inventory.md, resolved from this file's location. */
	private function inventoryFile()
	{
		return dirname($this->srcDir()) . '/docs/baseline/inventory.md';
	}

	private function inventoryDoc()
	{
		return file_get_contents($this->inventoryFile());
	}

	/**
	 * Returns the body of a "## <title>" section, up to the next
	 * same-level heading or end of file; null when absent.
	 */
	private function section($doc, $title)
	{
		$start = strpos($doc, '## ' . $title);
		if ($start === false) {
			return null;
		}
		$next = strpos($doc, "\n## ", $start + 1);
		if ($next === false) {
			return substr($doc, $start);
		}
		return substr($doc, $start, $next - $start);
	}

	/**
	 * Must Support: "A capture of the production database size, active
	 * plugin set, and key settings in storable form" (database size)
	 */
	public function testInventoryCapturesDatabaseSize()
	{
		$doc = $this->inventoryDoc();
		$section = $this->section($doc, 'Database Inventory');
		$this->assertNotNull($section, 'No "Database Inventory" section found');

		// Size subsection with a per-schema table.
		$this->assertContains('### Size', $section, 'Database Inventory lacks a Size subsection');

		// The in-scope production schema must have a row with a size.
		$this->assertSame(
			1,
			preg_match('/^\|\s*`spirihf6_elgg114`\s*\|\s*([\d.]+)\s*\|/m', $section, $row),
			'No size row for the in-scope production schema spirihf6_elgg114'
		);
		$this->assertGreaterThan(0, (float)$row[1], 'Production database size must be a positive number');

		// The declared total must match the in-scope row.
		$this->assertSame(
			1,
			preg_match('/The Elgg production database is ([\d.]+) MB/', $section, $total),
			'No declared total for the Elgg production database'
		);
		$this->assertSame(
			$row[1],
			$total[1],
			'Declared database total does not match the in-scope schema row'
		);
	}

	/**
	 * Must Support: "A capture of the production database size, active
	 * plugin set, and key settings in storable form" (active plugin set)
	 */
	public function testInventoryCapturesActivePluginSet()
	{
		$doc = $this->inventoryDoc();
		$section = $this->section($doc, 'Active Plugin Set');
		$this->assertNotNull($section, 'No "Active Plugin Set" section found');

		// Declared count.
		$this->assertSame(
			1,
			preg_match('/(\d+) plugins active at capture/', $section, $declared),
			'No declared active plugin count'
		);

		// Table rows: | # | Plugin ID | GUID | Activated |
		$this->assertGreaterThan(
			0,
			preg_match_all('/^\|\s*(\d+)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|/m', $section, $rows),
			'No plugin rows found'
		);

		$this->assertSame(
			(int)$declared[1],
			count($rows[1]),
			'Declared active plugin count does not match the number of listed rows'
		);

		// Sequential numbering 1..N.
		$this->assertSame(
			range(1, count($rows[1])),
			array_map('intval', $rows[1]),
			'Plugin rows are not numbered sequentially'
		);

		// Every row carries a plugin ID and a GUID.
		foreach ($rows[2] as $index => $pluginId) {
			$this->assertNotSame('', $pluginId, 'Plugin row ' . ($index + 1) . ' lacks a plugin ID');
			$this->assertNotSame('', $rows[3][$index], 'Plugin row ' . ($index + 1) . ' lacks a GUID');
		}
	}

	/**
	 * Must Support: "A capture of the production database size, active
	 * plugin set, and key settings in storable form" (key settings)
	 */
	public function testInventoryCapturesKeySettings()
	{
		$doc = $this->inventoryDoc();
		$section = $this->section($doc, 'Site Settings');
		$this->assertNotNull($section, 'No "Site Settings" section found');

		$requiredSettings = array(
			'Language',
			'Default access',
			'Allow registration',
			'Walled garden',
			'Dataroot',
		);
		foreach ($requiredSettings as $setting) {
			$this->assertSame(
				1,
				preg_match('/^\|\s*' . preg_quote($setting, '/') . '\s*\|/m', $section),
				'Site Settings table lacks a row for "' . $setting . '"'
			);
		}
	}

	/**
	 * Must Support: "A measurement and content summary of the data
	 * directory"
	 */
	public function testInventoryCapturesDataDirectorySummary()
	{
		$doc = $this->inventoryDoc();
		$section = $this->section($doc, 'Data Directory Inventory');
		$this->assertNotNull($section, 'No "Data Directory Inventory" section found');

		$this->assertContains('### Totals', $section, 'Data Directory Inventory lacks a Totals subsection');

		// Total size in MB.
		$this->assertSame(
			1,
			preg_match('/^\|\s*Total size\s*\|\s*([\d,]+(?:\.\d+)?)\s*MB/m', $section, $size),
			'No "Total size" measurement in MB'
		);
		$this->assertGreaterThan(
			0,
			(float)str_replace(',', '', $size[1]),
			'Data directory total size must be positive'
		);

		// Total file count.
		$this->assertSame(
			1,
			preg_match('/^\|\s*Total files\s*\|\s*([\d,]+)\s*\|/m', $section, $files),
			'No "Total files" count'
		);
		$this->assertGreaterThan(
			0,
			(int)str_replace(',', '', $files[1]),
			'Data directory file count must be positive'
		);

		// Content summary: file-type breakdown and largest buckets.
		$this->assertContains('### File Types', $section, 'No file-type breakdown');
		$this->assertSame(
			1,
			preg_match('/^\|\s*jpg\s*\|/m', $section),
			'File-type breakdown lacks the dominant jpg type'
		);

		$this->assertContains('### Largest Content Buckets', $section, 'No largest-bucket summary');
		$this->assertGreaterThan(
			0,
			preg_match_all('/^\|\s*`[^`]+`\s*\|\s*([\d.]+)\s*\|/m', $section, $buckets),
			'No largest-bucket rows'
		);
		$this->assertGreaterThan(0, (float)$buckets[1][0], 'Largest-bucket sizes must be positive');
	}

	/**
	 * Must Support: "Stored inventory results serving as the migration
	 * starting point"
	 */
	public function testInventoryStoredAsMigrationStartingPoint()
	{
		$inventoryFile = $this->inventoryFile();
		$this->assertFileExists($inventoryFile);

		$doc = $this->inventoryDoc();

		// Self-identifies as the S1.1 Step 4 migration starting point.
		$this->assertContains('Story S1.1 Step 4', $doc, 'Inventory does not identify its story step');
		$this->assertContains('migration starting point', $doc, 'Inventory does not state its role');

		// Capture details with a date.
		$capture = $this->section($doc, 'Capture Details');
		$this->assertNotNull($capture, 'No "Capture Details" section');
		$this->assertSame(
			1,
			preg_match('/Capture date:\s*\d{4}-\d{2}-\d{2}/', $capture),
			'Capture Details lacks a capture date'
		);

		// Read-only capture statement.
		$this->assertContains('Production was not modified', $doc, 'Inventory does not state production was untouched');

		// Cross-reference to the pinned baseline.
		$this->assertContains(
			'docs/baseline/baseline.md',
			$doc,
			'Inventory does not cross-reference the pinned baseline'
		);
	}
}
