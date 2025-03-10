<?php

namespace DrevOps\Installer\Utils;

use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class Tui {

  protected static OutputInterface $output;

  protected static ?string $hint;

  protected static string $message;

  public static function init(OutputInterface $output, Config $config) {
    static::$output = $output;

    Prompt::setOutput($output);

    if ($config->getNoInteraction()) {
      Prompt::interactive(FALSE);
    }
  }

  public static function output(): OutputInterface {
    if (!static::$output) {
      throw new \RuntimeException('Output not set. Call Tui::init() first.');
    }
    return static::$output;
  }

  public static function purple(string $text): string {
    return "\e[35m{$text}\e[39m";
  }

  public static function yellow(string $text): string {
    return "\e[33m{$text}\e[39m";
  }

  public static function dim(string $text): string {
    return "\e[2m{$text}\e[22m";
  }

  public static function cyan(string $text): string {
    return "\e[36m{$text}\e[39m";
  }

  public static function bgGreen(string $text): string {
    return "\e[42m{$text}\e[49m";
  }

  /**
   * Undim the text by resetting intensity.
   */
  public static function undim(string $text): string {
    return "\e[22m{$text}\e[22m";
  }

  public static function bgCyan(string $text): string {
    return "\e[46m{$text}\e[49m";
  }

  public static function bold(string $text): string {
    return "\e[1m{$text}\e[22m";
  }

  /**
   * Set the text color to green.
   *
   * @param string $text
   *
   * @return string
   */
  public static function green(string $text): string {
    return "\e[32m{$text}\e[39m";
  }

  public static function caretEol(string $text): string {
    $lines = explode(PHP_EOL, $text);
    $longest = max(array_map('strlen', $lines));

    return "\033[" . $longest . "C";
  }

  public static function caretDown() {
    return "\033[B";
  }

  public static function caretUp() {
    return "\033[A";
  }

  public static function action(
    \Closure|string $label,
    ?\Closure $action = NULL,
    \Closure|string|null $hint = NULL,
    \Closure|string|null $success = NULL,
    \Closure|string|null $failure = NULL,
  ) {
    $label = is_callable($label) ? $label() : $label;

    $return = spin($action, Tui::yellow($label));

    Tui::label($label, $hint && is_callable($hint) ? $hint() : $hint, is_array($return) ? $return : NULL, Strings::utfPos($label) === 0 ? 3 : 2);

    if ($return === FALSE) {
      Tui::error($failure ? is_callable($failure) ? $failure() : $failure : 'Failed');
    }
    else {
      Tui::ok($success ? is_callable($success) ? $success($return) : $success : $return);
    }
  }

  public static function error($message) {
    error($message);
  }

  public static function printBox(string $content, ?string $title = NULL, int $width = 80): void {
    $rows = [];

    $width = min($width, Tui::terminalWidth());
    $content = wordwrap($content, $width - 4, PHP_EOL, TRUE);

    if ($title) {
      $rows[] = [Tui::green($title)];
      $rows[] = [Tui::green(str_repeat('-', Strings::strlenPlain($title))) . PHP_EOL];
    }
    $rows[] = [$content];

    table([], $rows);
  }

  public static function ok($text = 'OK') {
    $ok = Tui::green("✅  " . $text);
    note($ok);
    note(str_repeat(Tui::caretUp(), 4));
  }

  public static function label(string $message, $hint = NULL, ?array $sublist = NULL, int $sublist_indent = 2) {
    $width = (new Terminal())->cols();
    $right_offset = 10;

    Tui::$message = Tui::yellow(wordwrap($message, $width - $right_offset, PHP_EOL));
    Tui::$hint = $hint ? wordwrap($hint, $width - $right_offset, PHP_EOL) : NULL;

    note(Tui::$message);
    note(str_repeat(Tui::caretUp(), 5));

    if (Tui::$hint) {
      note(Tui::dim(Tui::$hint));
      note(str_repeat(Tui::caretUp(), 5));
    }

    if (is_array($sublist)) {
      foreach ($sublist as $key => $value) {
        note(Tui::yellow(str_repeat(' ', $sublist_indent) . '- ' . $value));
        //        Check if is last
        note(str_repeat(Tui::caretUp(), $key === array_key_last($sublist) ? 4 : 5));
      }
    }
  }

  public static function formatYesNo(string|bool|int $value): string {
    return $value === '1' || $value === 1 || $value === TRUE ? 'Yes' : 'No';
  }

  public static function terminalWidth(): int {
    return (new Terminal())->cols();
  }

}
