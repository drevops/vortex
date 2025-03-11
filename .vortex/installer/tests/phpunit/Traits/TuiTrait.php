<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use DrevOps\Installer\Utils\Tui;
use Laravel\Prompts\Key;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;

trait TuiTrait {

  const TUI_MAX_QUESTIONS = 25;

  protected static function tuiSetUp(): void {
    Tui::init((new BufferedConsoleOutput()), FALSE);

    // Override how validation is handled (it expects a user input on incorrect
    // validation) to throw an exception instead so that we can assert on it
    // in the tests.
    // @note Prompts do not pass the transformed $value as an argument to the
    // static method, so this was added in a patch.
    Prompt::validateUsing(function (Prompt $prompt, mixed $value): null {
      if (is_callable($prompt->validate)) {
        $error = ($prompt->validate)($value);
        if ($error) {
          throw new \RuntimeException(sprintf('Validation failed with error "%s".', $error));
        }
      }

      return NULL;
    });
  }

  protected static function tuiTeardown(): void {
    Prompt::validateUsing(NULL);
  }

  protected static function tuiInput(array $responses, int $max = 100): void {
    $inputs = static::tuiNormalizeInput($responses, $max);

    // Pass inputs to the prompt's fake method.
    Prompt::fake($inputs);
  }

  protected static function tuiNormalizeInput(array $responses, int $max = 100): array {
    $inputs = [];

    foreach ($responses as $response) {
      // NULL response means to use the default value.
      if (!is_null($response)) {
        // Do not process the response if it is a special key.
        if (self::tuiIsKey($response)) {
          $inputs[] = $response;
          continue;
        }

        // Clear the input field default value.
        $inputs = array_merge($inputs, array_fill(0, $max, Key::BACKSPACE));
        // Enter the response, one character at a time.
        $inputs = array_merge($inputs, mb_str_split($response));
      }
      // Enter the response or accept the default value.
      $inputs[] = Key::ENTER;
    }

    return $inputs;
  }

  protected static function tuiIsKey(string $value): bool {
    return in_array($value, (new \ReflectionClass(Key::class))->getConstants());
  }

  protected static function tuiFill(int $skip = self::TUI_MAX_QUESTIONS, string ...$values): array {
    $suffix_length = max(self::TUI_MAX_QUESTIONS - $skip - count($values), 0);

    return array_merge(array_fill(0, $skip, NULL), $values, array_fill(0, $suffix_length, NULL));
  }

}
