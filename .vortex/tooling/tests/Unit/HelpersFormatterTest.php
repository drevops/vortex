<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for output formatter functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[CoversFunction('DrevOps\VortexTooling\note')]
#[CoversFunction('DrevOps\VortexTooling\task')]
#[CoversFunction('DrevOps\VortexTooling\info')]
#[CoversFunction('DrevOps\VortexTooling\pass')]
#[CoversFunction('DrevOps\VortexTooling\fail_no_exit')]
#[CoversFunction('DrevOps\VortexTooling\fail')]
#[CoversFunction('DrevOps\VortexTooling\term_supports_color')]
#[Group('helpers')]
class HelpersFormatterTest extends UnitTestCase {

  #[DataProvider('dataProviderOutputFormatters')]
  public function testOutputFormatters(string $function, ?bool $is_tty, string $expected_output): void {
    // Mock posix_isatty() BEFORE loading helpers.php.
    // Only mock for functions that use term_supports_color (not 'note').
    if ($is_tty !== NULL) {
      // Set TERM to a valid terminal type (not 'dumb') so color check proceeds.
      $this->envSet('TERM', 'xterm-256color');
      $this->mockPosixIsatty($is_tty);
    }

    require_once __DIR__ . '/../../src/helpers.php';

    ob_start();
    $callable = 'DrevOps\\VortexTooling\\' . $function;
    // @phpstan-ignore-next-line argument.type
    call_user_func($callable, 'Test message %s', 'arg');
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertEquals($expected_output, $output);
  }

  public static function dataProviderOutputFormatters(): array {
    return [
      // Note - does not use term_supports_color, always plain output.
      'note' => ['note', NULL, "       Test message arg\n"],
      // Task - blue (34m). Announces only (no body), so the message is printed
      // verbatim and the extra argument is the ignored done message.
      'task, no color' => ['task', FALSE, "[TASK] Test message %s\n"],
      'task, with color' => ['task', TRUE, "\033[34m[TASK] Test message %s\033[0m\n"],
      // Info - cyan (36m).
      'info, no color' => ['info', FALSE, "[INFO] Test message arg\n"],
      'info, with color' => ['info', TRUE, "\033[36m[INFO] Test message arg\033[0m\n"],
      // Pass - green (32m).
      'pass, no color' => ['pass', FALSE, "[ OK ] Test message arg\n"],
      'pass, with color' => ['pass', TRUE, "\033[32m[ OK ] Test message arg\033[0m\n"],
      // Fail_no_exit - red (31m).
      'fail_no_exit, no color' => ['fail_no_exit', FALSE, "[FAIL] Test message arg\n"],
      'fail_no_exit, with color' => ['fail_no_exit', TRUE, "\033[31m[FAIL] Test message arg\033[0m\n"],
    ];
  }

  public function testTaskBodySuccess(): void {
    $this->envSet('TERM', 'dumb');

    require_once __DIR__ . '/../../src/helpers.php';

    // A string done message reports [ OK ] verbatim and returns the body value.
    ob_start();
    $result = \DrevOps\VortexTooling\task('Doing the thing.', 'Did the thing.', fn(): string => 'value');
    $output = ob_get_clean();

    $this->assertSame('value', $result);
    $this->assertEquals("[TASK] Doing the thing.\n[ OK ] Did the thing.\n", $output);

    // A closure done receives the body's return value to build the message.
    ob_start();
    $result = \DrevOps\VortexTooling\task('Doing the thing.', fn(string $value): string => sprintf('Did the thing with %s.', $value), fn(): string => 'value');
    $output = ob_get_clean();

    $this->assertSame('value', $result);
    $this->assertEquals("[TASK] Doing the thing.\n[ OK ] Did the thing with value.\n", $output);
  }

  public function testTaskBodyNonFatalFailure(): void {
    $this->envSet('TERM', 'dumb');

    require_once __DIR__ . '/../../src/helpers.php';

    ob_start();
    $result = \DrevOps\VortexTooling\task('Doing the thing.', 'Did the thing.', function (): void {
      throw new \RuntimeException('Something went wrong.');
    }, fatal: FALSE);
    $output = ob_get_clean();

    $this->assertNull($result);
    $this->assertEquals("[TASK] Doing the thing.\n[FAIL] Something went wrong.\n", $output);
  }

  public function testTaskBodyFatalFailure(): void {
    $this->envSet('TERM', 'dumb');
    $this->mockQuit(1);

    require_once __DIR__ . '/../../src/helpers.php';

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    try {
      ob_start();
      \DrevOps\VortexTooling\task('Doing the thing.', 'Did the thing.', function (): void {
        throw new \RuntimeException('Something went wrong.');
      });
    }
    finally {
      $output = ob_get_clean();
      $this->assertEquals("[TASK] Doing the thing.\n[FAIL] Something went wrong.\n", $output);
    }
  }

  #[DataProvider('dataProviderFail')]
  public function testFail(bool $is_tty, string $expected_output): void {
    // Set TERM to a valid terminal type and mock posix_isatty BEFORE loading.
    $this->envSet('TERM', 'xterm-256color');
    $this->mockPosixIsatty($is_tty);
    $this->mockQuit(1);

    require_once __DIR__ . '/../../src/helpers.php';

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    try {
      ob_start();
      \DrevOps\VortexTooling\fail('Test failure %s', 'message');
    }
    finally {
      $output = ob_get_clean();
      $this->assertEquals($expected_output, $output);
    }
  }

  public static function dataProviderFail(): array {
    return [
      'no color' => [FALSE, "[FAIL] Test failure message\n"],
      'with color' => [TRUE, "\033[31m[FAIL] Test failure message\033[0m\n"],
    ];
  }

  #[DataProvider('dataProviderTermSupportsColor')]
  public function testTermSupportsColor(string|bool $term_value, bool $expected): void {
    require_once __DIR__ . '/../../src/helpers.php';

    if ($term_value === FALSE) {
      $this->envUnset('TERM');
    }
    else {
      $this->envSet('TERM', (string) $term_value);
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
