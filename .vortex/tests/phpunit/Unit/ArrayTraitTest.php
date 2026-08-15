<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Unit;

use Drupal\Tests\ys_base\Traits\ArrayTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ArrayTrait shipped to consumer sites.
 */
class ArrayTraitTest extends TestCase {

  use ArrayTrait;

  /**
   * Tests replacing values throughout an array.
   */
  #[DataProvider('dataProviderArrayReplaceValue')]
  public function testArrayReplaceValue(array $array, callable $callback, array $expected): void {
    $this->assertSame($expected, static::arrayReplaceValue($array, $callback));
  }

  /**
   * Data provider for testArrayReplaceValue().
   */
  public static function dataProviderArrayReplaceValue(): \Iterator {
    $upper = static fn(mixed $value): mixed => is_string($value) ? strtoupper($value) : $value;

    yield 'empty array' => [[], $upper, []];

    yield 'flat array' => [['first', 'second'], $upper, ['FIRST', 'SECOND']];

    yield 'string keys preserved' => [['a' => 'first', 'b' => 'second'], $upper, ['a' => 'FIRST', 'b' => 'SECOND']];

    yield 'nested array' => [['first', ['second', ['third']]], $upper, ['FIRST', ['SECOND', ['THIRD']]]];

    yield 'mixed value types' => [['first', 42, NULL, TRUE], $upper, ['FIRST', 42, NULL, TRUE]];

    yield 'empty nested array preserved' => [['first', []], $upper, ['FIRST', []]];
  }

  /**
   * Tests that the callback receives every leaf value.
   */
  public function testArrayReplaceValuePassesEveryLeafToCallback(): void {
    $seen = [];

    $collect = static function (mixed $value) use (&$seen): mixed {
      $seen[] = $value;

      return $value;
    };

    static::arrayReplaceValue(['first', ['second', 'third']], $collect);

    $this->assertSame(['first', 'second', 'third'], $seen);
  }

}
