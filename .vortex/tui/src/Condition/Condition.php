<?php

declare(strict_types=1);

namespace DrevOps\Tui\Condition;

/**
 * A leaf condition: a field reference and one operator.
 *
 * Constructed with named arguments - `new Condition('profile', eq: 'custom')` -
 * with exactly one operator (`eq` / `ne` / `in` / `contains`); with no operator
 * the referenced field is tested for a truthy value. Compose conditions with
 * {@see all()}, {@see any()} and {@see not()}.
 *
 * @package DrevOps\Tui\Condition
 */
final readonly class Condition implements ConditionInterface {

  /**
   * Construct a leaf condition.
   *
   * @param string $field
   *   The field id the condition references.
   * @param mixed $eq
   *   Matches when the field value equals this (scalar-aware).
   * @param mixed $ne
   *   Matches when the field value does not equal this.
   * @param list<mixed>|null $in
   *   Matches when the field value equals any member of the list.
   * @param mixed $contains
   *   Matches when the field's list contains this, or its string contains
   *   this substring.
   */
  public function __construct(
    public string $field,
    public mixed $eq = NULL,
    public mixed $ne = NULL,
    public ?array $in = NULL,
    public mixed $contains = NULL,
  ) {
  }

  /**
   * A composite matching when every condition matches.
   *
   * @param \DrevOps\Tui\Condition\ConditionInterface ...$conditions
   *   The conditions to combine.
   *
   * @return \DrevOps\Tui\Condition\CompositeCondition
   *   The composite condition.
   */
  public static function all(ConditionInterface ...$conditions): CompositeCondition {
    return new CompositeCondition('all', array_values($conditions));
  }

  /**
   * A composite matching when at least one condition matches.
   *
   * @param \DrevOps\Tui\Condition\ConditionInterface ...$conditions
   *   The conditions to combine.
   *
   * @return \DrevOps\Tui\Condition\CompositeCondition
   *   The composite condition.
   */
  public static function any(ConditionInterface ...$conditions): CompositeCondition {
    return new CompositeCondition('any', array_values($conditions));
  }

  /**
   * A composite matching when the condition does not match.
   *
   * @param \DrevOps\Tui\Condition\ConditionInterface $condition
   *   The condition to negate.
   *
   * @return \DrevOps\Tui\Condition\CompositeCondition
   *   The composite condition.
   */
  public static function not(ConditionInterface $condition): CompositeCondition {
    return new CompositeCondition('not', [$condition]);
  }

  /**
   * {@inheritdoc}
   */
  public function matches(array $answers): bool {
    $value = $answers[$this->field] ?? NULL;

    if ($this->eq !== NULL) {
      return $this->equals($value, $this->eq);
    }

    if ($this->ne !== NULL) {
      return !$this->equals($value, $this->ne);
    }

    if ($this->in !== NULL) {
      return $this->isIn($value, $this->in);
    }

    if ($this->contains !== NULL) {
      return $this->hasContains($value, $this->contains);
    }

    return !in_array($value, [NULL, FALSE, '', []], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [$this->field];
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $out = ['field' => $this->field];

    if ($this->eq !== NULL) {
      $out['eq'] = $this->eq;
    }
    elseif ($this->ne !== NULL) {
      $out['ne'] = $this->ne;
    }
    elseif ($this->in !== NULL) {
      $out['in'] = $this->in;
    }
    elseif ($this->contains !== NULL) {
      $out['contains'] = $this->contains;
    }

    return $out;
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
   * @param list<mixed> $list
   *   The candidate list.
   *
   * @return bool
   *   TRUE when the value is in the list.
   */
  protected function isIn(mixed $value, array $list): bool {
    foreach ($list as $item) {
      if ($this->equals($value, $item)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Whether an array value contains a member, or a string a substring.
   *
   * @param mixed $value
   *   The value (list or scalar).
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

}
