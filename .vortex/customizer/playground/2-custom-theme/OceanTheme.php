<?php

declare(strict_types=1);

namespace Playground\CustomTheme;

use DrevOps\Customizer\Tui\Theme;

/**
 * A self-contained custom theme: bright blues and cyans with rounded glyphs.
 *
 * A consumer theme is just a class that defines its palette and, optionally,
 * its glyphs. Register it under a name with Theme::register() to select it by
 * name, exactly like the built-in dark and light themes.
 */
class OceanTheme extends Theme {

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
      'cursor' => '1;7',
      'footer' => '2;36',
      'indicator' => '1;96',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineGlyphs(): array {
    return [
      'marker' => '➤',
      'indicator_up' => '△',
      'indicator_down' => '▽',
      'separator' => '/',
      'arrow' => '»',
    ];
  }

}
