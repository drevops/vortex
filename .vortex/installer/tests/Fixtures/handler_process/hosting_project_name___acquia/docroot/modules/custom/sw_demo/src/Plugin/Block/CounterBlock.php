<?php

declare(strict_types=1);

namespace Drupal\sw_demo\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a counter block with increment/decrement buttons.
 */
#[Block(
  id: 'sw_demo_counter_block',
  admin_label: new TranslatableMarkup('Counter Block'),
  category: new TranslatableMarkup('YS Demo'),
)]
class CounterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function build(): array {
    return [
      '#theme' => 'sw_demo_counter_block',
      '#counter_value' => 0,
      '#attached' => [
        'library' => [
          'sw_demo/counter',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    // The block is interactive, so it is never cached.
    return 0;
  }

}
