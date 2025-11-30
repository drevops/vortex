<?php

declare(strict_types=1);

namespace Drupal\the_force_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a counter block with increment/decrement buttons.
 *
 * @Block(
 *   id = "the_force_base_counter_block",
 *   admin_label = @Translation("Counter Block"),
 *   category = @Translation("YS Base"),
 * )
 */
class CounterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function build(): array {
    return [
      '#theme' => 'the_force_base_counter_block',
      '#counter_value' => 0,
      '#attached' => [
        'library' => [
          'the_force_base/counter',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    // This block should not be cached as it's interactive.
    return 0;
  }

}
