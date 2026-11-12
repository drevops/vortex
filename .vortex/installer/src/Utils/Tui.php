<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use AlexSkrypnyk\Prompty\Prompty;
use Symfony\Component\Console\Output\OutputInterface;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static bool $isInteractive = TRUE;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    self::$output = $output;
    self::$isInteractive = $is_interactive;

    // Raw output keeps the prompt frames away from the console formatter. The
    // callback binds $output rather than reading self::$output, so setOutput()
    // and a later init() leave a running task on the output it started on.
    Prompty::configure(
      labels: ['done' => 'OK', 'failed' => 'FAILED'],
      unicode: TRUE,
      ansi: $output->isDecorated(),
      output: fn(string $chunk) => $output->write($chunk, FALSE, OutputInterface::OUTPUT_RAW),
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

  public static function info(string $message): void {
    Prompty::note($message);
  }

  public static function note(string $message): void {
    Prompty::output(explode(PHP_EOL, $message));
  }

  public static function success(string $message): void {
    Prompty::note($message, type: 'success');
  }

  public static function error(string $message): void {
    Prompty::note($message, type: 'error');
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
    Prompty::table($rows, headers: $header);
  }

  public static function center(string $text, int $width = 80, ?string $border = NULL): string {
    $lines = preg_split('/\r?\n/', $text) ?: [];
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

    return implode("\n", $centered_lines);
  }

  public static function terminalWidth(int $max = 100): int {
    return min($max, max(20, Prompty::terminalWidth()));
  }

}
