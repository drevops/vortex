<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\Kernel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example kernel test case class.
 *
 * @package Drupal\ys_base\Tests
 */
#[Group('YsBase')]
class ExampleTest extends YsBaseKernelTestBase {

  /**
   * Tests addition.
   */
  #[DataProvider('dataProviderAdd')]
  #[Group('addition')]
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
    ];
  }

  /**
   * Tests subtraction.
   */
  #[DataProvider('dataProviderSubtract')]
  #[Group('kernel:subtraction')]
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
    ];
  }

  /**
   * Tests multiplication.
   */
  #[DataProvider('dataProviderMultiplication')]
  #[Group('multiplication')]
  #[Group('skipped')]
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
