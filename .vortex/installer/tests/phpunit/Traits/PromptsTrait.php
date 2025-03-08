<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

trait PromptsTrait {

  protected static function promptsSetUp(): void {
    // Override how validation is handled (it expects a user input on incorrect
    // validation) to throw an exception instead so that we can assert on it
    // in the tests.
    Prompt::validateUsing(function (Prompt $prompt) {
      if (is_callable($prompt->validate)) {
        $error = ($prompt->validate)($prompt->value());
        if ($error) {
          throw new \RuntimeException(sprintf('Validation for "%s" failed with error "%s".', $prompt->label, $error));
        }
      }

      return NULL;
    });
  }

  protected static function promptsTeardown(): void {
    Prompt::validateUsing(NULL);
  }

  protected static function promptsInput(array $responses): void {
    $inputs = [];

    foreach ($responses as $response) {
      // NULL response means to use the default value.
      if (!is_null($response)) {
        // Clear the input field default value.
        $inputs = array_merge($inputs, array_fill(0, 256, Key::BACKSPACE));
        // Enter the response, one character at a time.
        $inputs = array_merge($inputs, mb_str_split($response));
      }
      // Enter the response.
      $inputs[] = Key::ENTER;
    }

    // Pass inputs to the prompt's fake method.
    Prompt::fake($inputs);
  }

}
