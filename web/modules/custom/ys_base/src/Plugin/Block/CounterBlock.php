<?php

declare(strict_types=1);

namespace Drupal\ys_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a counter block with increment/decrement buttons.
 *
 * @Block(
 *   id = "ys_base_counter_block",
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
      '#theme' => 'ys_base_counter_block',
      '#counter_value' => 0,
      '#attached' => [
        'library' => [
          'ys_base/counter',
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
