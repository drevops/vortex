<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyStreamInterface;

/**
 * Drives a widget to completion from a key stream.
 *
 * @package DrevOps\Tui\Widget
 */
final class WidgetRunner {

  /**
   * Feed keys to a widget until it completes or is cancelled.
   *
   * @param \DrevOps\Tui\Widget\WidgetInterface $widget
   *   The widget to drive.
   * @param \DrevOps\Tui\Input\KeyStreamInterface $keys
   *   The key source.
   *
   * @return mixed
   *   The accepted value, or NULL when cancelled.
   */
  public static function run(WidgetInterface $widget, KeyStreamInterface $keys): mixed {
    while (($key = $keys->read()) instanceof Key) {
      $widget->handle($key);

      if ($widget->isComplete() || $widget->isCancelled()) {
        break;
      }
    }

    return $widget->isCancelled() ? NULL : $widget->value();
  }

}
