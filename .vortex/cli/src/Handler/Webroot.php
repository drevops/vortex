<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "webroot" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Webroot extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isDirname($value) ? NULL : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
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
