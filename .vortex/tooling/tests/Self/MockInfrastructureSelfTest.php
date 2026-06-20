<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Self-tests for mock infrastructure edge cases.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversClass(UnitTestCase::class)]
class MockInfrastructureSelfTest extends UnitTestCase {

  public function testAssertMockConsumedWithNonExistentMock(): void {
    $this->mockPassthruAssertAllMocksConsumed();
    $this->mockMailAssertAllMocksConsumed();
    $this->mockRequestAssertAllMocksConsumed();

    // If we reach here without exceptions, the test passes.
    $this->expectNotToPerformAssertions();
  }

}
