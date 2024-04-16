<?php

namespace Drupal\Tests\your_site_theme\Functional;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @group YourSiteTheme
 *
 * @package Drupal\your_site_theme\Tests
 */
class ExampleTest extends YourSiteThemeFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  protected function setUp(): void {
    parent::setUp();
    // DrevOps does not support Functional tests due to permission issues.
    // Override setup until @see https://github.com/drevops/scaffold/issues/820
    // resolved.
    // This test is left here to make sure that all DrevOps tooling works as
    // expected.
  }

  /**
   * Temporary test stub.
   *
   * @group addition
   */
  public function testAddition(): void {
    $this->assertEquals(2, 1 + 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/scaffold/issues/820
    $this->addToAssertionCount(1);
  }

  /**
   * Temporary test stub.
   *
   * @group functional:subtraction
   */
  public function testSubtraction(): void {
    $this->assertEquals(1, 2 - 1);
    // DrevOps does not support Functional tests due to permission issues.
    // @see https://github.com/drevops/scaffold/issues/820
    $this->addToAssertionCount(1);
  }

}
