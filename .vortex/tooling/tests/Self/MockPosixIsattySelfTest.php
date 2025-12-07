<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Self-tests for mocking of the posix_isatty() function.
 *
 * We test mockPosixIsatty() to ensure it returns the correct boolean value
 * that we can use to control terminal color detection in tests.
 */
#[CoversClass(UnitTestCase::class)]
class MockPosixIsattySelfTest extends UnitTestCase {

  #[DataProvider('dataProviderMockPosixIsatty')]
  public function testMockPosixIsatty(bool $mock_value): void {
    $this->mockPosixIsatty($mock_value);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result = \DrevOps\VortexTooling\posix_isatty(STDOUT);

    $this->assertEquals($mock_value, $result);
  }

  public static function dataProviderMockPosixIsatty(): array {
    return [
      'returns true' => [TRUE],
      'returns false' => [FALSE],
    ];
  }

  public function testMockPosixIsattyMultipleSuccess(): void {
    $this->mockPosixIsatty(TRUE);
    $this->mockPosixIsatty(FALSE);
    $this->mockPosixIsatty(TRUE);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result1 = \DrevOps\VortexTooling\posix_isatty(STDOUT);
    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result2 = \DrevOps\VortexTooling\posix_isatty(STDOUT);
    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result3 = \DrevOps\VortexTooling\posix_isatty(STDOUT);

    $this->assertTrue($result1);
    $this->assertFalse($result2);
    $this->assertTrue($result3);
  }

  public function testMockPosixIsattyMoreCallsFailure(): void {
    $this->mockPosixIsatty(TRUE);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result = \DrevOps\VortexTooling\posix_isatty(STDOUT);
    $this->assertTrue($result);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('posix_isatty() called more times than mocked responses. Expected 1 call(s), but attempting call #2.');

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    \DrevOps\VortexTooling\posix_isatty(STDOUT);
  }

  public function testMockPosixIsattyLessCallsFailure(): void {
    $this->mockPosixIsatty(TRUE);
    $this->mockPosixIsatty(FALSE);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $result = \DrevOps\VortexTooling\posix_isatty(STDOUT);
    $this->assertTrue($result);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked posix_isatty responses were consumed. Expected 2 call(s), but only 1 call(s) were made.');

    // Manually trigger the check that normally happens in tearDown().
    $this->mockPosixIsattyAssertAllMocksConsumed();
  }

  #[DataProvider('dataProviderMockPosixIsattyScript')]
  public function testMockPosixIsattyScript(bool $mock_value, string $fixture, string $expected): void {
    $this->mockPosixIsatty($mock_value);

    $output = $this->runScript($fixture);

    $this->assertStringContainsString('Calling posix_isatty(STDOUT)', $output);
    $this->assertStringContainsString($expected, $output);
  }

  public static function dataProviderMockPosixIsattyScript(): array {
    return [
      'true fixture with true mock' => [
        TRUE,
        'tests/Fixtures/test-posix-isatty-true',
        'SUCCESS: posix_isatty returned true as expected',
      ],
      'true fixture with false mock' => [
        FALSE,
        'tests/Fixtures/test-posix-isatty-true',
        'FAILURE: posix_isatty returned false, expected true',
      ],
      'false fixture with false mock' => [
        FALSE,
        'tests/Fixtures/test-posix-isatty-false',
        'SUCCESS: posix_isatty returned false as expected',
      ],
      'false fixture with true mock' => [
        TRUE,
        'tests/Fixtures/test-posix-isatty-false',
        'FAILURE: posix_isatty returned true, expected false',
      ],
    ];
  }

}
