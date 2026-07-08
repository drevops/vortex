<?php

declare(strict_types=1);

namespace DrevOps\Tui\Answers;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Panel;

/**
 * The collected answer set: values plus provenance, keyed by question id.
 *
 * This is the stable contract callers and handlers depend on - the question
 * ids and their value types. Only active (conditional-passing) questions are
 * present. Provenance is one of default / detected / edited / derived /
 * override.
 *
 * Answer sets produced by the engine and the panel TUI are self-describing:
 * each answer carries a snapshot of its question (label, kind, weight, panel
 * trail) in items(), so summaries and processing need no form configuration.
 *
 * @package DrevOps\Tui\Answers
 */
final readonly class Answers {

  /**
   * Construct an answer set.
   *
   * @param array<string,mixed> $values
   *   The answer values keyed by question id.
   * @param array<string,\DrevOps\Tui\Answers\Provenance> $provenance
   *   The provenance keyed by question id.
   * @param array<string,\DrevOps\Tui\Answers\Answer> $items
   *   The self-describing answers keyed by question id, in form order (empty
   *   when the set was assembled without a configuration).
   */
  public function __construct(
    public array $values = [],
    public array $provenance = [],
    public array $items = [],
  ) {
  }

  /**
   * Build a self-describing answer set from a configuration.
   *
   * Walks the panel tree in form order and snapshots each active question
   * (label, kind, weight, panel trail) into its answer.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration the answers were collected against.
   * @param array<string,mixed> $values
   *   The answer values keyed by question id.
   * @param array<string,\DrevOps\Tui\Answers\Provenance> $provenance
   *   The provenance keyed by question id.
   *
   * @return self
   *   The answer set.
   */
  public static function forConfig(Config $config, array $values, array $provenance): self {
    $items = [];

    $walk = function (Panel $panel, array $trail) use (&$walk, &$items, $values, $provenance): void {
      $trail[] = $panel->title;

      foreach ($panel->fields as $field) {
        if (!array_key_exists($field->id, $values)) {
          continue;
        }

        $items[$field->id] = new Answer($field->id, $values[$field->id], $provenance[$field->id] ?? Provenance::Default, $field->label, $field->type, $field->weight, $trail);
      }

      foreach ($panel->panels as $subpanel) {
        $walk($subpanel, $trail);
      }
    };

    foreach ($config->panels as $panel) {
      $walk($panel, []);
    }

    return new self($values, $provenance, $items);
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
   * @return \DrevOps\Tui\Answers\Provenance
   *   The provenance (Default when absent).
   */
  public function provenanceOf(string $id): Provenance {
    return $this->provenance[$id] ?? Provenance::Default;
  }

  /**
   * The self-describing answer for a question.
   *
   * @param string $id
   *   The question id.
   *
   * @return \DrevOps\Tui\Answers\Answer|null
   *   The answer, or NULL when absent (or the set carries no snapshots).
   */
  public function item(string $id): ?Answer {
    return $this->items[$id] ?? NULL;
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

  /**
   * The answers as a human summary grouped by panel.
   *
   * @return string
   *   The formatted summary (empty when the set carries no snapshots).
   */
  public function toSummary(): string {
    return (new SummaryFormatter())->format($this);
  }

}
