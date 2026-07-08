<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "webroot" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Webroot extends AbstractHandler {

  const WEB = 'web';

  const DOCROOT = 'docroot';

  /**
   * Validate the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error message, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && Validate::isDirname($value) ? NULL : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * Normalize the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? rtrim($value, '/') : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $chosen = is_string($value) ? $value : '';
    $default = 'web';

    if ($chosen === $default) {
      return;
    }

    File::replaceContentAsync([
      sprintf('%s/', $default) => $chosen . '/',
      sprintf('%s\/', $default) => $chosen . '\/',
      sprintf(': %s', $default) => ': ' . $chosen,
      sprintf('!%s', $default) => '!' . $chosen,
      sprintf('/\/%s\//', $default) => '/' . $chosen . '/',
      sprintf('/\'\/%s\'/', $default) => "'/" . $chosen . "'",
    ]);

    File::replaceContentAsync(fn(string $content): string => preg_replace('/=' . preg_quote($default, '/') . '\b/', '=' . $chosen, $content) ?? $content);

    rename($context->directory . DIRECTORY_SEPARATOR . $default, $context->directory . DIRECTORY_SEPARATOR . $chosen);
  }

}
