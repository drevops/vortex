<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

/**
 * Terminal text formatting helpers used in handler labels and descriptions.
 *
 * @package DrevOps\VortexCli\Utils
 */
class Tui {

  /**
   * Format text as bold.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The formatted text.
   */
  public static function bold(string $text): string {
    return static::escapeMultiline($text, 1, 22);
  }

  /**
   * Format text as underscored.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The formatted text.
   */
  public static function underscore(string $text): string {
    return static::escapeMultiline($text, 4, 0);
  }

  /**
   * Wrap every line of the text in an ANSI escape pair.
   *
   * @param string $text
   *   The text (may be multi-line).
   * @param int $color_code
   *   The opening SGR code.
   * @param int $end_code
   *   The closing SGR code.
   *
   * @return string
   *   The wrapped text.
   */
  protected static function escapeMultiline(string $text, int $color_code, int $end_code = 39): string {
    $lines = explode("\n", $text);
    $colored_lines = array_map(fn(string $line): string => sprintf("\033[%sm%s\033[%sm", $color_code, $line, $end_code), $lines);

    return implode("\n", $colored_lines);
  }

}
