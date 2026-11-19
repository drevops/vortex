<?php

declare(strict_types=1);

namespace Drupal\sw_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for sw_demo.
 */
class SwDemoHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'sw_demo_counter_block' => [
        'variables' => [
          'counter_value' => 0,
        ],
        'template' => 'sw-demo-counter-block',
      ],
    ];
  }

}
