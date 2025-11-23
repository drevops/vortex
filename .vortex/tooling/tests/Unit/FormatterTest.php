<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

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
#[CoversFunction('DrevOps\VortexTooling\term_supports_color')]
#[Group('helpers')]
class FormatterTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderOutputFormatters')]
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

  public static function dataProviderOutputFormatters(): array {
    return [
      'note' => ['note', '       '],
      'task' => ['task', '[TASK] '],
      'info' => ['info', '[INFO] '],
      'pass' => ['pass', '[ OK ] '],
      'fail_no_exit' => ['fail_no_exit', '[FAIL] '],
    ];
  }

  #[DataProvider('dataProviderOutputFormattersWithColor')]
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

  public static function dataProviderOutputFormattersWithColor(): array {
    return [
      'task' => ['task', '[TASK]'],
      'info' => ['info', '[INFO]'],
      'pass' => ['pass', '[ OK ]'],
    ];
  }

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

  #[DataProvider('dataProviderTermSupportsColor')]
  public function testTermSupportsColor(string|bool $term_value, bool $expected): void {
    if ($term_value === FALSE) {
      putenv('TERM');
    }
    else {
      putenv('TERM=' . $term_value);
    }

    $result = \DrevOps\VortexTooling\term_supports_color();
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderTermSupportsColor(): array {
    return [
      'dumb terminal' => ['term_value' => 'dumb', 'expected' => FALSE],
      'no terminal' => ['term_value' => FALSE, 'expected' => FALSE],
    ];
  }

}
