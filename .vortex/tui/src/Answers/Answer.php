<?php

declare(strict_types=1);

namespace DrevOps\Tui\Answers;

use DrevOps\Tui\Config\FieldType;

/**
 * A single collected answer with a snapshot of the question it answers.
 *
 * The snapshot (label, kind, weight, panel trail) is taken at collection
 * time, so consumers can present or process an answer set without holding
 * the form configuration.
 *
 * @package DrevOps\Tui\Answers
 */
final readonly class Answer {

  /**
   * Construct an answer.
   *
   * @param string $id
   *   The question id.
   * @param mixed $value
   *   The answer value.
   * @param string $provenance
   *   One of default / detected / edited / derived / override.
   * @param string $label
   *   The question's human-readable label.
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The question kind.
   * @param int $weight
   *   The processing weight; lower runs earlier.
   * @param list<string> $panels
   *   The titles of the panels the question lives under, outermost first.
   */
  public function __construct(
    public string $id,
    public mixed $value,
    public string $provenance = 'default',
    public string $label = '',
    public FieldType $type = FieldType::Text,
    public int $weight = 0,
    public array $panels = [],
  ) {
  }

}
