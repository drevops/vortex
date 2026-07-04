<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Answers;

/**
 * The collected answer set: values plus provenance, keyed by question id.
 *
 * This is the stable contract callers and handlers depend on - the question
 * ids and their value types. Only active (conditional-passing) questions are
 * present. Provenance is one of default / detected / edited / derived /
 * override.
 *
 * @package DrevOps\Customizer\Answers
 */
final readonly class Answers {

  /**
   * Construct an answer set.
   *
   * @param array<string,mixed> $values
   *   The answer values keyed by question id.
   * @param array<string,string> $provenance
   *   The provenance keyed by question id.
   */
  public function __construct(
    public array $values = [],
    public array $provenance = [],
  ) {
  }

  /**
   * Whether the set contains a question.
   *
   * @param string $id
   *   The question id.
   *
   * @return bool
   *   TRUE when present.
   */
  public function has(string $id): bool {
    return array_key_exists($id, $this->values);
  }

  /**
   * The value for a question.
   *
   * @param string $id
   *   The question id.
   *
   * @return mixed
   *   The value, or NULL when absent.
   */
  public function value(string $id): mixed {
    return $this->values[$id] ?? NULL;
  }

  /**
   * The provenance for a question.
   *
   * @param string $id
   *   The question id.
   *
   * @return string
   *   The provenance (defaults to "default" when absent).
   */
  public function provenanceOf(string $id): string {
    return $this->provenance[$id] ?? 'default';
  }

  /**
   * The active question ids, in order.
   *
   * @return list<string>
   *   The question ids.
   */
  public function ids(): array {
    return array_keys($this->values);
  }

  /**
   * The answer values as a plain array.
   *
   * @return array<string,mixed>
   *   The values keyed by question id.
   */
  public function toArray(): array {
    return $this->values;
  }

  /**
   * The answer values as a JSON string.
   *
   * @return string
   *   The JSON-encoded values.
   */
  public function toJson(): string {
    return (string) json_encode($this->values);
  }

}
