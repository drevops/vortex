<?php

declare(strict_types=1);

namespace DrevOps\Tui\Condition;

/**
 * A condition evaluated against a set of answers.
 *
 * @package DrevOps\Tui\Condition
 */
interface ConditionInterface {

  /**
   * Whether the condition matches the answers.
   *
   * @param array<string,mixed> $answers
   *   The current answers keyed by field id.
   *
   * @return bool
   *   TRUE when the condition matches.
   */
  public function matches(array $answers): bool;

  /**
   * The field ids the condition references.
   *
   * @return list<string>
   *   The referenced field ids.
   */
  public function fields(): array;

  /**
   * The condition as the raw array shape used by the JSON schema.
   *
   * @return array<array-key,mixed>
   *   The raw condition.
   */
  public function toArray(): array;

}
