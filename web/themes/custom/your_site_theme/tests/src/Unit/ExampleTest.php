<?php

declare(strict_types=1);

namespace Drupal\Tests\your_site_theme\Unit;

/**
 * Class ExampleTest.
 *
 * Example unit test case class.
 *
 * @group YourSiteTheme
 *
 * @package Drupal\your_site_theme\Tests
 */
class ExampleTest extends YourSiteThemeUnitTestBase {

  /**
   * Tests addition.
   *
   * @dataProvider dataProviderAdd
   * @group addition
   */
  public function testAdd(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a + $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testAdd().
   */
  public function dataProviderAdd(): array {
    return [
      [0, 0, 0],
      [1, 1, 2],
      [2, 1, 3],
    ];
  }

  /**
   * Tests subtraction.
   *
   * @dataProvider dataProviderSubtract
   * @group unit:subtraction
   */
  public function testSubtract(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a - $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testSubtract().
   */
  public function dataProviderSubtract(): array {
    return [
      [0, 0, 0],
      [1, 1, 0],
      [2, 1, 1],
      [3, 1, 2],
    ];
  }

  /**
   * Tests multiplication.
   *
   * @dataProvider dataProviderMultiplication
   * @group multiplication
   * @group skipped
   */
  public function testMultiplication(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a * $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testMultiplication().
   */
  public function dataProviderMultiplication(): array {
    return [
      [0, 0, 0],
      [1, 1, 1],
      [2, 1, 2],
    ];
  }

}
