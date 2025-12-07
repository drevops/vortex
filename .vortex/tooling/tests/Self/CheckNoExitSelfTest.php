<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;

/**
 * Self-tests for check-exit-usage.php script.
 *
 * These tests verify that the exit() usage checker works correctly.
 */
class CheckNoExitSelfTest extends UnitTestCase {

  /**
   * Path to the check-exit-usage.php script.
   */
  protected string $scriptPath;

  /**
   * Temporary directory for test files.
   */
  protected string $testDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->scriptPath = dirname(__DIR__, 2) . '/check-no-exit.php';
    $this->testDir = self::$tmp . '/check-exit-test';
    mkdir($this->testDir);
    mkdir($this->testDir . '/src');
    mkdir($this->testDir . '/tests');
  }

  /**
   * Test that script detects exit() usage.
   */
  public function testDetectsExitUsage(): void {
    // Create a file with exit() usage.
    $test_file = $this->testDir . '/src/bad.php';
    file_put_contents($test_file, '<?php exit(1); ?>');

    // Run script from test directory.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $this->assertEquals(1, $exit_code, 'Script should exit with code 1 when exit() is found');
    $this->assertStringContainsString('Use quit() instead of exit()', implode("\n", $output));
    $this->assertStringContainsString('bad.php', implode("\n", $output));
  }

  /**
   * Test that script allows quit() usage.
   */
  public function testAllowsQuitUsage(): void {
    // Create a file with quit() usage.
    $test_file = $this->testDir . '/src/good.php';
    file_put_contents($test_file, '<?php quit(1); ?>');

    // Run script from test directory.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $this->assertEquals(0, $exit_code, 'Script should exit with code 0 when no exit() is found');
  }

  /**
   * Test that script detects multiple exit() patterns.
   */
  public function testDetectsMultipleExitPatterns(): void {
    // Create files with various exit patterns.
    file_put_contents($this->testDir . '/src/exit-with-code.php', '<?php exit(1); ?>');
    file_put_contents($this->testDir . '/src/exit-no-parens.php', '<?php if ($x) exit; ?>');
    file_put_contents($this->testDir . '/src/exit-with-space.php', '<?php exit (0); ?>');

    // Run script from test directory.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $output_str = implode("\n", $output);

    $this->assertEquals(1, $exit_code);
    $this->assertStringContainsString('exit-with-code.php', $output_str);
    $this->assertStringContainsString('exit-no-parens.php', $output_str);
    $this->assertStringContainsString('exit-with-space.php', $output_str);
  }

  /**
   * Test that script ignores exit in comments.
   */
  public function testIgnoresExitInComments(): void {
    // Create file with exit in comments.
    $test_file = $this->testDir . '/src/comments.php';
    file_put_contents($test_file, "<?php\n// Don't use exit(1)\n# exit; is bad\n/* exit() */\n?>");

    // Run script from test directory.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $this->assertEquals(0, $exit_code, 'Script should ignore exit() in comments');
  }

  /**
   * Test that script reports correct line numbers.
   */
  public function testReportsCorrectLineNumbers(): void {
    // Create file with exit on line 5.
    $test_file = $this->testDir . '/src/line-test.php';
    $content = "<?php\n\nfunction test() {\n\n  exit(1);\n}\n";
    file_put_contents($test_file, $content);

    // Run script from test directory.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $output_str = implode("\n", $output);
    $this->assertStringContainsString('line-test.php:5', $output_str);
  }

  /**
   * Test that script handles empty directories.
   */
  public function testHandlesEmptyDirectories(): void {
    // Run script with no PHP files.
    $output = [];
    $exit_code = 0;
    exec(sprintf('cd %s && php %s 2>&1', $this->testDir, $this->scriptPath), $output, $exit_code);

    $this->assertEquals(0, $exit_code);
  }

}
