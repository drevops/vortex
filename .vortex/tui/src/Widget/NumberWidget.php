<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

/**
 * Integer input: digits with an optional leading minus, accepted as an int.
 *
 * @package DrevOps\Tui\Widget
 */
class NumberWidget extends TextWidget {

  /**
   * {@inheritdoc}
   */
  protected function insert(string $char): void {
    if ($char === '-') {
      if ($this->cursor !== 0 || str_contains($this->buffer, '-')) {
        return;
      }
    }
    elseif (!ctype_digit($char)) {
      return;
    }

    parent::insert($char);
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    return (int) $this->buffer;
  }

}
