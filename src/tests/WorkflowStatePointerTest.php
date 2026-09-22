<?php

class WorkflowStatePointerTest extends \PHPUnit_Framework_TestCase
{
	private function repoDir()
	{
		return dirname(dirname(__DIR__));
	}

	private function stateFile()
	{
		return $this->repoDir() . '/docs/workflow_state.md';
	}

	private function stateLines()
	{
		return $this->stateLinesAt($this->stateFile());
	}

	/**
	 * Lines of the file at $path with trailing blank lines removed.
	 * A missing or empty file yields an empty array, so every
	 * constraint check passes vacuously while the state file does
	 * not exist.
	 *
	 * @return string[]
	 */
	private function stateLinesAt($path)
	{
		if (!is_file($path)) {
			return array();
		}

		$contents = file_get_contents($path);

		if ($contents === false || $contents === '') {
			return array();
		}

		$lines = preg_split("/\r\n|\r|\n/", $contents);

		while (count($lines) > 0 && end($lines) === '') {
			array_pop($lines);
		}

		return $lines;
	}

	private function linesStartingWith(array $lines, $prefix)
	{
		$matches = array();

		foreach ($lines as $line) {
			if (strpos(ltrim($line), $prefix) === 0) {
				$matches[] = $line;
			}
		}

		return $matches;
	}

	private function characterCount($line)
	{
		return function_exists('mb_strlen') ? mb_strlen($line) : strlen($line);
	}

	public function testStateFileIsAtMostFifteenLines()
	{
		$this->assertLessThanOrEqual(
			15,
			count($this->stateLines()),
			'docs/workflow_state.md must be at most 15 lines.'
		);
	}

	public function testAtMostOneLastCompletedLine()
	{
		$this->assertLessThanOrEqual(
			1,
			count($this->linesStartingWith($this->stateLines(), '- Last completed:')),
			'docs/workflow_state.md must contain at most one "- Last completed:" line.'
		);
	}

	public function testLastCompletedLineIsAtMostFourHundredCharacters()
	{
		$lines = $this->linesStartingWith($this->stateLines(), '- Last completed:');

		if (count($lines) === 0) {
			$this->assertTrue(true); // no "- Last completed:" line: holds vacuously
			return;
		}

		foreach ($lines as $line) {
			$this->assertLessThanOrEqual(
				400,
				$this->characterCount($line),
				'The "- Last completed:" line must be at most 400 characters.'
			);
		}
	}

	public function testNoSnapshotLines()
	{
		$lines = $this->stateLines();

		foreach (array('- Files in context:', '- droppable', '- summaries only:') as $prefix) {
			$this->assertCount(
				0,
				$this->linesStartingWith($lines, $prefix),
				"docs/workflow_state.md must not contain a line starting with \"{$prefix}\"."
			);
		}
	}

	public function testAtMostOneReloadToResumeLine()
	{
		$this->assertLessThanOrEqual(
			1,
			count($this->linesStartingWith($this->stateLines(), '- Reload to resume:')),
			'docs/workflow_state.md must contain at most one "- Reload to resume:" line.'
		);
	}

	public function testReloadToResumePresentWheneverCommandLinePresent()
	{
		$lines = $this->stateLines();

		if (count($this->linesStartingWith($lines, '- Command:')) === 0) {
			$this->assertTrue(true); // no "- Command:" line: holds vacuously
			return;
		}

		$this->assertGreaterThanOrEqual(
			1,
			count($this->linesStartingWith($lines, '- Reload to resume:')),
			'A "- Command:" line requires at least one "- Reload to resume:" line.'
		);
	}

	public function testMissingStateFilePassesAllChecksVacuously()
	{
		$lines = $this->stateLinesAt($this->repoDir() . '/docs/workflow_state.missing');

		$this->assertSame(
			array(),
			$lines,
			'Expected an empty line set for a missing state file.'
		);
		$this->assertLessThanOrEqual(15, count($lines));
		$this->assertLessThanOrEqual(1, count($this->linesStartingWith($lines, '- Last completed:')));
		$this->assertCount(0, $this->linesStartingWith($lines, '- Files in context:'));
		$this->assertCount(0, $this->linesStartingWith($lines, '- droppable'));
		$this->assertCount(0, $this->linesStartingWith($lines, '- summaries only:'));
		$this->assertLessThanOrEqual(1, count($this->linesStartingWith($lines, '- Reload to resume:')));
		$this->assertCount(0, $this->linesStartingWith($lines, '- Command:'));
	}
}
