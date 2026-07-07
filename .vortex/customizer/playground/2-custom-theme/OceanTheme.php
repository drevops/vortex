<?php

declare(strict_types=1);

namespace Playground\CustomTheme;

use DrevOps\Customizer\Theme\DarkTheme;

/**
 * A custom theme: bright blues and cyans over the dark theme's glyphs.
 *
 * The lowest-friction way to make a theme is to clone a shipped one - extend it
 * and override only what differs. Here that is just the palette; the glyphs are
 * inherited from DarkTheme. Register it under a name with Theme::register() to
 * select it by name, exactly like the built-in dark and light themes, or point
 * a config's `theme:` key straight at this class.
 */
class OceanTheme extends DarkTheme {

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

}
