<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Self-tests for mocking of the quit() function.
 *
 * We test mockQuit() to ensure it throws QuitErrorException with a correct
 * exit code that we can catch and assert in tests.
 */
#[CoversClass(UnitTestCase::class)]
class MockQuitSelfTest extends UnitTestCase {

  public function testMockQuit0Success(): void {
    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);
    $this->expectExceptionCode(0);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    \DrevOps\VortexTooling\quit(0);
  }

  public function testMockQuit0Failure(): void {
    $this->mockQuit(0);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('quit() called with unexpected exit code. Expected 0, got 1.');

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    \DrevOps\VortexTooling\quit(1);
  }

  public function testMockQuit1Success(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    \DrevOps\VortexTooling\quit(1);
  }

  public function testMockQuit1Failure(): void {
    $this->mockQuit(1);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('quit() called with unexpected exit code. Expected 1, got 0.');

    // @phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    \DrevOps\VortexTooling\quit(0);
  }

  public function testMockQuitScript0Success(): void {
    $this->mockQuit();

    $this->expectException(QuitSuccessException::class);
    $this->expectExceptionCode(0);

    $output = $this->runScript('tests/Fixtures/test-quit-passing');

    $this->assertStringContainsString('Script will exit with code 0', $output);
    $this->assertStringNotContainsString('ERROR Script continued after quit()', $output);
  }

  public function testMockQuitScript0Failure(): void {
    $this->mockQuit();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('quit() called with unexpected exit code. Expected 0, got 1.');

    $output = $this->runScript('tests/Fixtures/test-quit-failing');

    $this->assertStringContainsString('Script will exit with code 1', $output);
    $this->assertStringNotContainsString('ERROR Script continued after quit()', $output);
  }

  public function testMockQuitScript1Success(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('tests/Fixtures/test-quit-failing');

    $this->assertStringContainsString('Script will exit with code 1', $output);
    $this->assertStringNotContainsString('ERROR Script continued after quit()', $output);
  }

  public function testMockQuitScript1Failure(): void {
    $this->mockQuit(1);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('quit() called with unexpected exit code. Expected 1, got 0.');

    $output = $this->runScript('tests/Fixtures/test-quit-passing');

    $this->assertStringContainsString('Script will exit with code 0', $output);
    $this->assertStringNotContainsString('ERROR Script continued after quit()', $output);
  }

}
