<?php

declare(strict_types=1);

namespace Drupal\ys_demo\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a counter block with increment/decrement buttons.
 *
 * @Block(
 *   id = "ys_demo_counter_block",
 *   admin_label = @Translation("Counter Block"),
 *   category = @Translation("YS Demo"),
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
      '#theme' => 'ys_demo_counter_block',
      '#counter_value' => 0,
      '#attached' => [
        'library' => [
          'ys_demo/counter',
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
