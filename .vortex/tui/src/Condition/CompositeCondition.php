<?php

declare(strict_types=1);

namespace DrevOps\Tui\Condition;

/**
 * A composite condition: all / any / not over other conditions.
 *
 * Construct via {@see Condition::all()}, {@see Condition::any()} and
 * {@see Condition::not()}.
 *
 * @package DrevOps\Tui\Condition
 */
final readonly class CompositeCondition implements ConditionInterface {

  /**
   * Construct a composite condition.
   *
   * @param string $operator
   *   The operator: "all", "any" or "not".
   * @param list<\DrevOps\Tui\Condition\ConditionInterface> $conditions
   *   The combined conditions (exactly one for "not").
   */
  public function __construct(protected string $operator, protected array $conditions) {
  }

  /**
   * {@inheritdoc}
   */
  public function matches(array $answers): bool {
    if ($this->operator === 'not') {
      $first = $this->conditions[0] ?? NULL;

      return !($first instanceof ConditionInterface && $first->matches($answers));
    }

    foreach ($this->conditions as $condition) {
      $matched = $condition->matches($answers);

      if ($this->operator === 'any' && $matched) {
        return TRUE;
      }

      if ($this->operator === 'all' && !$matched) {
        return FALSE;
      }
    }

    return $this->operator === 'all';
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $fields = [];

    foreach ($this->conditions as $condition) {
      $fields = array_merge($fields, $condition->fields());
    }

    return array_values(array_unique($fields));
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    if ($this->operator === 'not') {
      $first = $this->conditions[0] ?? NULL;

      return ['not' => $first instanceof ConditionInterface ? $first->toArray() : []];
    }

    return [$this->operator => array_map(static fn(ConditionInterface $condition): array => $condition->toArray(), $this->conditions)];
  }

}
