<?php

declare(strict_types=1);

namespace DrevOps\Tui\Engine;

/**
 * Where a field's initial value was resolved from.
 *
 * @package DrevOps\Tui\Engine
 */
enum Source {

  case Input;
  case Detected;
  case Default;

}
