<?php

declare(strict_types=1);

namespace Drupal\ys_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for ys_demo.
 */
class YsDemoHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'ys_demo_counter_block' => [
        'variables' => [
          'counter_value' => 0,
        ],
        'template' => 'ys-demo-counter-block',
      ],
    ];
  }

}
