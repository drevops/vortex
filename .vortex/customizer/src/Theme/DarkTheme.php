<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Theme;

/**
 * The default theme for dark terminals: bright foregrounds on a dark ground.
 *
 * @package DrevOps\Customizer\Theme
 */
class DarkTheme extends AbstractTheme {

  /**
   * {@inheritdoc}
   */
  protected function defineStyles(): array {
    return [
      'title' => '1;36',
      'breadcrumb' => '2',
      'label' => '',
      'value' => '32',
      'description' => '2',
      'marker' => '1;36',
      'badge' => '7',
      'cursor' => '1;7',
      'footer' => '2',
      'indicator' => '1;33',
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
    ];
  }

}
