<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class Tui {

  const LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static string $message;

  protected static ?string $hint;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE): void {
    static::$output = $output;

    Prompt::setOutput($output);

    if (!$is_interactive) {
      Prompt::interactive(FALSE);
    }
  }

  public static function output(): OutputInterface {
    if (!isset(static::$output)) {
      throw new \RuntimeException('Output not set. Call Tui::init() first.');
    }
    return static::$output;
  }

  public static function action(
    \Closure|string $label,
    ?\Closure $action = NULL,
    \Closure|string|null $hint = NULL,
    \Closure|string|null $success = NULL,
    \Closure|string|null $failure = NULL,
  ): void {
    $label = is_callable($label) ? $label() : $label;

    if (!is_callable($action)) {
      throw new \InvalidArgumentException('Action must be callable.');
    }

    $label = static::normalizeText($label);

    // @phpstan-ignore-next-line
    $return = spin($action, static::yellow($label));

    static::label($label, $hint && is_callable($hint) ? $hint() : $hint, is_array($return) ? $return : NULL, Strings::utfPos($label) === 0 ? 3 : 2);

    if ($return === FALSE) {
      $failure = $failure && is_callable($failure) ? $failure() : $failure;
      static::error($failure ? static::normalizeText($failure) : 'FAILED');
    }
    else {
      $success = $success && is_callable($success) ? $success($return) : $success;
      static::ok($success ? static::normalizeText($success) : 'OK');
    }
  }

  public static function info(string $message): void {
    intro($message);
  }

  public static function note(string $message): void {
    note($message);
  }

  public static function error(string $message): void {
    error('❌  ' . $message);
  }

  public static function box(string $content, ?string $title = NULL, int $width = 80): void {
    $rows = [];

    $width = min($width, static::terminalWidth());
    $content = wordwrap($content, $width - 4, PHP_EOL, TRUE);

    if ($title) {
      $rows[] = [static::green($title)];
      $rows[] = [static::green(str_repeat('-', Strings::strlenPlain($title))) . PHP_EOL];
    }
    $rows[] = [$content];

    table([], $rows);
  }

  public static function ok(string $text = 'OK'): void {
    $ok = static::green(static::normalizeText("✅ " . $text));
    static::note($ok);
    static::note(str_repeat(static::caretUp(), 4));
  }

  public static function label(string $message, ?string $hint = NULL, ?array $sublist = NULL, int $sublist_indent = 2): void {
    $width = static::terminalWidth();
    $right_offset = 10;

    $message = static::normalizeText($message);

    static::$message = static::blue(wordwrap($message, $width - $right_offset, PHP_EOL));
    static::$hint = $hint ? wordwrap(static::normalizeText($hint), $width - $right_offset, PHP_EOL) : NULL;

    static::note(static::$message);
    static::note(str_repeat(static::caretUp(), 5));

    if (static::$hint) {
      static::note(str_repeat(' ', $sublist_indent) . static::dim(static::$hint));
      static::note(str_repeat(static::caretUp(), 5));
    }

    if (is_array($sublist)) {
      foreach ($sublist as $value) {
        static::note(str_repeat(' ', $sublist_indent) . static::dim($value));
        static::note(str_repeat(static::caretUp(), 5));
      }
    }
  }

  public static function terminalWidth(): int {
    return (new Terminal())->cols();
  }

  public static function green(string $text): string {
    return sprintf('[32m%s[39m', $text);
  }

  public static function blue(string $text): string {
    return sprintf('[34m%s[39m', $text);
  }

  public static function purple(string $text): string {
    return sprintf('[35m%s[39m', $text);
  }

  public static function yellow(string $text): string {
    return sprintf('[33m%s[39m', $text);
  }

  public static function cyan(string $text): string {
    return sprintf('[36m%s[39m', $text);
  }

  public static function bold(string $text): string {
    return sprintf('[1m%s[22m', $text);
  }

  public static function dim(string $text): string {
    return sprintf('[2m%s[22m', $text);
  }

  public static function undim(string $text): string {
    return sprintf('[22m%s[22m', $text);
  }

  public static function caretDown(): string {
    return "\033[B";
  }

  public static function caretUp(): string {
    return "\033[A";
  }

  public static function caretEol(string $text): string {
    $lines = explode(PHP_EOL, $text);
    $longest = max(array_map('strlen', $lines));

    return "\033[" . $longest . "C";
  }

  public static function list(array $values, ?string $title): void {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $values[$key] = implode(', ', $value);
      }
    }

    $header = [];
    $rows = [];
    foreach ($values as $key => $value) {
      if ($value === self::LIST_SECTION_TITLE) {
        $rows[] = [Tui::cyan(Tui::bold(static::normalizeText($key)))];
        continue;
      }

      $rows[] = ['  ' . static::normalizeText($key), static::normalizeText($value)];
    }

    intro(PHP_EOL . static::normalizeText($title) . PHP_EOL);
    table($header, $rows);
  }

  public static function normalizeText(string $text): string {
    if (is_null(Strings::utfPos($text))) {
      return $text;
    }

    $text = preg_replace('/\s{2,}/', ' ', $text);

    preg_match_all('/\X/u', $text, $matches);

    $utf8_chars = $matches[0];
    $utf8_chars = array_map(fn($char): string => Strings::utfPos($char) === 0 ? $char . static::utfPadding($char) : $char, $utf8_chars);

    return implode('', $utf8_chars);
  }

  public static function utfPadding(string $char): string {
    $padding = '';

    $len = strlen($char);
    $mblen = mb_strlen($char);

    // @see https://youtrack.jetbrains.com/issue/IJPL-101568/Terminal-display-Python-icon-in-wrong-width
    if (str_contains((string) getenv('TERMINAL_EMULATOR'), 'JetBrains') && ($mblen == 1 && $len < 4)) {
      $padding = ' ';
    }

    if (str_contains((string) getenv('TERM_PROGRAM'), 'Apple_Terminal') && ($mblen > 1 && $len < 8)) {
      $padding = ' ';

    }

    return $padding;
  }

}
