<?php

declare(strict_types=1);

namespace DrevOps\Tui\Config;

/**
 * A single selectable option for a select, multiselect or suggest field.
 *
 * @package DrevOps\Tui\Config
 */
final readonly class Option {

  public function __construct(
    public string $value,
    public string $label,
    public string $description = '',
  ) {
  }

}
