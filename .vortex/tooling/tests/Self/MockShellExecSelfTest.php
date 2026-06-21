<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use PHPUnit\Framework\AssertionFailedError;
use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use function DrevOps\VortexTooling\shell_exec;

/**
 * Self-tests for mocking of the shell_exec() function.
 *
 * We test mockShellExec() to ensure it returns the correct output
 * that we can capture and assert in tests.
 */
#[CoversClass(UnitTestCase::class)]
class MockShellExecSelfTest extends UnitTestCase {

  #[DataProvider('dataProviderMockShellExec')]
  public function testMockShellExec(string|null|false $mock_value): void {
    $this->mockShellExec($mock_value);

    $result = shell_exec('echo "test"');

    if ($mock_value === NULL) {
      $this->assertNull($result);
    }
    elseif ($mock_value === FALSE) {
      $this->assertFalse($result);
    }
    else {
      $this->assertEquals($mock_value, $result);
    }
  }

  public static function dataProviderMockShellExec(): array {
    return [
      'string output' => ['command output'],
      'null output' => [NULL],
      'false output' => [FALSE],
      'empty string output' => [''],
    ];
  }

  public function testMockShellExecMultipleSuccess(): void {
    $this->mockShellExecMultiple([
      ['value' => 'first output'],
      ['value' => 'second output'],
      ['value' => 'third output'],
    ]);

    $result1 = shell_exec('command 1');
    $result2 = shell_exec('command 2');
    $result3 = shell_exec('command 3');

    $this->assertEquals('first output', $result1);
    $this->assertEquals('second output', $result2);
    $this->assertEquals('third output', $result3);
  }

  public function testMockShellExecMultipleAddingSuccess(): void {
    $this->mockShellExec('first');
    $this->mockShellExec('second');
    $this->mockShellExec('third');

    $result1 = shell_exec('command 1');
    $result2 = shell_exec('command 2');
    $result3 = shell_exec('command 3');

    $this->assertEquals('first', $result1);
    $this->assertEquals('second', $result2);
    $this->assertEquals('third', $result3);
  }

  public function testMockShellExecMultipleMixedSuccess(): void {
    $this->mockShellExec('first');
    $this->mockShellExecMultiple([
      ['value' => 'second'],
      ['value' => NULL],
    ]);
    $this->mockShellExec('fourth');

    $result1 = shell_exec('command 1');
    $result2 = shell_exec('command 2');
    $result3 = shell_exec('command 3');
    $result4 = shell_exec('command 4');

    $this->assertEquals('first', $result1);
    $this->assertEquals('second', $result2);
    $this->assertNull($result3);
    $this->assertEquals('fourth', $result4);
  }

  public function testMockShellExecMoreCallsFailure(): void {
    $this->mockShellExec('output');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('shell_exec() called more times than mocked responses. Expected 1 call(s), but attempting call #2.');

    shell_exec('command 1');
    shell_exec('command 2');
  }

  public function testMockShellExecMoreCallsMultipleFailure(): void {
    $this->mockShellExecMultiple([
      ['value' => 'first'],
      ['value' => 'second'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('shell_exec() called more times than mocked responses. Expected 2 call(s), but attempting call #3.');

    shell_exec('command 1');
    shell_exec('command 2');
    shell_exec('command 3');
  }

  public function testMockShellExecLessCallsFailure(): void {
    $this->mockShellExecMultiple([
      ['value' => 'first'],
      ['value' => 'second'],
      ['value' => 'third'],
    ]);

    shell_exec('command 1');
    shell_exec('command 2');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked shell_exec responses were consumed. Expected 3 call(s), but only 2 call(s) were made.');

    $this->mockShellExecAssertAllMocksConsumed();
  }

  public function testMockShellExecLessCallsSingleFailure(): void {
    $this->mockShellExec('output');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked shell_exec responses were consumed. Expected 1 call(s), but only 0 call(s) were made.');

    $this->mockShellExecAssertAllMocksConsumed();
  }

  #[DataProvider('dataProviderMockShellExecScriptPassing')]
  public function testMockShellExecScriptPassing(string|null|false $mock_value, string $expected): void {
    $this->mockShellExec($mock_value);

    $output = $this->runScript('tests/Fixtures/test-shell-exec-passing');

    $this->assertStringContainsString('Script will call shell_exec', $output);
    $this->assertStringContainsString($expected, $output);
  }

  public static function dataProviderMockShellExecScriptPassing(): array {
    return [
      'string output' => ['command output', 'Shell output: command output'],
      'null output' => [NULL, 'Shell output is null'],
      'false output' => [FALSE, 'Shell output is false'],
      'empty string output' => ['', 'Shell output is empty'],
    ];
  }

  public function testMockShellExecScriptMultipleSuccess(): void {
    $this->mockShellExecMultiple([
      ['value' => 'first output'],
      ['value' => 'second output'],
      ['value' => 'third output'],
    ]);

    $output = $this->runScript('tests/Fixtures/test-shell-exec-multiple');

    $this->assertStringContainsString('First: first output', $output);
    $this->assertStringContainsString('Second: second output', $output);
    $this->assertStringContainsString('Third: third output', $output);
  }

  public function testMockShellExecScriptMoreCallsFailure(): void {
    $this->mockShellExec('output');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('shell_exec() called more times than mocked responses. Expected 1 call(s), but attempting call #2.');

    $this->runScript('tests/Fixtures/test-shell-exec-multiple');
  }

  public function testMockShellExecScriptLessCallsFailure(): void {
    $this->mockShellExecMultiple([
      ['value' => 'first'],
      ['value' => 'second'],
      ['value' => 'third'],
      ['value' => 'fourth'],
    ]);

    $this->runScript('tests/Fixtures/test-shell-exec-multiple');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked shell_exec responses were consumed. Expected 4 call(s), but only 3 call(s) were made.');

    $this->mockShellExecAssertAllMocksConsumed();
  }

}
