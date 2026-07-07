<?php

declare(strict_types=1);

namespace Playground\CustomTheme;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\Panel;
use DrevOps\Customizer\Theme\DarkTheme;
use DrevOps\Customizer\Tui\Navigator;

/**
 * A custom theme that overrides as much as it sensibly can, to show the surface.
 *
 * There are three kinds of override, all demonstrated below:
 *  - the constructor - to change the defaults (here, a narrower 72-col frame);
 *  - defineStyles() and defineGlyphs() - the palette and the glyph pairs;
 *  - any render*() and summarizePanel() method - to change how an element is
 *    laid out.
 *
 * It extends DarkTheme, so anything left un-overridden (e.g. renderBody(),
 * renderFrame()) falls back to the dark theme. Extend AbstractTheme instead to
 * start from a blank slate. Select it from a config with
 * `theme: '\Playground\CustomTheme\OceanTheme'`, or register a short name with
 * Theme::register('ocean', OceanTheme::class).
 */
class OceanTheme extends DarkTheme {

  /**
   * Override the constructor to default to a narrower 72-column frame.
   *
   * @param bool $color
   *   Whether colour is enabled.
   * @param int $width
   *   The frame width.
   * @param bool $unicode
   *   Whether Unicode glyphs are used.
   */
  public function __construct(bool $color = TRUE, int $width = 72, bool $unicode = TRUE) {
    parent::__construct($color, $width, $unicode);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineStyles(): array {
    return [
      'title' => '1;96',
      'breadcrumb' => '2;36',
      'label' => '',
      'value' => '96',
      'description' => '2;34',
      'marker' => '1;96',
      'badge' => '7;36',
      'cursor' => '1;7;96',
      'footer' => '2;36',
      'indicator' => '1;96',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineGlyphs(): array {
    return [
      'marker' => ['➤', '>'],
      'indicator_up' => ['▴', '^'],
      'indicator_down' => ['▾', 'v'],
      'separator' => ['/', '/'],
      'arrow' => ['»', '>'],
      'arrow_up' => ['↑', '^'],
      'arrow_down' => ['↓', 'v'],
      'enter' => ['⏎', '<'],
      'dot' => ['•', '*'],
      'radio_on' => ['◉', '(o)'],
      'radio_off' => ['◯', '( )'],
      'check_on' => ['▣', '[x]'],
      'check_off' => ['▢', '[ ]'],
      'caret' => ['▏', '|'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFieldLine(Field $field, Answers $answers, bool $selected): string {
    return $this->marker($selected) . ' ' . $this->style('label', $field->label) . ': ' . $this->style('value', $this->renderValue($answers->value($field->id)));
  }

  /**
   * {@inheritdoc}
   */
  public function renderPanelLine(Panel $panel, bool $selected): string {
    $count = count($panel->fields) + count($panel->panels);

    return $this->marker($selected) . ' ' . $this->style('title', $panel->title) . '  ' . $this->style('description', $this->glyph('arrow') . ' ' . $count . ' item' . ($count === 1 ? '' : 's'));
  }

  /**
   * {@inheritdoc}
   */
  public function renderDescriptionLine(string $description): string {
    return '    ' . $this->style('description', $this->glyph('dot') . ' ' . $description);
  }

  /**
   * {@inheritdoc}
   */
  public function summarizePanel(Panel $panel, Answers $answers): string {
    $parts = [];
    foreach ($panel->fields as $field) {
      if ($answers->has($field->id)) {
        $parts[] = $this->renderValue($answers->value($field->id));
      }
    }

    return implode(' ' . $this->glyph('separator') . ' ', array_slice($parts, 0, 3));
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummaryLine(string $summary): string {
    return '    ' . $this->style('description', $this->glyph('arrow') . ' ' . $summary);
  }

  /**
   * {@inheritdoc}
   */
  public function renderBreadcrumbLine(Navigator $navigator): string {
    return $this->style('breadcrumb', '≈ ' . implode(' ' . $this->glyph('separator') . ' ', $navigator->breadcrumb()));
  }

  /**
   * {@inheritdoc}
   */
  public function renderStatusLine(): string {
    $sep = '  ' . $this->glyph('dot') . '  ';

    return $this->style('footer', $this->glyph('arrow_up') . $this->glyph('arrow_down') . ' move' . $sep . $this->glyph('enter') . ' choose' . $sep . 'esc back');
  }

  /**
   * {@inheritdoc}
   */
  public function renderButtonBar(array $labels, int $selected): string {
    $buttons = [];
    foreach ($labels as $index => $label) {
      $text = '« ' . $label . ' »';
      $buttons[] = $index === $selected ? $this->style('cursor', $text) : $this->style('label', $text);
    }

    return '  ' . implode('   ', $buttons);
  }

  /**
   * {@inheritdoc}
   */
  public function renderBanner(string $logo, string $version): string {
    $lines = [];
    foreach (explode("\n", $logo) as $line) {
      $lines[] = $this->style('title', $line);
    }

    if ($version !== '') {
      $lines[] = '';
      $lines[] = $this->style('footer', '≈ ' . $version . ' ≈');
    }

    return implode("\n", $lines);
  }

}
