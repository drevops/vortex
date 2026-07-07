<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Theme\Theme;

/**
 * A single interactive field collector driven one key at a time.
 *
 * @package DrevOps\Customizer\Widget
 */
interface WidgetInterface {

  /**
   * Process one key press, mutating the widget state.
   *
   * @param \DrevOps\Customizer\Input\Key $key
   *   The key to process.
   */
  public function handle(Key $key): void;

  /**
   * Whether a valid value has been accepted.
   */
  public function isComplete(): bool;

  /**
   * Whether the widget was cancelled (Escape).
   */
  public function isCancelled(): bool;

  /**
   * The current value.
   *
   * @return mixed
   *   The typed value (string, string[] or bool depending on the widget).
   */
  public function value(): mixed;

  /**
   * The current validation error, if any.
   *
   * @return string|null
   *   The error message, or NULL when there is none.
   */
  public function error(): ?string;

  /**
   * A rendering of the current state, using the theme's glyphs.
   *
   * @param \DrevOps\Customizer\Theme\Theme $theme
   *   The theme supplying Unicode or ASCII glyphs.
   *
   * @return string
   *   The rendered view.
   */
  public function view(Theme $theme): string;

}
