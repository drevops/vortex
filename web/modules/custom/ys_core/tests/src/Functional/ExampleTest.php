<?php

namespace Drupal\Tests\ys_core\Functional;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @group YsCore
 *
 * @package Drupal\ys_core\Tests
 */
class ExampleTest extends YsCoreFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  protected function setUp(): void {
    // DrevOps does not support Functional tests due to permission issues.
    // Override setup until @see https://github.com/drevops/drevops/issues/820
    // resolved.
    // This test is left here to make sure that all DrevOps tooling works as
    // expected.
  }

  /**
   * Temporary test stub.
   *
   * @group addition
   */
  public function testAddition() {
    $this->assertEquals(2, 1 + 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/drevops/issues/820
    $this->addToAssertionCount(1);
  }

  /**
   * Temporary test stub.
   *
   * @group functional:subtraction
   */
  public function testSubtraction() {
    $this->assertEquals(1, 2 - 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/drevops/issues/820
    $this->addToAssertionCount(1);
  }

}
