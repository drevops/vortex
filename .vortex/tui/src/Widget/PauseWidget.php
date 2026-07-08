<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\ThemeInterface;

/**
 * An acknowledgement gate: Enter (or Space) accepts TRUE.
 *
 * @package DrevOps\Tui\Widget
 */
class PauseWidget extends AbstractWidget {

  /**
   * {@inheritdoc}
   */
  public function handle(Key $key): void {
    if ($this->handleCancel($key)) {
      return;
    }

    if ($key->is(KeyName::Enter) || $key->is(KeyName::Space)) {
      $this->accept(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function view(ThemeInterface $theme): string {
    return $theme->glyph('enter') . ' Press Enter to continue';
  }

}
