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

}
