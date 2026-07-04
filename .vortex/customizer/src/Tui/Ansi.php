<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * ANSI helpers: styling, escape stripping and visible-width alignment.
 *
 * @package DrevOps\Customizer\Tui
 */
final class Ansi {

  /**
   * The escape character.
   */
  public const ESC = "\033";

  /**
   * Wrap text in an SGR style, resetting afterwards.
   *
   * @param string $text
   *   The text.
   * @param string $sgr
   *   The SGR parameters (e.g. "1;32"); empty leaves the text unstyled.
   *
   * @return string
   *   The styled text.
   */
  public static function style(string $text, string $sgr): string {
    return $sgr === '' ? $text : self::ESC . '[' . $sgr . 'm' . $text . self::ESC . '[0m';
  }

  /**
   * Strip ANSI escape sequences from text.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The text without escape sequences.
   */
  public static function strip(string $text): string {
    return (string) preg_replace('/\033\[[0-9;?<>=]*[A-Za-z]/', '', $text);
  }

  /**
   * The visible width of text (ANSI-stripped, code-point counted).
   *
   * @param string $text
   *   The text.
   *
   * @return int
   *   The visible width.
   */
  public static function width(string $text): int {
    return mb_strlen(self::strip($text));
  }

  /**
   * Place a left and right part on one line, right-aligning by visible width.
   *
   * @param string $left
   *   The left part.
   * @param string $right
   *   The right part.
   * @param int $width
   *   The total line width.
   *
   * @return string
   *   The composed line.
   */
  public static function alignRight(string $left, string $right, int $width): string {
    $pad = $width - self::width($left) - self::width($right);

    return $left . str_repeat(' ', max(1, $pad)) . $right;
  }

}
