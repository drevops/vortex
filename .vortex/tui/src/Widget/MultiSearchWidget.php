<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Theme\ThemeInterface;

/**
 * A multi-select whose type-to-filter query is shown as a search line.
 *
 * @package DrevOps\Tui\Widget
 */
class MultiSearchWidget extends MultiSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function view(ThemeInterface $theme): string {
    return $this->filter . $theme->glyph('caret') . "\n" . parent::view($theme);
  }

}
