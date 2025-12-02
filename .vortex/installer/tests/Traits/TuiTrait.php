<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Traits;

use DrevOps\VortexInstaller\Utils\Tui;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;

trait TuiTrait {

  const TUI_MAX_QUESTIONS = 25;

  protected static function tuiSetUp(): void {
    Tui::init((new BufferedConsoleOutput()), FALSE);

    // Override how validation is handled (it expects a user input on incorrect
    // validation) to throw an exception instead so that we can assert on it
    // in the tests.
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

  /**
   * Helper to create command options array with '--' prefix.
   *
   * @param array<string, mixed> $options
   *   Array of option constants as keys and their values.
   *
   * @return array<string, mixed>
   *   Array with '--' prefix added to each option key.
   */
  protected static function tuiOptions(array $options): array {
    $result = [];
    foreach ($options as $option => $value) {
      $result['--' . $option] = $value;
    }
    return $result;
  }

}
