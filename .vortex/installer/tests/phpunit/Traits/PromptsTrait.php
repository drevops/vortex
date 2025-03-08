<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

trait PromptsTrait {

  protected static function promptsSetUp(): void {
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
      // Null response means to use the default value.
      if (!is_null($response)) {
        // Clear the input field default value.
        $inputs = array_merge($inputs, array_fill(0, 256, Key::BACKSPACE));
        $inputs = array_merge($inputs, mb_str_split($response));
      }
      $inputs[] = Key::ENTER;
    }

    Prompt::fake($inputs);
  }

}
