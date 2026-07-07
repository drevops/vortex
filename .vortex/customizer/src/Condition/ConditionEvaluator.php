<?php

declare(strict_types=1);

namespace DrevOps\Tui\Condition;

/**
 * Evaluates a structured `when` condition against a set of answers.
 *
 * A condition is either a composite (`all` / `any` / `not`) or a leaf with a
 * `field` reference and one operator (`eq` / `ne` / `in` / `contains`); a leaf
 * with no operator tests the referenced field for a truthy value.
 *
 * @package DrevOps\Tui\Condition
 */
class ConditionEvaluator {

  /**
   * Whether the condition matches the answers.
   *
   * @param array<array-key,mixed> $when
   *   The raw condition.
   * @param array<string,mixed> $answers
   *   The current answers keyed by field id.
   *
   * @return bool
   *   TRUE when the condition matches.
   */
  public function matches(array $when, array $answers): bool {
    if (array_key_exists('all', $when)) {
      return $this->matchesAll($this->subConditions($when['all']), $answers);
    }

    if (array_key_exists('any', $when)) {
      return $this->matchesAny($this->subConditions($when['any']), $answers);
    }

    if (array_key_exists('not', $when)) {
      $sub = $when['not'];

      return !(is_array($sub) && $this->matches($sub, $answers));
    }

    return $this->matchesLeaf($when, $answers);
  }

  /**
   * Extract the list of sub-conditions from a composite operand.
   *
   * @param mixed $list
   *   The raw operand.
   *
   * @return array<int,array<array-key,mixed>>
   *   The sub-conditions.
   */
  protected function subConditions(mixed $list): array {
    if (!is_array($list)) {
      return [];
    }

    $out = [];
    foreach ($list as $item) {
      if (is_array($item)) {
        $out[] = $item;
      }
    }

    return $out;
  }

  /**
   * Whether every sub-condition matches.
   *
   * @param array<int,array<array-key,mixed>> $subs
   *   The sub-conditions.
   * @param array<string,mixed> $answers
   *   The current answers.
   *
   * @return bool
   *   TRUE when all match.
   */
  protected function matchesAll(array $subs, array $answers): bool {
    foreach ($subs as $sub) {
      if (!$this->matches($sub, $answers)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Whether any sub-condition matches.
   *
   * @param array<int,array<array-key,mixed>> $subs
   *   The sub-conditions.
   * @param array<string,mixed> $answers
   *   The current answers.
   *
   * @return bool
   *   TRUE when at least one matches.
   */
  protected function matchesAny(array $subs, array $answers): bool {
    foreach ($subs as $sub) {
      if ($this->matches($sub, $answers)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Evaluate a leaf condition (field + operator).
   *
   * @param array<array-key,mixed> $when
   *   The leaf condition.
   * @param array<string,mixed> $answers
   *   The current answers.
   *
   * @return bool
   *   TRUE when the leaf matches.
   */
  protected function matchesLeaf(array $when, array $answers): bool {
    $field = isset($when['field']) && is_scalar($when['field']) ? (string) $when['field'] : '';
    $value = $answers[$field] ?? NULL;

    if (array_key_exists('eq', $when)) {
      return $this->equals($value, $when['eq']);
    }

    if (array_key_exists('ne', $when)) {
      return !$this->equals($value, $when['ne']);
    }

    if (array_key_exists('in', $when)) {
      return $this->isIn($value, $when['in']);
    }

    if (array_key_exists('contains', $when)) {
      return $this->hasContains($value, $when['contains']);
    }

    return $this->truthy($value);
  }

  /**
   * Loose scalar-aware equality.
   *
   * @param mixed $a
   *   The first value.
   * @param mixed $b
   *   The second value.
   *
   * @return bool
   *   TRUE when equal.
   */
  protected function equals(mixed $a, mixed $b): bool {
    if ($a === $b) {
      return TRUE;
    }

    return is_scalar($a) && is_scalar($b) && (string) $a === (string) $b;
  }

  /**
   * Whether the value equals any member of the list.
   *
   * @param mixed $value
   *   The value.
   * @param mixed $list
   *   The candidate list.
   *
   * @return bool
   *   TRUE when the value is in the list.
   */
  protected function isIn(mixed $value, mixed $list): bool {
    if (!is_array($list)) {
      return FALSE;
    }

    foreach ($list as $item) {
      if ($this->equals($value, $item)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Whether an array value contains a member, or a string contains a substring.
   *
   * @param mixed $value
   *   The value (array or scalar).
   * @param mixed $needle
   *   The needle.
   *
   * @return bool
   *   TRUE when contained.
   */
  protected function hasContains(mixed $value, mixed $needle): bool {
    if (is_array($value)) {
      foreach ($value as $item) {
        if ($this->equals($item, $needle)) {
          return TRUE;
        }
      }

      return FALSE;
    }

    return is_scalar($value) && is_scalar($needle) && str_contains((string) $value, (string) $needle);
  }

  /**
   * Whether the value is truthy (set and non-empty).
   *
   * @param mixed $value
   *   The value.
   *
   * @return bool
   *   TRUE when truthy.
   */
  protected function truthy(mixed $value): bool {
    return !in_array($value, [NULL, FALSE, '', []], TRUE);
  }

}
