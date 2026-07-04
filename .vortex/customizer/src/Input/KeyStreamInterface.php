<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Input;

/**
 * A source of key presses consumed by the widgets.
 *
 * @package DrevOps\Customizer\Input
 */
interface KeyStreamInterface {

  /**
   * Read the next key.
   *
   * @return \DrevOps\Customizer\Input\Key|null
   *   The next key, or NULL when the stream is exhausted.
   */
  public function read(): ?Key;

}
