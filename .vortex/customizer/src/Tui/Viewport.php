<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * The computed state of a scrolling viewport.
 *
 * @package DrevOps\Customizer\Tui
 */
final readonly class Viewport {

  /**
   * Construct a viewport state.
   *
   * @param int $offset
   *   The index of the first visible line.
   * @param bool $has_above
   *   Whether there is content scrolled off above (▲).
   * @param bool $has_below
   *   Whether there is content scrolled off below (▼).
   */
  public function __construct(
    public int $offset,
    public bool $has_above,
    public bool $has_below,
  ) {
  }

}
