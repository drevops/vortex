<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Self-tests for captureOutput() method.
 */
#[CoversClass(UnitTestCase::class)]
class CaptureOutputSelfTest extends UnitTestCase {

  #[DataProvider('dataProviderCaptureOutput')]
  public function testCaptureOutput(callable $callback, string $expected_output): void {
    $output = $this->captureOutput($callback);
    $this->assertEquals($expected_output, $output);
  }

  public static function dataProviderCaptureOutput(): array {
    return [
      'simple output' => [
        function (): void {
          echo 'Hello World';
        },
        'Hello World',
      ],
      'multiple echo statements' => [
        function (): void {
          echo 'Line 1';
          echo "\n";
          echo 'Line 2';
        },
        "Line 1\nLine 2",
      ],
      'print statement' => [
        function (): void {
          print 'Printed output';
        },
        'Printed output',
      ],
      'empty output' => [
        function (): void {
          // No output.
        },
        '',
      ],
      'printf statement' => [
        function (): void {
          printf('Value: %d', 42);
        },
        'Value: 42',
      ],
    ];
  }

  public function testCaptureOutputRethrowsException(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Test exception');

    $this->captureOutput(function (): void {
      echo 'Output before exception';
      throw new \RuntimeException('Test exception');
    });
  }

  public function testCaptureOutputCleansBufferOnException(): void {
    // Start output buffering to check it's not corrupted.
    ob_start();
    echo 'Outer buffer content';

    try {
      $this->captureOutput(function (): void {
        echo 'Inner buffer content';
        throw new \RuntimeException('Test exception');
      });
    }
    catch (\RuntimeException) {
      // Expected exception.
    }

    // Outer buffer should still be intact.
    $outer_content = ob_get_clean();
    $this->assertEquals('Outer buffer content', $outer_content);
  }

}
