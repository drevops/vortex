<?php

namespace Drupal\Tests\your_site_core\Unit;

/**
 * Class YourSiteCoreExampleUnitTest.
 *
 * @group YourSiteCore
 */
class YourSiteCoreExampleUnitTest extends YourSiteCoreUnitTestCase {

  /**
   * @dataProvider dataProviderAdd
   */
  public function testAdd($a, $b, $expected, $excpectExceptionMessage = NULL) {
    if ($excpectExceptionMessage) {
      $this->setExpectedException(\Exception::class, $excpectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a + $b;

    if (!$excpectExceptionMessage) {
      $this->assertEquals($expected, $actual);
    }
  }

  public function dataProviderAdd() {
    return [
      [0, 0, 0],
      [1, 1, 2],
    ];
  }

}
