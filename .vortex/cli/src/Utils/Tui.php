<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

use DrevOps\PhpTui\Primitive\Output;
use DrevOps\PhpTui\Terminal\Terminal;
use DrevOps\PhpTui\Theme\DefaultTheme;
use Symfony\Component\Console\Output\OutputInterface;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static bool $isInteractive = TRUE;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    static::$output = $output;
    static::$isInteractive = $is_interactive;
  }

  public static function output(): OutputInterface {
    if (!isset(static::$output)) {
      throw new \RuntimeException('Output not set. Call Tui::init() first.');
    }
    return static::$output;
  }

  public static function setOutput(OutputInterface $output): void {
    static::$output = $output;
  }

  public static function info(string $message): void {
    static::marker('›', $message, static::cyan(...));
  }

  public static function note(string $message): void {
    static::marker('', $message, static::dim(...));
  }

  public static function success(string $message): void {
    static::marker('✓', $message, static::green(...));
  }

  public static function error(string $message): void {
    static::marker('✕', $message, static::yellow(...));
  }

  public static function confirm(string $label, bool $default = TRUE, ?string $hint = NULL): bool {
    if (!static::$isInteractive) {
      return $default;
    }

    static::line(sprintf('%s %s [%s]', static::cyan(static::bold('›')), $label, $default ? 'Y/n' : 'y/N'));

    if ($hint !== NULL && $hint !== '') {
      static::line(static::dim($hint));
    }

    $answer = static::getChar();

    return match (mb_strtolower($answer)) {
      'y' => TRUE,
      'n' => FALSE,
      default => $default,
    };
  }

  /**
   * Write a message behind a marker glyph, colouring the line as a whole.
   *
   * @param string $marker
   *   The glyph opening the first line; empty for none.
   * @param string $message
   *   The message, which may span lines.
   * @param \Closure $color
   *   Applied to the composed line, so the glyph and the text read as one run
   *   rather than being separated by a reset.
   */
  protected static function marker(string $marker, string $message, \Closure $color): void {
    $prefix = $marker === '' ? '' : $marker . ' ';

    foreach (explode(PHP_EOL, $message) as $index => $line) {
      static::line($color(($index === 0 ? $prefix : str_repeat(' ', mb_strlen($prefix))) . $line));
    }
  }

  public static function line(string $message, int $padding = 1): void {
    static::$output->writeln(str_repeat(' ', max(0, $padding)) . $message);
  }

  public static function green(string $text): string {
    return static::escapeMultiline($text, 32);
  }

  public static function blue(string $text): string {
    return static::escapeMultiline($text, 34);
  }

  public static function purple(string $text): string {
    return static::escapeMultiline($text, 35);
  }

  public static function yellow(string $text): string {
    return static::escapeMultiline($text, 33);
  }

  public static function cyan(string $text): string {
    return static::escapeMultiline($text, 36);
  }

  public static function bold(string $text): string {
    return static::escapeMultiline($text, 1, 22);
  }

  public static function underscore(string $text): string {
    return static::escapeMultiline($text, 4, 0);
  }

  public static function dim(string $text): string {
    // Replace reset codes with reset+dim to maintain dim through color resets.
    $text = str_replace("\033[0m", "\033[0m\033[2m", $text);
    return static::escapeMultiline($text, 2, 22);
  }

  public static function undim(string $text): string {
    return static::escapeMultiline($text, 22, 22);
  }

  public static function getChar(): string {
    if (!static::$isInteractive) {
      return '';
    }

    // The state is captured rather than assumed, and restored from a finally,
    // so a failed open or an exception cannot leave the terminal without an
    // echo. A signal unwinds nothing, so the same restore is installed as a
    // handler for as long as the terminal is out of its normal mode.
    $state = trim((string) shell_exec('stty -g 2>/dev/null'));
    $restore = static fn(): string|false => system($state === '' ? 'stty -cbreak echo' : 'stty ' . escapeshellarg($state));

    $trapped = static::trapSignals($restore);

    try {
      system('stty cbreak -echo');

      $res = fopen('php://stdin', 'r');

      if ($res === FALSE) {
        // @codeCoverageIgnoreStart
        return '';
        // @codeCoverageIgnoreEnd
      }

      $char = (string) fgetc($res);
      fclose($res);

      return $char;
    }
    finally {
      $restore();
      static::releaseSignals($trapped);
    }
  }

  /**
   * Restore the terminal when a signal ends the run.
   *
   * Interrupting a question is the ordinary way to leave one, and the shell
   * that gets its terminal back has to be able to echo what is typed into it.
   *
   * @param callable $restore
   *   Puts the terminal back the way it was found.
   *
   * @return array{async: bool, handlers: array<int, callable|int>}|null
   *   What was in place before, to be put back once the read is over, or NULL
   *   when the extension is absent and nothing was installed.
   */
  protected static function trapSignals(callable $restore): ?array {
    if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals') || !function_exists('pcntl_signal_get_handler')) {
      return NULL;
    }

    // Whatever a caller arranged for these signals is theirs, and this read
    // borrows them only for as long as the terminal is out of its normal mode.
    $previous = ['async' => pcntl_async_signals(TRUE), 'handlers' => []];

    foreach ([SIGINT, SIGTERM] as $signal) {
      $previous['handlers'][$signal] = pcntl_signal_get_handler($signal);

      // @codeCoverageIgnoreStart
      pcntl_signal($signal, static function (int $received) use ($restore): void {
        $restore();

        // The conventional status for a run a signal ended, so a caller reading
        // the exit code still learns which signal it was.
        exit(128 + $received);
      });
      // @codeCoverageIgnoreEnd
    }

    return $previous;
  }

  /**
   * Put back what was handling these signals before the read.
   *
   * @param array{async: bool, handlers: array<int, callable|int>}|null $previous
   *   What trapSignals() found in place, or NULL when it installed nothing.
   */
  protected static function releaseSignals(?array $previous): void {
    if ($previous === NULL) {
      return;
    }

    foreach ([SIGINT, SIGTERM] as $signal) {
      pcntl_signal($signal, $previous['handlers'][$signal] ?? SIG_DFL);
    }

    pcntl_async_signals($previous['async']);
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

    $terminal_width = static::terminalWidth();

    // (margin + 2 x border + 2 x padding) x 2 - 1 collapse divider width.
    $column_width = max(1, (int) floor(($terminal_width - (1 + (1 + 1) * 2) * 2 - 1) / 2));

    $header = [];
    $rows = [];
    foreach ($values as $key => $value) {
      if ($value === self::LIST_SECTION_TITLE) {
        $rows[] = [Tui::cyan(Tui::bold(static::normalizeText($key)))];
        continue;
      }

      $key = static::normalizeText($key);
      $value = static::normalizeText($value);

      $key = '  ' . wordwrap(static::normalizeText($key), $column_width + 2, PHP_EOL . '  ', TRUE);
      $value = wordwrap(static::normalizeText($value), $column_width, PHP_EOL, TRUE);

      $rows[] = [$key, $value];
    }

    static::info(PHP_EOL . static::normalizeText((string) $title) . PHP_EOL);
    static::table($header, $rows);
  }

  public static function box(string $content, ?string $title = NULL, ?int $width = NULL): void {
    $width ??= static::terminalWidth();

    // 1 margin + 1 border + 1 padding + 1 padding + 1 border + 1 margin.
    $offset = 6;

    $lines = explode(PHP_EOL, wordwrap($content, $width - $offset, PHP_EOL, TRUE));

    static::primitive(function (Output $out) use ($title, $lines): void {
      $out->box(static::normalizeText((string) $title), $lines);
    }, $width);
  }

  /**
   * Render a bordered table through the console output.
   *
   * @param array<int,string> $header
   *   The header cells; empty for a borderless block.
   * @param array<int,array<int,string>> $rows
   *   The rows.
   */
  protected static function table(array $header, array $rows): void {
    $header = array_values($header);
    $rows = array_map(array_values(...), array_values($rows));

    static::primitive(function (Output $out) use ($header, $rows): void {
      $out->table($header, $rows);
    });
  }

  /**
   * Draw with the library's primitives and write the result to the output.
   *
   * The primitives render to a terminal of their own, so they are pointed at a
   * memory stream and the drawn frame is forwarded to the console output the
   * rest of this class writes through - which keeps them capturable.
   *
   * @param \Closure $draw
   *   Receives the primitives to draw with.
   * @param int|null $width
   *   The width to draw within; NULL for the terminal's own.
   */
  protected static function primitive(\Closure $draw, ?int $width = NULL): void {
    $stream = fopen('php://memory', 'w+');

    if ($stream === FALSE) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    $width ??= static::terminalWidth();
    $draw(new Output(new Terminal($stream), new DefaultTheme($width)));

    rewind($stream);
    $drawn = (string) stream_get_contents($stream);
    fclose($stream);

    static::$output->write($drawn);
  }

  public static function center(string $text, int $width = 80, ?string $border = NULL): string {
    $lines = explode(PHP_EOL, $text);
    $centered_lines = [];

    // Find the maximum line length.
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
    return min($max, max(20, (new Terminal())->columns()));
  }

  public static function normalizeText(string $text): string {
    if (!Strings::isAsciiStart($text)) {
      return $text;
    }

    $text = preg_replace('/\s{2,}/', ' ', $text);

    preg_match_all('/\X/u', (string) $text, $matches);

    $utf8_chars = $matches[0];
    $utf8_chars = array_map(fn(string $char): string => Strings::isAsciiStart($char) ? $char : $char . static::utfPadding($char), $utf8_chars);

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
