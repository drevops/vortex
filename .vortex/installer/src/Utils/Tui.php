<?php

namespace DrevOps\Installer\Utils;

use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class Tui {

  public const string LIST_SECTION_TITLE = '---SECTION_TITLE---';

  protected static OutputInterface $output;

  protected static ?string $hint;

  protected static string $message;

  public static function init(OutputInterface $output, bool $is_interactive = TRUE) {
    static::$output = $output;

    Prompt::setOutput($output);

    if (!$is_interactive) {
      Prompt::interactive(FALSE);
    }
  }

  public static function output(): OutputInterface {
    if (!static::$output) {
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
  ) {
    $label = is_callable($label) ? $label() : $label;

    $return = spin($action, static::yellow($label));

    static::label($label, $hint && is_callable($hint) ? $hint() : $hint, is_array($return) ? $return : NULL, Strings::utfPos($label) === 0 ? 3 : 2);

    if ($return === FALSE) {
      static::error($failure ? is_callable($failure) ? $failure() : $failure : 'Failed');
    }
    else {
      static::ok($success ? is_callable($success) ? $success($return) : $success : $return);
    }
  }

  public static function info(string $message): void {
    info($message);
  }

  public static function note(string $message): void {
    note($message);
  }

  public static function error(string $message): void {
    error($message);
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

  public static function ok($text = 'OK') {
    $ok = static::green("✅  " . $text);
    static::note($ok);
    static::note(str_repeat(static::caretUp(), 4));
  }

  public static function label(string $message, $hint = NULL, ?array $sublist = NULL, int $sublist_indent = 2) {
    $width = (new Terminal())->cols();
    $right_offset = 10;

    static::$message = static::yellow(wordwrap($message, $width - $right_offset, PHP_EOL));
    static::$hint = $hint ? wordwrap($hint, $width - $right_offset, PHP_EOL) : NULL;

    static::note(static::$message);
    static::note(str_repeat(static::caretUp(), 5));

    if (static::$hint) {
      static::note(static::dim(static::$hint));
      static::note(str_repeat(static::caretUp(), 5));
    }

    if (is_array($sublist)) {
      foreach ($sublist as $key => $value) {
        static::note(static::yellow(str_repeat(' ', $sublist_indent) . '- ' . $value));
        static::note(str_repeat(static::caretUp(), $key === array_key_last($sublist) ? 4 : 5));
      }
    }
  }

  public static function terminalWidth(): int {
    return (new Terminal())->cols();
  }

  public static function green(string $text): string {
    return "\e[32m{$text}\e[39m";
  }

  public static function purple(string $text): string {
    return "\e[35m{$text}\e[39m";
  }

  public static function yellow(string $text): string {
    return "\e[33m{$text}\e[39m";
  }

  public static function cyan(string $text): string {
    return "\e[36m{$text}\e[39m";
  }

  public static function bold(string $text): string {
    return "\e[1m{$text}\e[22m";
  }

  public static function dim(string $text): string {
    return "\e[2m{$text}\e[22m";
  }

  public static function undim(string $text): string {
    return "\e[22m{$text}\e[22m";
  }

  public static function caretDown() {
    return "\033[B";
  }

  public static function caretUp() {
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
        $rows[] = [Tui::cyan(Tui::bold($key))];
        continue;
      }

      $rows[] = ['  ' . $key, $value];
    }

    intro(PHP_EOL . $title . PHP_EOL);
    table($header, $rows);
  }

}
