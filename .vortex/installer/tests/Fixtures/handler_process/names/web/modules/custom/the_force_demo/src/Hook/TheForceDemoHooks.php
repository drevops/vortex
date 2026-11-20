<?php

declare(strict_types=1);

namespace Drupal\the_force_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the_force_demo.
 */
class TheForceDemoHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'the_force_demo_counter_block' => [
        'variables' => [
          'counter_value' => 0,
        ],
        'template' => 'the-force-demo-counter-block',
      ],
    ];
  }

}
