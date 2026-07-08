<?php

declare(strict_types=1);

namespace DrevOps\Tui\Theme;

/**
 * A theme for light terminals: darker, higher-contrast foregrounds.
 *
 * Bright cyan and yellow wash out on a light background, so the palette leans
 * on blue and magenta instead.
 *
 * @package DrevOps\Tui\Theme
 */
class LightTheme extends AbstractTheme {

  /**
   * {@inheritdoc}
   */
  protected function defineStyles(): array {
    return [
      'title' => '1;34',
      'breadcrumb' => '2',
      'label' => '',
      'value' => '34',
      'description' => '2',
      'marker' => '1;34',
      'badge' => '7',
      'cursor' => '1;7',
      'footer' => '2',
      'indicator' => '35',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineGlyphs(): array {
    return [
      'marker' => ['❯', '>'],
      'indicator_up' => ['▲', '^'],
      'indicator_down' => ['▼', 'v'],
      'separator' => ['›', '>'],
      'arrow' => ['›', '>'],
      'arrow_up' => ['↑', '^'],
      'arrow_down' => ['↓', 'v'],
      'enter' => ['↵', '<'],
      'dot' => ['·', '*'],
      'radio_on' => ['●', '(*)'],
      'radio_off' => ['○', '( )'],
      'check_on' => ['◼', '[x]'],
      'check_off' => ['◻', '[ ]'],
      'caret' => ['│', '|'],
      'mask' => ['•', '*'],
    ];
  }

}
