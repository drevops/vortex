<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Kernel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example kernel test case class.
 *
 * @package Drupal\star_wars\Tests
 */
#[Group('StarWars')]
class ExampleTest extends StarWarsKernelTestBase {

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
  public static function dataProviderAdd(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 2];
    yield [3, 1, 4];
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
  public static function dataProviderSubtract(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 0];
    yield [2, 1, 1];
    yield [3, 1, 2];
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
  public static function dataProviderMultiplication(): \Iterator {
    yield [0, 0, 0];
    yield [1, 1, 1];
    yield [2, 1, 2];
  }

}
