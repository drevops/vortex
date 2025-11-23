<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use PHPUnit\Framework\AssertionFailedError;
use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use function DrevOps\VortexTooling\passthru;

/**
 * Self-tests for mocking of the passthru() function.
 *
 * We test mockPassthru() to ensure it returns the correct output and
 * exit code that we can capture and assert in tests.
 */
#[CoversClass(UnitTestCase::class)]
class MockPassthruSelfTest extends UnitTestCase {

  public function testMockPassthruSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => '',
      'result_code' => 0,
      'return' => NULL,
    ]);

    ob_start();
    $exit_code = NULL;
    $return = passthru('echo "success"', $exit_code);
    $output = ob_get_clean();

    $this->assertEquals('', $output);
    $this->assertEquals(0, $exit_code);
    $this->assertEquals(NULL, $return);
  }

  public function testMockPassthruCustomSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => 'Command output',
      'result_code' => 255,
      'return' => FALSE,
    ]);

    ob_start();
    $exit_code = NULL;
    $return = passthru('echo "success"', $exit_code);
    $output = ob_get_clean();

    $this->assertEquals('Command output', $output);
    $this->assertEquals(255, $exit_code);
    $this->assertEquals(FALSE, $return);
  }

  public function testMockPassthruDefaultsSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
    ]);

    ob_start();
    $exit_code = NULL;
    $return = passthru('echo "success"', $exit_code);
    $output = ob_get_clean();

    $this->assertEquals('', $output);
    $this->assertEquals(0, $exit_code);
    $this->assertEquals(NULL, $return);
  }

  public function testMockPassthruFailureArgumentExceptionCmd(): void {
    // Intentionally missing 'cmd' key.
    // @phpstan-ignore-next-line argument.type
    $this->mockPassthru([
      'return' => 'someval',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked passthru response must include "cmd" key to specify expected command.');

    try {
      ob_start();
      passthru('echo "success"');
    }
    finally {
      ob_end_clean();
    }
  }

  public function testMockPassthruFailureArgumentExceptionReturn(): void {
    // Intentionally incorrect type for 'return' key.
    // @phpstan-ignore-next-line argument.type
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'return' => 'someval',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked passthru response "return" key must be either NULL or FALSE, but got string.');

    try {
      ob_start();
      passthru('echo "success"');
    }
    finally {
      ob_end_clean();
    }
  }

  public function testMockPassthruFailureAssertUnexpectedCmd(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('passthru() called with unexpected command. Expected "echo "success"", got "echo "failure"".');

    try {
      ob_start();
      passthru('echo "failure"');
    }
    finally {
      ob_end_clean();
    }
  }

  public function testMockPassthruMultipleSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success1"',
      'output' => 'output 1',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "success2"',
      'output' => 'output 2',
      'result_code' => 22,
      'return' => FALSE,
    ]);

    ob_start();
    $exit_code1 = NULL;
    $return1 = passthru('echo "success1"', $exit_code1);
    $output1 = ob_get_clean();

    ob_start();
    $exit_code2 = NULL;
    $return2 = passthru('echo "success2"', $exit_code2);
    $output2 = ob_get_clean();

    $this->assertEquals('output 1', $output1);
    $this->assertEquals(11, $exit_code1);
    $this->assertEquals(NULL, $return1);

    $this->assertEquals('output 2', $output2);
    $this->assertEquals(22, $exit_code2);
    $this->assertEquals(FALSE, $return2);
  }

  public function testMockPassthruMultipleMoreCallsFailure(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success1"',
      'output' => 'output 1',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "success2"',
      'output' => 'output 2',
      'result_code' => 22,
      'return' => FALSE,
    ]);

    ob_start();
    $exit_code1 = NULL;
    $return1 = passthru('echo "success1"', $exit_code1);
    $output1 = ob_get_clean();

    ob_start();
    $exit_code2 = NULL;
    $return2 = passthru('echo "success2"', $exit_code2);
    $output2 = ob_get_clean();

    $this->assertEquals('output 1', $output1);
    $this->assertEquals(11, $exit_code1);
    $this->assertEquals(NULL, $return1);

    $this->assertEquals('output 2', $output2);
    $this->assertEquals(22, $exit_code2);
    $this->assertEquals(FALSE, $return2);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('passthru() called more times than mocked responses. Expected 2 call(s), but attempting call #3');

    try {
      ob_start();
      $exit_code3 = NULL;
      $return3 = passthru('echo "success3"', $exit_code3);
    }
    finally {
      $output3 = ob_get_clean();
    }
  }

  public function testMockPassthruMultipleLessCallsFailure(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success1"',
      'output' => 'output 1',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "success2"',
      'output' => 'output 2',
      'result_code' => 22,
      'return' => FALSE,
    ]);

    ob_start();
    $exit_code1 = NULL;
    $return1 = passthru('echo "success1"', $exit_code1);
    $output1 = ob_get_clean();

    $this->assertEquals('output 1', $output1);
    $this->assertEquals(11, $exit_code1);
    $this->assertEquals(NULL, $return1);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked passthru responses were consumed. Expected 2 call(s), but only 1 call(s) were made.');

    // Manually trigger the check that normally happens in tearDown().
    $this->mockPassthruAssertAllMocksConsumed();
  }

  public function testMockPassthruScriptPassingSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => 'success',
      'result_code' => 0,
      'return' => NULL,
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-passing');

    $this->assertStringContainsString('Script should return code 0', $output);
    $this->assertStringContainsString('Script actually returned code 0', $output);
  }

  public function testMockPassthruScriptPassingFailure(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => 'success',
      'result_code' => 1,
      'return' => NULL,
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-passing');

    $this->assertStringContainsString('Script should return code 0', $output);
    $this->assertStringNotContainsString('Script actually returned code 0', $output);
  }

  public function testMockPassthruScriptCustomSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => 'Custom output',
      'result_code' => 255,
      'return' => FALSE,
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-passing');

    $this->assertStringContainsString('Script should return code 0', $output);
    $this->assertStringContainsString('Script actually returned code 255', $output);
  }

  public function testMockPassthruScriptDefaultsSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-passing');

    $this->assertStringContainsString('Script should return code 0', $output);
    $this->assertStringContainsString('Script actually returned code 0', $output);
  }

  public function testMockPassthruScriptFailureArgumentExceptionCmd(): void {
    // Intentionally missing 'cmd' key.
    // @phpstan-ignore-next-line argument.type
    $this->mockPassthru([
      'return' => 'someval',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked passthru response must include "cmd" key to specify expected command.');

    $this->runScript('tests/Fixtures/test-passthru-passing');
  }

  public function testMockPassthruScriptFailureArgumentExceptionReturn(): void {
    // Intentionally incorrect type for 'return' key.
    // @phpstan-ignore-next-line argument.type
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'return' => 'someval',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked passthru response "return" key must be either NULL or FALSE, but got string.');

    $this->runScript('tests/Fixtures/test-passthru-passing');
  }

  public function testMockPassthruScriptPassingFailureAssertUnexpectedCmd(): void {
    $this->mockPassthru([
      'cmd' => 'echo "failure"',
      'output' => 'success',
      'result_code' => 1,
      'return' => NULL,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('passthru() called with unexpected command. Expected "echo "failure"", got "echo "success"".');

    $this->runScript('tests/Fixtures/test-passthru-passing');
  }

  public function testMockPassthruScriptMultipleSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "first"',
      'output' => 'first output',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "second"',
      'output' => 'second output',
      'result_code' => 22,
      'return' => FALSE,
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-multiple');

    $this->assertStringContainsString('Script will call passthru twice', $output);
    $this->assertStringContainsString('First call returned code 11', $output);
    $this->assertStringContainsString('Second call returned code 22', $output);
    $this->assertStringContainsString('Script completed', $output);
  }

  public function testMockPassthruScriptMultipleMoreCallsFailure(): void {
    $this->mockPassthru([
      'cmd' => 'echo "first"',
      'output' => 'first output',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('passthru() called more times than mocked responses. Expected 1 call(s), but attempting call #2');

    $this->runScript('tests/Fixtures/test-passthru-multiple');
  }

  public function testMockPassthruScriptMultipleLessCallsFailure(): void {
    $this->mockPassthru([
      'cmd' => 'echo "first"',
      'output' => 'first output',
      'result_code' => 11,
      'return' => NULL,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "second"',
      'output' => 'second output',
      'result_code' => 22,
      'return' => FALSE,
    ]);

    $this->mockPassthru([
      'cmd' => 'echo "third"',
      'output' => 'third output',
      'result_code' => 33,
      'return' => NULL,
    ]);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked passthru responses were consumed. Expected 3 call(s), but only 2 call(s) were made.');

    $this->runScript('tests/Fixtures/test-passthru-multiple');

    // Manually trigger the check that normally happens in tearDown().
    $this->mockPassthruAssertAllMocksConsumed();
  }

  public function testMockPassthruScriptFailingSuccess(): void {
    $this->mockPassthru([
      'cmd' => 'echo "success"',
      'output' => 'success',
      'result_code' => 1,
      'return' => NULL,
    ]);

    $output = $this->runScript('tests/Fixtures/test-passthru-failing');

    $this->assertStringContainsString('Script should return code 1', $output);
    $this->assertStringContainsString('Script actually returned code 1', $output);
  }

}
