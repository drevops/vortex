<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use AlexSkrypnyk\Prompty\Prompty;
use Symfony\Component\Console\Output\OutputInterface;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  /**
   * The output Prompty draws to.
   *
   * Only init() sets it, so setOutput() redirects lines but not Prompty's
   * frames.
   */
  protected static OutputInterface $promptyOutput;

  protected static bool $isInteractive = TRUE;

  /**
   * The number of streamed tasks whose action is running.
   *
   * While it is above 0, output is the content of the outer task, so it is
   * written as plain lines to $output instead of being drawn by Prompty.
   */
  protected static int $captureDepth = 0;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    self::$output = $output;
    self::$isInteractive = $is_interactive;

    // A command run inside a streamed task keeps Prompty drawing the task.
    if (self::isCaptured()) {
      return;
    }

    self::$promptyOutput = $output;

    // Raw output keeps the prompt frames away from the console formatter.
    Prompty::configure(
      labels: ['done' => 'OK', 'failed' => 'FAILED'],
      unicode: TRUE,
      ansi: $output->isDecorated(),
      output: fn(string $chunk) => self::$promptyOutput->write($chunk, FALSE, OutputInterface::OUTPUT_RAW),
      interactive: $is_interactive,
      placeholder_fallback: FALSE,
    );
  }

  public static function output(): OutputInterface {
    if (!isset(self::$output)) {
      throw new \RuntimeException('Output not set. Call Tui::init() first.');
    }
    return self::$output;
  }

  public static function setOutput(OutputInterface $output): void {
    self::$output = $output;
  }

  public static function isInteractive(): bool {
    return self::$isInteractive;
  }

  /**
   * Start or end capturing output as the content of a streamed task.
   *
   * @param bool $is_captured
   *   TRUE when a streamed task's action starts, FALSE when it ends.
   */
  public static function capture(bool $is_captured): void {
    self::$captureDepth = max(0, self::$captureDepth + ($is_captured ? 1 : -1));
  }

  public static function isCaptured(): bool {
    return self::$captureDepth > 0;
  }

  public static function info(string $message): void {
    self::isCaptured() ? self::line($message, 0) : Prompty::note($message);
  }

  public static function note(string $message): void {
    self::isCaptured() ? self::line($message, 0) : Prompty::output(explode(PHP_EOL, $message));
  }

  public static function success(string $message): void {
    self::isCaptured() ? self::line('✓ ' . $message, 0) : Prompty::note($message, type: 'success');
  }

  public static function error(string $message): void {
    self::isCaptured() ? self::line('✕ ' . $message, 0) : Prompty::note($message, type: 'error');
  }

  public static function confirm(string $label, bool $default = TRUE, ?string $hint = NULL): bool {
    if (!self::$isInteractive) {
      return $default;
    }

    // A cancelled prompt answers NULL, which declines.
    return (bool) Prompty::confirm($label, default: $default, help: $hint ?? '');
  }

  public static function pause(string $message): void {
    Prompty::pause($message, erase: TRUE);
  }

  public static function line(string $message, int $padding = 1): void {
    self::$output->writeln(str_repeat(' ', max(0, $padding)) . $message);
  }

  public static function green(string $text): string {
    return self::escapeMultiline($text, 32);
  }

  public static function blue(string $text): string {
    return self::escapeMultiline($text, 34);
  }

  public static function purple(string $text): string {
    return self::escapeMultiline($text, 35);
  }

  public static function yellow(string $text): string {
    return self::escapeMultiline($text, 33);
  }

  public static function cyan(string $text): string {
    return self::escapeMultiline($text, 36);
  }

  public static function bold(string $text): string {
    return self::escapeMultiline($text, 1, 22);
  }

  public static function underscore(string $text): string {
    return self::escapeMultiline($text, 4, 0);
  }

  public static function dim(string $text): string {
    // Replace reset codes with reset+dim to maintain dim through color resets.
    $text = str_replace("\033[0m", "\033[0m\033[2m", $text);
    return self::escapeMultiline($text, 2, 22);
  }

  public static function undim(string $text): string {
    return self::escapeMultiline($text, 22, 22);
  }

  protected static function escapeMultiline(string $text, int $color_code, int $end_code = 39): string {
    $lines = explode("\n", $text);
    $colored_lines = array_map(fn(string $line): string => sprintf("\033[%sm%s\033[%sm", $color_code, $line, $end_code), $lines);
    return implode("\n", $colored_lines);
  }

  /**
   * Print a titled key-value list.
   *
   * @param array<string, string|array<int, string>> $values
   *   Values keyed by their label. A value of LIST_SECTION_TITLE makes its key
   *   a section row, and a list value is joined with commas.
   * @param string|null $title
   *   The title printed above the list.
   */
  public static function list(array $values, ?string $title): void {
    $rows = [];

    foreach ($values as $key => $value) {
      if ($value === self::LIST_SECTION_TITLE) {
        $rows[] = (string) $key;
        continue;
      }

      $rows[] = [(string) $key, is_array($value) ? implode(', ', $value) : $value];
    }

    if ($title) {
      self::info($title);
    }

    self::table([], $rows);
  }

  public static function box(string $content, ?string $title = NULL): void {
    if (self::isCaptured()) {
      self::line(trim(($title ? $title . PHP_EOL : '') . $content), 0);

      return;
    }

    Prompty::box($content, title: $title ?? '');
  }

  /**
   * Print rows as a table.
   *
   * @param array<int, string> $header
   *   Header cells. An empty header draws no header row.
   * @param array<int, string|array<int, string>> $rows
   *   Table rows. A string row spans every column.
   */
  public static function table(array $header, array $rows): void {
    if (self::isCaptured()) {
      foreach (array_merge($header === [] ? [] : [$header], $rows) as $row) {
        self::line(is_array($row) ? implode(': ', $row) : $row, 0);
      }

      return;
    }

    Prompty::table($rows, headers: $header);
  }

  public static function center(string $text, int $width = 80, ?string $border = NULL): string {
    $lines = explode(PHP_EOL, $text);
    $centered_lines = [];

    $max_length = 0;
    foreach ($lines as $line) {
      $line_length = Strings::strlenPlain($line);
      if ($line_length > $max_length) {
        $max_length = $line_length;
      }
    }

    foreach ($lines as $line) {
      $padding = empty($line) ? '' : str_repeat(' ', (int) (max(0, ($width - $max_length)) / 2));
      $centered_lines[] = $padding . $line;
    }

    if ($border) {
      $border = str_repeat($border, $width - 2);
      array_unshift($centered_lines, '');
      array_unshift($centered_lines, $border);
      $centered_lines[] = '';
      $centered_lines[] = $border;
    }

    return implode(PHP_EOL, $centered_lines);
  }

  public static function terminalWidth(int $max = 100): int {
    return min($max, max(20, Prompty::terminalWidth()));
  }

}
