<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\Functional;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @group YsBase
 *
 * @package Drupal\ys_base\Tests
 */
class ExampleTest extends YsBaseFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  protected function setUp(): void {
    parent::setUp();
    // DrevOps does not support Functional tests due to permission issues.
    // Override setup until @see https://github.com/drevops/vortex/issues/820
    // resolved.
    // This test is left here to make sure that all Vortex tooling works as
    // expected.
  }

  /**
   * Temporary test stub.
   *
   * @group addition
   */
  public function testAddition(): void {
    $this->assertEquals(2, 1 + 1);
    // Vortex does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/vortex/issues/820
    $this->addToAssertionCount(1);
  }

  /**
   * Temporary test stub.
   *
   * @group functional:subtraction
   */
  public function testSubtraction(): void {
    $this->assertEquals(1, 2 - 1);
    // Vortex does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/vortex/issues/820
    $this->addToAssertionCount(1);
  }

}
