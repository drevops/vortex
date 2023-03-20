<?php

namespace Drupal\Tests\ys_core\Functional;

/**
 * Class YsCoreExampleFunctionalTest.
 *
 * Example test case class.
 *
 * @group YsCore
 */
class YsCoreExampleFunctionalTest extends YsCoreFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
   * @group subtraction
   */
  public function testSubtraction() {
    $this->assertEquals(1, 2 - 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/drevops/issues/820
    $this->addToAssertionCount(1);
  }

}
