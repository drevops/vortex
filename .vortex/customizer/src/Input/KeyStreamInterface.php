<?php

declare(strict_types=1);

namespace DrevOps\Tui\Input;

/**
 * A source of key presses consumed by the widgets.
 *
 * @package DrevOps\Tui\Input
 */
interface KeyStreamInterface {

  /**
   * Read the next key.
   *
   * @return \DrevOps\Tui\Input\Key|null
   *   The next key, or NULL when the stream is exhausted.
   */
  public function read(): ?Key;

}
