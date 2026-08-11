<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @package Drupal\light_saber\Tests
 */
#[Group('LightSaber')]
class ExampleTest extends LightSaberFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  protected function setUp(): void {
    parent::setUp();
    // Vortex does not support Functional tests due to permission issues, so
    // the setup is overridden. This test is left here to make sure that all
    // tooling works as expected.
  }

  /**
   * Temporary test stub.
   */
  #[Group('addition')]
  public function testAdd(): void {
    $this->assertEquals(2, 1 + 1);
    // DrevOps does not support Functional tests due to permission issues.
    $this->addToAssertionCount(1);
  }

  /**
   * Temporary test stub.
   */
  #[Group('subtraction')]
  public function testSubtract(): void {
    $this->assertEquals(1, 2 - 1);
    // DrevOps does not support Functional tests due to permission issues.
    $this->addToAssertionCount(1);
  }

}
