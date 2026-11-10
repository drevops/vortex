<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use AlexSkrypnyk\Prompty\Prompty;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static bool $isInteractive = TRUE;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    self::$output = $output;
    self::$isInteractive = $is_interactive;

    // The writer reads the output on each call, so a replacement set with
    // setOutput() receives the prompts too. Raw output keeps the prompt frames
    // away from the console formatter.
    Prompty::configure(
      unicode: TRUE,
      ansi: $output->isDecorated(),
      output: fn(string $chunk) => self::$output->write($chunk, FALSE, OutputInterface::OUTPUT_RAW),
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

  public static function info(string $message): void {
    $lines = array_map(fn(string $line): string => ' ' . $line . ' ', explode(PHP_EOL, $message));
    $longest = max(array_map(mb_strlen(...), $lines));

    self::block(array_map(fn(string $line): string => " \033[46m\033[30m" . mb_str_pad($line, $longest) . "\033[39m\033[49m", $lines));
  }

  public static function note(string $message): void {
    self::block(array_map(fn(string $line): string => ' ' . $line, explode(PHP_EOL, $message)));
  }

  public static function success(string $message): void {
    self::block(array_map(fn(string $line): string => "\033[32m " . $line . "\033[39m", explode(PHP_EOL, $message)));
  }

  public static function error(string $message): void {
    self::block(array_map(fn(string $line): string => "\033[31m " . $line . "\033[39m", explode(PHP_EOL, '✕ ' . $message)));
  }

  public static function confirm(string $label, bool $default = TRUE, ?string $hint = NULL): bool {
    if (!self::$isInteractive) {
      return $default;
    }

    // A cancelled prompt answers NULL, which declines.
    return (bool) Prompty::confirm($label, default: $default, help: $hint ?? '');
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
    $longest = max(array_map(Strings::strlenPlain(...), $lines));

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

    self::info(PHP_EOL . self::normalizeText($title) . PHP_EOL);
    self::table($header, $rows);
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

    self::table([], $rows);
  }

  /**
   * Render rows as a bordered table with a 1-column margin.
   *
   * @param array<int, string> $header
   *   Header cells. An empty header draws no header row.
   * @param array<int, array<int, string>> $rows
   *   Table rows.
   */
  public static function table(array $header, array $rows): void {
    $style = (new TableStyle())
      ->setHorizontalBorderChars('─')
      ->setVerticalBorderChars('│', '│')
      ->setCellHeaderFormat("\033[2m<fg=default>%s</>\033[22m")
      ->setCellRowFormat('<fg=default>%s</>');

    if (empty($header)) {
      $style->setCrossingChars('┼', '<fg=gray>┌', '┬', '┐', '┤', '┘</>', '┴', '└', '├', '<fg=gray>┌', '┬', '┐');
    }
    else {
      $style->setCrossingChars('┼', '<fg=gray>┌', '┬', '┐', '┤', '┘</>', '┴', '└', '├');
    }

    $buffered = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, self::output()->isDecorated());

    (new Table($buffered))->setHeaders($header)->setRows($rows)->setStyle($style)->render();

    self::block(array_map(fn(string $line): string => ' ' . $line, explode(PHP_EOL, trim($buffered->fetch(), PHP_EOL))));
  }

  /**
   * Write lines as a block separated from the output around it.
   *
   * @param array<int, string> $lines
   *   Lines to write.
   */
  protected static function block(array $lines): void {
    self::output()->write(PHP_EOL . implode('', array_map(fn(string $line): string => $line . PHP_EOL, $lines)) . PHP_EOL);
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
    return min($max, max(20, (new Terminal())->getWidth()));
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
