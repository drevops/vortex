<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example unit test case class.
 *
 * @package Drupal\star_wars\Tests
 */
#[Group('StarWars')]
class ExampleTest extends StarWarsUnitTestBase {

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
    yield [2, 1, 3];
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
  #[DataProvider('dataProviderMultiply')]
  #[Group('multiplication')]
  #[Group('skipped')]
  public function testMultiply(int $a, int $b, int $expected, string|null $expectExceptionMessage = NULL): void {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
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
