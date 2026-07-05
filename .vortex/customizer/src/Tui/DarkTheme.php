<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * The default theme for dark terminals: bright foregrounds on a dark ground.
 *
 * @package DrevOps\Customizer\Tui
 */
class DarkTheme extends Theme {

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

}
