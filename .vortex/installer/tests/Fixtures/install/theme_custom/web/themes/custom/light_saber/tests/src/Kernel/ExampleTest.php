<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Kernel;

/**
 * Class ExampleTest.
 *
 * Example kernel test case class.
 *
 * @group LightSaber
 *
 * @package Drupal\light_saber\Tests
 */
class ExampleTest extends LightSaberKernelTestBase {

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
  public static function dataProviderAdd(): array {
    return [
      [0, 0, 0],
      [1, 1, 2],
      [3, 1, 4],
    ];
  }

  /**
   * Tests subtraction.
   *
   * @dataProvider dataProviderSubtract
   * @group kernel:subtraction
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
  public static function dataProviderSubtract(): array {
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
  public static function dataProviderMultiplication(): array {
    return [
      [0, 0, 0],
      [1, 1, 1],
      [2, 1, 2],
    ];
  }

}
