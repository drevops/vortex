<?php

declare(strict_types=1);

namespace DrevOps\Tui\Answers;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Panel;

/**
 * Formats an answer set as a human summary grouped by panel.
 *
 * Panels (and sub-panels) with no active answers are omitted; each value is
 * rendered readably and non-default answers carry a provenance badge.
 *
 * @package DrevOps\Tui\Answers
 */
class SummaryFormatter {

  /**
   * Format the answers grouped by the configuration's panels.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration providing the panel structure.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The answer set.
   *
   * @return string
   *   The formatted summary.
   */
  public function format(Config $config, Answers $answers): string {
    $lines = [];

    foreach ($config->panels as $panel) {
      $lines = array_merge($lines, $this->formatPanel($panel, $answers, 0));
    }

    return implode("\n", $lines);
  }

  /**
   * Format a single panel and its sub-panels.
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The answer set.
   * @param int $depth
   *   The nesting depth.
   *
   * @return list<string>
   *   The lines for this panel, or empty when it has no active answers.
   */
  protected function formatPanel(Panel $panel, Answers $answers, int $depth): array {
    $indent = str_repeat('  ', $depth);
    $body = [];

    foreach ($panel->fields as $field) {
      if (!$answers->has($field->id)) {
        continue;
      }

      $raw = $answers->value($field->id);
      // Secrets never print: a fixed-length mask hides both value and length.
      $value = $field->type === FieldType::Password && is_string($raw) && $raw !== '' ? str_repeat('*', 8) : $this->renderValue($raw);
      $body[] = $indent . '  ' . $field->label . ': ' . $value . $this->badge($answers->provenanceOf($field->id));
    }

    foreach ($panel->panels as $subpanel) {
      $body = array_merge($body, $this->formatPanel($subpanel, $answers, $depth + 1));
    }

    if ($body === []) {
      return [];
    }

    return array_merge([$indent . $panel->title], $body);
  }

  /**
   * Render a value readably.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The rendered value.
   */
  protected function renderValue(mixed $value): string {
    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    if (is_array($value)) {
      return implode(', ', array_map(static fn(mixed $item): string => is_scalar($item) ? (string) $item : '', $value));
    }

    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * The provenance badge for a value (empty for defaults).
   *
   * @param string $provenance
   *   The provenance.
   *
   * @return string
   *   The badge suffix.
   */
  protected function badge(string $provenance): string {
    return $provenance === 'default' ? '' : ' (' . $provenance . ')';
  }

}
