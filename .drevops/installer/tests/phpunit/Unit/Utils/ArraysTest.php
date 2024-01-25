<?php

namespace DrevOps\Installer\Tests\Unit\Utils;

use DrevOps\Installer\Utils\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\Installer\Utils\Arrays
 */
class ArraysTest extends TestCase {

  /**
   * @covers ::sortByKeyArray
   * @dataProvider providerSortByKeyArray
   */
  public function testSortByKeyArray(mixed $array, array $keys, mixed $expected): void {
    $array_before = $array;
    $this->assertEquals($expected, Arrays::sortByKeyArray($array, $keys));
    $this->assertSame($array_before, $array, 'Array was not modified');
  }

  public static function providerSortByKeyArray(): array {
    return [
      // Non-numeric keys.
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        ['c', 'a'],
        ['c' => 3, 'a' => 1, 'b' => 2],
      ],
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        ['d', 'a'],
        ['a' => 1, 'b' => 2, 'c' => 3],
      ],
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        [],
        ['a' => 1, 'b' => 2, 'c' => 3],
      ],

      // Numeric keys.
      [
        [1, 2, 3],
        [2, 0],
        [3, 1, 2],
      ],
      [
        [10 => 1, 20 => 2, 30 => 3],
        [30, 10],
        [3, 1, 2],
      ],
      [
        [10 => 1, 20 => 2, 30 => 3],
        [40, 10],
        [1, 2, 3],
      ],

      // Mixed keys.
      [
        ['a' => 1, 'b' => 2, 3 => 3],
        ['b', 'a'],
        ['b' => 2, 'a' => 1, 3],
      ],
      [
        ['a' => 1, 'b' => 2, 3 => 3],
        [3, 'a'],
        [3, 'a' => 1, 'b' => 2],
      ],

    ];
  }

  /**
   * @covers ::sortByValueArray
   * @dataProvider dataProviderSortByValueArray
   */
  public function testSortByValueArray(mixed $array, array $values, mixed $expected): void {
    $array_before = $array;
    $result = Arrays::sortByValueArray($array, $values);
    $this->assertSame($expected, $result);
    $this->assertSame($array_before, $array, 'Array was not modified');
  }

  public static function dataProviderSortByValueArray(): array {
    return [
      [['a' => 3, 'b' => 1, 'c' => 2], [1, 2, 3], ['b' => 1, 'c' => 2, 'a' => 3]],
      [['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'], ['cherry', 'banana', 'apple'], ['c' => 'cherry', 'b' => 'banana', 'a' => 'apple']],
      [['a' => 'apple', 'b' => 'banana'], ['banana', 'apple'], ['b' => 'banana', 'a' => 'apple']],
      [['a' => 'apple', 'b' => 'banana'], ['apple'], ['a' => 'apple', 'b' => 'banana']],
      [[0 => 'apple', 1 => 'banana'], ['banana', 'apple'], [0 => 'banana', 1 => 'apple']],
      [[2 => 'apple', 3 => 'banana'], ['banana', 'apple'], [0 => 'banana', 1 => 'apple']],
    ];
  }

  /**
   * @covers ::reindex
   * @dataProvider dataProviderReindex
   */
  public function testReindex(array $values, int $start, mixed $expected): void {
    $result = Arrays::reindex($values, $start);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderReindex(): array {
    return [
      [[1, 2, 3], 0, [0 => 1, 1 => 2, 2 => 3]],
      [['a', 'b', 'c'], 5, [5 => 'a', 6 => 'b', 7 => 'c']],
      [[], 2, []],

      [['x', 'y'], -2, [-2 => 'x', -1 => 'y']],
      [[10 => 'a', 20 => 'b'], 0, [0 => 'a', 1 => 'b']],
      [[['a'], ['b']], 0, [0 => ['a'], 1 => ['b']]],
      [[1, 'a', NULL, 4.5], 0, [0 => 1, 1 => 'a', 2 => NULL, 3 => 4.5]],
      [range(1, 1000), 0, array_combine(range(0, 999), range(1, 1000))],
      [['a' => 'apple', 'b' => 'banana'], 0, [0 => 'apple', 1 => 'banana']],
    ];
  }

}
