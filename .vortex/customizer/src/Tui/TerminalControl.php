<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * Pure terminal control sequences (alternate screen, cursor, mouse).
 *
 * Kept separate from I/O so the sequences are unit-testable; a Terminal writes
 * them to the real TTY.
 *
 * @package DrevOps\Customizer\Tui
 */
final class TerminalControl {

  /**
   * Enter the alternate screen buffer (disables native scrollback).
   *
   * @return string
   *   The control sequence.
   */
  public static function altScreenOn(): string {
    return Ansi::ESC . '[?1049h';
  }

  /**
   * Leave the alternate screen buffer.
   *
   * @return string
   *   The control sequence.
   */
  public static function altScreenOff(): string {
    return Ansi::ESC . '[?1049l';
  }

  /**
   * Hide the cursor.
   *
   * @return string
   *   The control sequence.
   */
  public static function hideCursor(): string {
    return Ansi::ESC . '[?25l';
  }

  /**
   * Show the cursor.
   *
   * @return string
   *   The control sequence.
   */
  public static function showCursor(): string {
    return Ansi::ESC . '[?25h';
  }

  /**
   * Enable SGR mouse tracking.
   *
   * @return string
   *   The control sequence.
   */
  public static function mouseOn(): string {
    return Ansi::ESC . '[?1000h' . Ansi::ESC . '[?1006h';
  }

  /**
   * Disable SGR mouse tracking.
   *
   * @return string
   *   The control sequence.
   */
  public static function mouseOff(): string {
    return Ansi::ESC . '[?1000l' . Ansi::ESC . '[?1006l';
  }

  /**
   * Clear the screen and home the cursor.
   *
   * @return string
   *   The control sequence.
   */
  public static function clear(): string {
    return Ansi::ESC . '[2J' . Ansi::ESC . '[H';
  }

  /**
   * The full restore sequence (mouse off, cursor shown, main screen).
   *
   * @return string
   *   The control sequence.
   */
  public static function restore(): string {
    return self::mouseOff() . self::showCursor() . self::altScreenOff();
  }

}
