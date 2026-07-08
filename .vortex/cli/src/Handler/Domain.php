<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "domain" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Domain extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isDomain($value) ? NULL : 'Please enter a valid domain name.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? Validate::domain($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $domain = is_string($value) ? $value : '';

    // Replace in regular expressions.
    File::replaceContentAsync(preg_quote('your-site-domain.example'), preg_quote($domain));

    // Replace scalar values.
    File::replaceContentAsync('your-site-domain.example', $domain);
  }

}
