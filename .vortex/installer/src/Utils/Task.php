<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use function Laravel\Prompts\spin;

class Task {

  protected static string $message;

  protected static ?string $hint;

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

    $label = Tui::normalizeText($label);

    // @phpstan-ignore-next-line
    $return = spin($action, Tui::yellow($label));

    self::label($label, $hint && is_callable($hint) ? $hint() : $hint, is_array($return) ? $return : NULL, Strings::isAsciiStart($label) ? 2 : 3);

    if ($return === FALSE) {
      $failure = $failure && is_callable($failure) ? $failure() : $failure;
      Tui::error($failure ? Tui::normalizeText($failure) : 'FAILED');
    }
    else {
      $success = $success && is_callable($success) ? $success($return) : $success;
      static::ok($success ? Tui::normalizeText($success) : 'OK');
    }
  }

  protected static function label(string $message, ?string $hint = NULL, ?array $sublist = NULL, int $sublist_indent = 3): void {
    $width = Tui::terminalWidth();
    $right_offset = 10;

    $message = '✦ ' . $message;
    $message = Tui::normalizeText($message);

    static::$message = Tui::blue(wordwrap($message, $width - $right_offset, PHP_EOL));
    static::$hint = $hint ? wordwrap(Tui::normalizeText($hint), $width - $right_offset, PHP_EOL) : NULL;

    Tui::note(static::$message);
    Tui::note(str_repeat(Tui::caretUp(), 5));

    if (static::$hint) {
      Tui::note(str_repeat(' ', $sublist_indent) . Tui::dim(static::$hint));
      Tui::note(str_repeat(Tui::caretUp(), 5));
    }

    if (is_array($sublist)) {
      foreach ($sublist as $value) {
        Tui::note(str_repeat(' ', $sublist_indent) . Tui::dim($value));
        Tui::note(str_repeat(Tui::caretUp(), 5));
      }
    }
  }

  protected static function ok(string $text = 'OK'): void {
    $ok = Tui::green(Tui::normalizeText('✓ ' . $text));
    Tui::note($ok);
    Tui::note(str_repeat(Tui::caretUp(), 4));
  }

}
