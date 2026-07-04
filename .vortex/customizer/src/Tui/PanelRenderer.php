<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\Panel;

/**
 * Renders a panel's rows and composes a scrolled, pinned-chrome frame.
 *
 * Rows are the panel's fields followed by its sub-panels; the selected item is
 * marked and provenance badges are right-aligned by visible (ANSI-stripped)
 * width so colour on or off keeps the same layout.
 *
 * @package DrevOps\Customizer\Tui
 */
class PanelRenderer {

  /**
   * Construct a renderer.
   *
   * @param \DrevOps\Customizer\Tui\Theme $theme
   *   The theme.
   * @param int $width
   *   The frame width.
   */
  public function __construct(protected Theme $theme, protected int $width = 76) {
  }

  /**
   * The number of navigable items in a panel (fields + sub-panels).
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   *
   * @return int
   *   The item count.
   */
  public function itemCount(Panel $panel): int {
    return count($panel->fields) + count($panel->panels);
  }

  /**
   * Build the body lines and the line index of the selected item.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param int $cursor
   *   The selected item index.
   *
   * @return array{list<string>,int}
   *   The body lines and the selected item's first line index.
   */
  public function body(Panel $panel, Answers $answers, int $cursor): array {
    $lines = [];
    $cursor_line = 0;
    $index = 0;

    foreach ($panel->fields as $field) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->fieldLine($field, $answers, $index === $cursor);
      if ($field->description !== '') {
        $lines[] = $this->descriptionLine($field->description);
      }

      $index++;
    }

    foreach ($panel->panels as $subpanel) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->panelLine($subpanel, $index === $cursor);
      if ($subpanel->description !== '') {
        $lines[] = $this->descriptionLine($subpanel->description);
      }

      $index++;
    }

    return [$lines, $cursor_line];
  }

  /**
   * Render a field row.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function fieldLine(Field $field, Answers $answers, bool $selected): string {
    $left = $this->marker($selected) . ' ' . $this->theme->style('label', $field->label) . '  ' . $this->theme->style('value', $this->renderValue($answers->value($field->id)));

    $provenance = $answers->provenanceOf($field->id);
    if ($provenance === 'default') {
      return $left;
    }

    return Ansi::alignRight($left, $this->theme->style('badge', ' ' . $provenance . ' '), $this->width);
  }

  /**
   * Render a sub-panel row.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The sub-panel.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function panelLine(Panel $panel, bool $selected): string {
    return $this->marker($selected) . ' ' . $this->theme->style('label', $panel->title) . ' ' . $this->theme->style('description', '›');
  }

  /**
   * Render a description row.
   *
   * @param string $description
   *   The description.
   *
   * @return string
   *   The row.
   */
  public function descriptionLine(string $description): string {
    return '    ' . $this->theme->style('description', $description);
  }

  /**
   * Render a breadcrumb line for the navigator.
   *
   * @param \DrevOps\Customizer\Tui\Navigator $navigator
   *   The navigator.
   *
   * @return string
   *   The breadcrumb line.
   */
  public function breadcrumbLine(Navigator $navigator): string {
    return $this->theme->style('breadcrumb', implode(' › ', $navigator->breadcrumb()));
  }

  /**
   * Compose a frame: pinned header, scrolled body with ▲/▼, pinned footer.
   *
   * @param list<string> $header
   *   The pinned header lines.
   * @param list<string> $body
   *   The full body lines.
   * @param list<string> $footer
   *   The pinned footer lines.
   * @param \DrevOps\Customizer\Tui\Viewport $viewport
   *   The computed viewport.
   * @param int $height
   *   The body viewport height.
   *
   * @return string
   *   The composed frame.
   */
  public function frame(array $header, array $body, array $footer, Viewport $viewport, int $height): string {
    $visible = (new Scroller())->slice($body, $viewport->offset, $height);

    $lines = $header;
    if ($viewport->has_above) {
      $lines[] = $this->theme->style('indicator', '  ▲');
    }

    $lines = array_merge($lines, $visible);

    if ($viewport->has_below) {
      $lines[] = $this->theme->style('indicator', '  ▼');
    }

    return implode("\n", array_merge($lines, $footer));
  }

  /**
   * The selection marker.
   *
   * @param bool $selected
   *   Whether selected.
   *
   * @return string
   *   The marker.
   */
  protected function marker(bool $selected): string {
    return $selected ? $this->theme->style('marker', '❯') : ' ';
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

}
