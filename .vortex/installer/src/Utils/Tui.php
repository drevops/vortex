<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static bool $isInteractive = TRUE;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    self::$output = $output;
    self::$isInteractive = $is_interactive;

    // Symfony console styles are not used here because Laravel Prompts does
    // not correctly calculate the length of strings with style tags, which
    // breaks the layout. Instead, helpers in this class apply ANSI escape
    // codes directly.
    Prompt::setOutput($output);

    if (!$is_interactive) {
      Prompt::interactive(FALSE);
    }
  }

  public static function output(): OutputInterface {
    if (!isset(self::$output)) {
      throw new \RuntimeException('Output not set. Call Tui::init() first.');
    }
    return self::$output;
  }

  public static function setOutput(OutputInterface $output): void {
    self::$output = $output;
    Prompt::setOutput($output);
  }

  public static function info(string $message): void {
    intro($message);
  }

  public static function note(string $message): void {
    note($message);
  }

  public static function success(string $message): void {
    info($message);
  }

  public static function error(string $message): void {
    error('✕ ' . $message);
  }

  public static function confirm(string $label, bool $default = TRUE, ?string $hint = NULL): bool {
    if (!self::$isInteractive) {
      return $default;
    }

    return confirm(
      label: $label,
      default: $default,
      hint: $hint ?? '',
    );
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

  public static function getChar(): string {
    if (!self::$isInteractive) {
      return '';
    }

    // Disable input buffering.
    system('stty cbreak -echo');

    $res = fopen('php://stdin', 'r');
    if ($res === FALSE) {
      return '';
    }

    $char = (string) fgetc($res);

    // Restore terminal settings.
    system('stty -cbreak echo');

    return $char;
  }

  protected static function escapeMultiline(string $text, int $color_code, int $end_code = 39): string {
    $lines = explode("\n", $text);
    $colored_lines = array_map(fn(string $line): string => sprintf("\033[%sm%s\033[%sm", $color_code, $line, $end_code), $lines);
    return implode("\n", $colored_lines);
  }

  public static function caretDown(): string {
    return "\033[B";
  }

  public static function caretUp(): string {
    return "\033[A";
  }

  public static function caretEol(string $text): string {
    $lines = explode(PHP_EOL, $text);
    $longest = max(array_map(strlen(...), $lines));

    return "\033[" . $longest . "C";
  }

  public static function list(array $values, ?string $title): void {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $values[$key] = implode(', ', $value);
      }
    }

    $terminal_width = self::terminalWidth();

    // (margin + 2 x border + 2 x padding) x 2 - 1 collapse divider width.
    $column_width = max(1, (int) floor(($terminal_width - (1 + (1 + 1) * 2) * 2 - 1) / 2));

    $header = [];
    $rows = [];
    foreach ($values as $key => $value) {
      if ($value === self::LIST_SECTION_TITLE) {
        $rows[] = [Tui::cyan(Tui::bold(self::normalizeText($key)))];
        continue;
      }

      $key = self::normalizeText($key);
      $value = self::normalizeText($value);

      $key = '  ' . wordwrap(self::normalizeText($key), $column_width + 2, PHP_EOL . '  ', TRUE);
      $value = wordwrap(self::normalizeText($value), $column_width, PHP_EOL, TRUE);

      $rows[] = [$key, $value];
    }

    intro(PHP_EOL . self::normalizeText($title) . PHP_EOL);
    table($header, $rows);
  }

  public static function box(string $content, ?string $title = NULL, ?int $width = NULL): void {
    $rows = [];

    $width ??= self::terminalWidth();

    // 1 margin + 1 border + 1 padding + 1 padding + 1 border + 1 margin.
    $offset = 6;

    $content = wordwrap($content, $width - $offset, PHP_EOL, TRUE);

    if ($title) {
      $title = wordwrap($title, $width - $offset, PHP_EOL, FALSE);
      $rows[] = [self::green($title)];
      $rows[] = [self::green(str_repeat('─', Strings::strlenPlain(explode(PHP_EOL, self::normalizeText($title))[0]))) . PHP_EOL];
    }

    $rows[] = [$content];

    table([], $rows);
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
    return min($max, max(20, (new Terminal())->cols()));
  }

  public static function normalizeText(string $text): string {
    if (!Strings::isAsciiStart($text)) {
      return $text;
    }

    $text = preg_replace('/\s{2,}/', ' ', $text);

    preg_match_all('/\X/u', (string) $text, $matches);

    $utf8_chars = $matches[0];
    $utf8_chars = array_map(fn(string $char): string => Strings::isAsciiStart($char) ? $char : $char . self::utfPadding($char), $utf8_chars);

    return implode('', $utf8_chars);
  }

  protected static function utfPadding(string $char): string {
    $padding = '';

    $len = strlen($char);
    $mblen = mb_strlen($char);

    // @see https://youtrack.jetbrains.com/issue/IJPL-101568/Terminal-display-Python-icon-in-wrong-width
    if (str_contains((string) getenv('TERMINAL_EMULATOR'), 'JetBrains') && ($mblen === 1 && $len < 4)) {
      $padding = ' ';
    }

    if (str_contains((string) getenv('TERM_PROGRAM'), 'Apple_Terminal') && ($mblen > 1 && $len < 8)) {
      return ' ';
    }

    return $padding;
  }

}
