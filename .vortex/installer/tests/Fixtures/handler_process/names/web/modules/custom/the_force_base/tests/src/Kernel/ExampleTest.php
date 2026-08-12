<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Kernel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example kernel test case class.
 *
 * @package Drupal\the_force_base\Tests
 */
#[Group('TheForceBase')]
class ExampleTest extends TheForceBaseKernelTestBase {

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

    // Replace the line below with a call to the method under test.
    $actual = $a + $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testAdd().
   */
  public static function dataProviderAdd(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 2];
  }

  /**
   * Tests subtraction.
   */
  #[DataProvider('dataProviderSubtract')]
  #[Group('subtraction')]
  public function testSubtract(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace the line below with a call to the method under test.
    $actual = $a - $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testSubtract().
   */
  public static function dataProviderSubtract(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 0];
    yield [2, 1, 1];
  }

  /**
   * Tests multiplication.
   */
  #[DataProvider('dataProviderMultiply')]
  #[Group('multiplication')]
  #[Group('skipped')]
  public function testMultiply(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace the line below with a call to the method under test.
    $actual = $a * $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testMultiply().
   */
  public static function dataProviderMultiply(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 1];
    yield [2, 1, 2];
  }

}
