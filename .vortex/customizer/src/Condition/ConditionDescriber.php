<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Condition;

/**
 * Renders a human-readable reason for a structured `when` condition.
 *
 * @package DrevOps\Customizer\Condition
 */
class ConditionDescriber {

  /**
   * Build an "appears when ..." reason for an inactive field.
   *
   * @param array<array-key,mixed> $when
   *   The raw condition.
   *
   * @return string
   *   The reason, e.g. "appears when theme is custom".
   */
  public function reason(array $when): string {
    return 'appears when ' . $this->describe($when);
  }

  /**
   * Describe a condition in prose.
   *
   * @param array<array-key,mixed> $when
   *   The raw condition.
   *
   * @return string
   *   The prose description.
   */
  public function describe(array $when): string {
    if (array_key_exists('all', $when)) {
      return $this->join($when['all'], ' and ');
    }

    if (array_key_exists('any', $when)) {
      return $this->join($when['any'], ' or ');
    }

    if (array_key_exists('not', $when)) {
      $sub = $when['not'];

      return 'not (' . (is_array($sub) ? $this->describe($sub) : '') . ')';
    }

    return $this->describeLeaf($when);
  }

  /**
   * Join a list of sub-conditions with a separator.
   *
   * @param mixed $list
   *   The raw operand.
   * @param string $separator
   *   The separator (e.g. " and ").
   *
   * @return string
   *   The joined description.
   */
  protected function join(mixed $list, string $separator): string {
    if (!is_array($list)) {
      return '';
    }

    $parts = [];
    foreach ($list as $item) {
      if (is_array($item)) {
        $parts[] = $this->describe($item);
      }
    }

    return implode($separator, $parts);
  }

  /**
   * Describe a leaf condition.
   *
   * @param array<array-key,mixed> $when
   *   The leaf condition.
   *
   * @return string
   *   The prose description.
   */
  protected function describeLeaf(array $when): string {
    $field = isset($when['field']) && is_scalar($when['field']) ? (string) $when['field'] : 'value';

    if (array_key_exists('eq', $when)) {
      return $field . ' is ' . $this->stringify($when['eq']);
    }

    if (array_key_exists('ne', $when)) {
      return $field . ' is not ' . $this->stringify($when['ne']);
    }

    if (array_key_exists('in', $when)) {
      return $field . ' is one of ' . $this->stringifyList($when['in']);
    }

    if (array_key_exists('contains', $when)) {
      return $field . ' contains ' . $this->stringify($when['contains']);
    }

    return $field . ' is set';
  }

  /**
   * Render a scalar value as a string.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The rendered value.
   */
  protected function stringify(mixed $value): string {
    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Render a list of scalar values as a comma-separated string.
   *
   * @param mixed $list
   *   The raw list.
   *
   * @return string
   *   The rendered list.
   */
  protected function stringifyList(mixed $list): string {
    if (!is_array($list)) {
      return '';
    }

    return implode(', ', array_map($this->stringify(...), $list));
  }

}
