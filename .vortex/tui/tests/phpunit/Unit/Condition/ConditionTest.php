<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Condition;

use DrevOps\Tui\Condition\CompositeCondition;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Condition\ConditionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests condition objects: leaves, composites, serialization.
 */
#[CoversClass(Condition::class)]
#[CoversClass(CompositeCondition::class)]
#[Group('condition')]
final class ConditionTest extends TestCase {

  /**
   * Conditions evaluate against answers as expected.
   *
   * @param \DrevOps\Tui\Condition\ConditionInterface $condition
   *   The condition.
   * @param array<string,mixed> $answers
   *   The answers.
   * @param bool $expected
   *   The expected result.
   */
  #[DataProvider('dataProviderMatches')]
  public function testMatches(ConditionInterface $condition, array $answers, bool $expected): void {
    $this->assertSame($expected, $condition->matches($answers));
  }

  /**
   * Data provider for testMatches().
   *
   * @return \Iterator<string,array{\DrevOps\Tui\Condition\ConditionInterface,array<string,mixed>,bool}>
   *   Conditions, answers and expected results.
   */
  public static function dataProviderMatches(): \Iterator {
    yield 'eq true' => [new Condition('a', eq: 'x'), ['a' => 'x'], TRUE];
    yield 'eq false' => [new Condition('a', eq: 'x'), ['a' => 'y'], FALSE];
    yield 'eq scalar coercion' => [new Condition('a', eq: 1), ['a' => '1'], TRUE];
    yield 'eq bool' => [new Condition('a', eq: TRUE), ['a' => TRUE], TRUE];
    yield 'eq false operand' => [new Condition('a', eq: FALSE), ['a' => FALSE], TRUE];
    yield 'eq missing field' => [new Condition('a', eq: 'x'), [], FALSE];
    yield 'ne true' => [new Condition('a', ne: 'x'), ['a' => 'y'], TRUE];
    yield 'ne false' => [new Condition('a', ne: 'x'), ['a' => 'x'], FALSE];
    yield 'in true' => [new Condition('a', in: ['x', 'y']), ['a' => 'y'], TRUE];
    yield 'in false' => [new Condition('a', in: ['x', 'y']), ['a' => 'z'], FALSE];
    yield 'contains array true' => [new Condition('a', contains: 'x'), ['a' => ['x', 'y']], TRUE];
    yield 'contains array false' => [new Condition('a', contains: 'z'), ['a' => ['x', 'y']], FALSE];
    yield 'contains string true' => [new Condition('a', contains: 'ell'), ['a' => 'hello'], TRUE];
    yield 'contains string false' => [new Condition('a', contains: 'zz'), ['a' => 'hello'], FALSE];
    yield 'truthy set' => [new Condition('a'), ['a' => 'x'], TRUE];
    yield 'truthy empty string' => [new Condition('a'), ['a' => ''], FALSE];
    yield 'truthy false' => [new Condition('a'), ['a' => FALSE], FALSE];
    yield 'truthy empty array' => [new Condition('a'), ['a' => []], FALSE];
    yield 'all true' => [Condition::all(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')), ['a' => 'x', 'b' => 'y'], TRUE];
    yield 'all false' => [Condition::all(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')), ['a' => 'x', 'b' => 'z'], FALSE];
    yield 'all empty is vacuously true' => [Condition::all(), [], TRUE];
    yield 'any true' => [Condition::any(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')), ['a' => 'q', 'b' => 'y'], TRUE];
    yield 'any false' => [Condition::any(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')), ['a' => 'q', 'b' => 'z'], FALSE];
    yield 'any empty' => [Condition::any(), [], FALSE];
    yield 'not true' => [Condition::not(new Condition('a', eq: 'x')), ['a' => 'y'], TRUE];
    yield 'not false' => [Condition::not(new Condition('a', eq: 'x')), ['a' => 'x'], FALSE];
    yield 'nested composite' => [Condition::all(new Condition('a', eq: 'x'), Condition::any(new Condition('b', eq: '1'), new Condition('b', eq: '2'))), ['a' => 'x', 'b' => '2'], TRUE];
  }

  public function testFields(): void {
    $this->assertSame(['a'], (new Condition('a', eq: 'x'))->fields());
    $this->assertSame(['a', 'b'], Condition::all(new Condition('a'), Condition::not(new Condition('b')), new Condition('a'))->fields());
  }

  public function testToArray(): void {
    $this->assertSame(['field' => 'a', 'eq' => 'x'], (new Condition('a', eq: 'x'))->toArray());
    $this->assertSame(['field' => 'a', 'ne' => 'x'], (new Condition('a', ne: 'x'))->toArray());
    $this->assertSame(['field' => 'a', 'in' => ['x', 'y']], (new Condition('a', in: ['x', 'y']))->toArray());
    $this->assertSame(['field' => 'a', 'contains' => 'x'], (new Condition('a', contains: 'x'))->toArray());
    $this->assertSame(['field' => 'a'], (new Condition('a'))->toArray());
    $this->assertSame(['all' => [['field' => 'a'], ['not' => ['field' => 'b']]]], Condition::all(new Condition('a'), Condition::not(new Condition('b')))->toArray());
    $this->assertSame(['any' => [['field' => 'a']]], Condition::any(new Condition('a'))->toArray());
  }

}
