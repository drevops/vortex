<?php

declare(strict_types=1);

namespace DrevOps\Tui\Render;

/**
 * Computes the visible window of a scrolling list.
 *
 * `compute()` follows the cursor (a key press re-engages cursor-follow),
 * keeping it inside the viewport; `scroll()` moves the window without moving
 * the cursor (mouse wheel). Both clamp to the valid range and report ▲/▼.
 *
 * @package DrevOps\Tui\Render
 */
class Scroller {

  /**
   * Compute the viewport that keeps the cursor visible.
   *
   * @param int $total
   *   The total number of lines.
   * @param int $height
   *   The viewport height.
   * @param int $cursor
   *   The cursor line index.
   * @param int $offset
   *   The current first-visible-line index.
   *
   * @return \DrevOps\Tui\Render\Viewport
   *   The computed viewport.
   */
  public function compute(int $total, int $height, int $cursor, int $offset): Viewport {
    if ($height <= 0 || $total <= 0) {
      return new Viewport(0, FALSE, FALSE);
    }

    $cursor = max(0, min($total - 1, $cursor));

    if ($cursor < $offset) {
      $offset = $cursor;
    }
    elseif ($cursor >= $offset + $height) {
      $offset = $cursor - $height + 1;
    }

    $offset = $this->clamp($offset, $total, $height);

    return new Viewport($offset, $offset > 0, $offset + $height < $total);
  }

  /**
   * Move the window by a delta without moving the cursor (mouse wheel).
   *
   * @param int $offset
   *   The current first-visible-line index.
   * @param int $delta
   *   The scroll delta (negative up, positive down).
   * @param int $total
   *   The total number of lines.
   * @param int $height
   *   The viewport height.
   *
   * @return int
   *   The new offset.
   */
  public function scroll(int $offset, int $delta, int $total, int $height): int {
    return $this->clamp($offset + $delta, $total, $height);
  }

  /**
   * Slice the visible lines for an offset and height.
   *
   * @param list<string> $lines
   *   The lines.
   * @param int $offset
   *   The first-visible-line index.
   * @param int $height
   *   The viewport height.
   *
   * @return list<string>
   *   The visible lines.
   */
  public function slice(array $lines, int $offset, int $height): array {
    return array_slice($lines, max(0, $offset), max(0, $height));
  }

  /**
   * Clamp an offset to the valid range.
   *
   * @param int $offset
   *   The offset.
   * @param int $total
   *   The total number of lines.
   * @param int $height
   *   The viewport height.
   *
   * @return int
   *   The clamped offset.
   */
  protected function clamp(int $offset, int $total, int $height): int {
    return max(0, min(max(0, $total - $height), $offset));
  }

}
