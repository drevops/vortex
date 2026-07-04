<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Condition;

use DrevOps\Customizer\Condition\ConditionEvaluator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the condition evaluator.
 */
#[CoversClass(ConditionEvaluator::class)]
#[Group('condition')]
final class ConditionEvaluatorTest extends TestCase {

  /**
   * Conditions evaluate against answers as expected.
   *
   * @param array<array-key,mixed> $when
   *   The condition.
   * @param array<string,mixed> $answers
   *   The answers.
   * @param bool $expected
   *   The expected result.
   */
  #[DataProvider('dataProviderMatches')]
  public function testMatches(array $when, array $answers, bool $expected): void {
    $this->assertSame($expected, (new ConditionEvaluator())->matches($when, $answers));
  }

  /**
   * Data provider for testMatches().
   *
   * @return \Iterator<string,array{array<array-key,mixed>,array<string,mixed>,bool}>
   *   Conditions, answers and expected results.
   */
  public static function dataProviderMatches(): \Iterator {
    yield 'eq true' => [['field' => 'a', 'eq' => 'x'], ['a' => 'x'], TRUE];
    yield 'eq false' => [['field' => 'a', 'eq' => 'x'], ['a' => 'y'], FALSE];
    yield 'eq scalar coercion' => [['field' => 'a', 'eq' => 1], ['a' => '1'], TRUE];
    yield 'eq bool' => [['field' => 'a', 'eq' => TRUE], ['a' => TRUE], TRUE];
    yield 'eq missing field' => [['field' => 'a', 'eq' => 'x'], [], FALSE];
    yield 'ne true' => [['field' => 'a', 'ne' => 'x'], ['a' => 'y'], TRUE];
    yield 'ne false' => [['field' => 'a', 'ne' => 'x'], ['a' => 'x'], FALSE];
    yield 'in true' => [['field' => 'a', 'in' => ['x', 'y']], ['a' => 'y'], TRUE];
    yield 'in false' => [['field' => 'a', 'in' => ['x', 'y']], ['a' => 'z'], FALSE];
    yield 'in non-array' => [['field' => 'a', 'in' => 'x'], ['a' => 'x'], FALSE];
    yield 'contains array true' => [['field' => 'a', 'contains' => 'x'], ['a' => ['x', 'y']], TRUE];
    yield 'contains array false' => [['field' => 'a', 'contains' => 'z'], ['a' => ['x', 'y']], FALSE];
    yield 'contains string true' => [['field' => 'a', 'contains' => 'ell'], ['a' => 'hello'], TRUE];
    yield 'contains string false' => [['field' => 'a', 'contains' => 'zz'], ['a' => 'hello'], FALSE];
    yield 'truthy set' => [['field' => 'a'], ['a' => 'x'], TRUE];
    yield 'truthy empty string' => [['field' => 'a'], ['a' => ''], FALSE];
    yield 'truthy false' => [['field' => 'a'], ['a' => FALSE], FALSE];
    yield 'truthy empty array' => [['field' => 'a'], ['a' => []], FALSE];
    yield 'all true' => [['all' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], ['a' => 'x', 'b' => 'y'], TRUE];
    yield 'all false' => [['all' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], ['a' => 'x', 'b' => 'z'], FALSE];
    yield 'any true' => [['any' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], ['a' => 'q', 'b' => 'y'], TRUE];
    yield 'any false' => [['any' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]], ['a' => 'q', 'b' => 'z'], FALSE];
    yield 'not true' => [['not' => ['field' => 'a', 'eq' => 'x']], ['a' => 'y'], TRUE];
    yield 'not false' => [['not' => ['field' => 'a', 'eq' => 'x']], ['a' => 'x'], FALSE];
    yield 'not non-array operand' => [['not' => 'bogus'], [], TRUE];
    yield 'nested composite' => [['all' => [['field' => 'a', 'eq' => 'x'], ['any' => [['field' => 'b', 'eq' => '1'], ['field' => 'b', 'eq' => '2']]]]], ['a' => 'x', 'b' => '2'], TRUE];
  }

}
