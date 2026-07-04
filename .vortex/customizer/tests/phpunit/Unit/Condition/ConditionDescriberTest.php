<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Condition;

use DrevOps\Customizer\Condition\ConditionDescriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the condition describer.
 */
#[CoversClass(ConditionDescriber::class)]
#[Group('condition')]
final class ConditionDescriberTest extends TestCase {

  public function testReason(): void {
    $describer = new ConditionDescriber();

    $this->assertSame('appears when theme is custom', $describer->reason(['field' => 'theme', 'eq' => 'custom']));
  }

  /**
   * Conditions describe to the expected prose.
   *
   * @param array<array-key,mixed> $when
   *   The condition.
   * @param string $expected
   *   The expected description.
   */
  #[DataProvider('dataProviderDescribe')]
  public function testDescribe(array $when, string $expected): void {
    $this->assertSame($expected, (new ConditionDescriber())->describe($when));
  }

  /**
   * Data provider for testDescribe().
   *
   * @return \Iterator<string,array{array<array-key,mixed>,string}>
   *   Conditions and their expected descriptions.
   */
  public static function dataProviderDescribe(): \Iterator {
    yield 'eq' => [['field' => 'theme', 'eq' => 'custom'], 'theme is custom'];
    yield 'eq bool' => [['field' => 'ai', 'eq' => TRUE], 'ai is yes'];
    yield 'ne' => [['field' => 'a', 'ne' => 'x'], 'a is not x'];
    yield 'in' => [['field' => 'a', 'in' => ['x', 'y']], 'a is one of x, y'];
    yield 'contains' => [['field' => 'mods', 'contains' => 'devel'], 'mods contains devel'];
    yield 'truthy' => [['field' => 'a'], 'a is set'];
    yield 'all' => [['all' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], 'a is x and b is y'];
    yield 'any' => [['any' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], 'a is x or b is y'];
    yield 'not' => [['not' => ['field' => 'a', 'eq' => 'x']], 'not (a is x)'];
    yield 'no field' => [['eq' => 'x'], 'value is x'];
  }

}
