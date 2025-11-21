<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for output formatter functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\note')]
#[CoversFunction('DrevOps\VortexTooling\task')]
#[CoversFunction('DrevOps\VortexTooling\info')]
#[CoversFunction('DrevOps\VortexTooling\pass')]
#[CoversFunction('DrevOps\VortexTooling\fail_no_exit')]
#[CoversFunction('DrevOps\VortexTooling\fail')]
#[CoversFunction('DrevOps\VortexTooling\_supports_color')]
class FormatterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load helpers to make functions available.
    require_once __DIR__ . '/../../src/helpers.php';
  }

  /**
   * Test output formatter functions.
   *
   * @param string $function
   *   Function name to test.
   * @param string $expected_prefix
   *   Expected output prefix (when colors are disabled).
   */
  #[DataProvider('providerOutputFormatters')]
  public function testOutputFormatters(string $function, string $expected_prefix): void {
    putenv('TERM=dumb');

    ob_start();
    $callable = 'DrevOps\\VortexTooling\\' . $function;
    // @phpstan-ignore-next-line argument.type
    call_user_func($callable, 'Test message %s', 'arg');
    $output = ob_get_clean();
    $this->assertIsString($output);

    if ($function === 'note') {
      $this->assertEquals("       Test message arg\n", $output);
    }
    else {
      $this->assertEquals($expected_prefix . "Test message arg\n", $output);
    }
  }

  /**
   * Data provider for testOutputFormatters().
   *
   * @return array<string, array<int, string>>
   *   Test cases.
   */
  public static function providerOutputFormatters(): array {
    return [
      'note' => ['note', '       '],
      'task' => ['task', '[TASK] '],
      'info' => ['info', '[INFO] '],
      'pass' => ['pass', '[ OK ] '],
      'fail_no_exit' => ['fail_no_exit', '[FAIL] '],
    ];
  }

  /**
   * Test output formatters with color support.
   *
   * @param string $function
   *   Function name to test.
   * @param string $expected_prefix
   *   Expected output prefix.
   */
  #[DataProvider('providerOutputFormattersWithColor')]
  public function testOutputFormattersWithColor(string $function, string $expected_prefix): void {
    putenv('TERM=xterm-256color');

    ob_start();
    $callable = 'DrevOps\\VortexTooling\\' . $function;
    // @phpstan-ignore-next-line argument.type
    call_user_func($callable, 'Test');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString($expected_prefix, $output);
    $this->assertStringContainsString('Test', $output);
  }

  /**
   * Data provider for testOutputFormattersWithColor().
   *
   * @return array<string, array<int, string>>
   *   Test cases.
   */
  public static function providerOutputFormattersWithColor(): array {
    return [
      'task' => ['task', '[TASK]'],
      'info' => ['info', '[INFO]'],
      'pass' => ['pass', '[ OK ]'],
    ];
  }

  /**
   * Test fail() function exits with code 1.
   */
  public function testFail(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    try {
      ob_start();
      \DrevOps\VortexTooling\fail('Test failure');
    }
    finally {
      ob_end_clean();
    }
  }

  /**
   * Test _supports_color() with various TERM values.
   *
   * @param string|false $term_value
   *   Value to set for TERM.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerSupportsColor')]
  public function testSupportsColor(string|bool $term_value, bool $expected): void {
    if ($term_value === FALSE) {
      putenv('TERM');
    }
    else {
      putenv('TERM=' . $term_value);
    }

    $result = \DrevOps\VortexTooling\_supports_color();
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testSupportsColor().
   *
   * @return array<string, array<string, mixed>>
   *   Test cases.
   */
  public static function providerSupportsColor(): array {
    return [
      'dumb terminal' => ['term_value' => 'dumb', 'expected' => FALSE],
      'no terminal' => ['term_value' => FALSE, 'expected' => FALSE],
    ];
  }

}
