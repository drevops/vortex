<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Answers;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Panel;

/**
 * Formats an answer set as a human summary grouped by panel.
 *
 * Panels (and sub-panels) with no active answers are omitted; each value is
 * rendered readably and non-default answers carry a provenance badge.
 *
 * @package DrevOps\Customizer\Answers
 */
class SummaryFormatter {

  /**
   * Format the answers grouped by the configuration's panels.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration providing the panel structure.
   * @param \DrevOps\Customizer\Answers\Answers $answers
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
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Customizer\Answers\Answers $answers
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

      $value = $this->renderValue($answers->value($field->id));
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
