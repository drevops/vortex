<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @package Drupal\star_wars\Tests
 */
#[Group('StarWars')]
class ExampleTest extends StarWarsFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    // Vortex does not support Functional tests due to permission issues.
    // Override setup until @see https://github.com/drevops/vortex/issues/820
    // resolved.
    // This test is left here to make sure that all DrevOps tooling works as
    // expected.
  }

  /**
   * Temporary test stub.
   */
  #[Group('addition')]
  public function testAddition(): void {
    $this->assertEquals(2, 1 + 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/vortex/issues/820
    $this->addToAssertionCount(1);
  }

  /**
   * Temporary test stub.
   */
  #[Group('functional:subtraction')]
  public function testSubtraction(): void {
    $this->assertEquals(1, 2 - 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/vortex/issues/820
    $this->addToAssertionCount(1);
  }

}
