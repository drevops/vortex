<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyStreamInterface;

/**
 * Drives a widget to completion from a key stream.
 *
 * @package DrevOps\Customizer\Widget
 */
final class WidgetRunner {

  /**
   * Feed keys to a widget until it completes or is cancelled.
   *
   * @param \DrevOps\Customizer\Widget\WidgetInterface $widget
   *   The widget to drive.
   * @param \DrevOps\Customizer\Input\KeyStreamInterface $keys
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
