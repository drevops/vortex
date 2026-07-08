<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "domain" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Domain extends AbstractFieldHandler {

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
    return is_string($value) && Validate::isDomain($value) ? NULL : 'Please enter a valid domain name.';
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

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'domain';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Public domain';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Text;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Domain name without protocol and trailing slash.';
  }

  /**
   * {@inheritdoc}
   */
  public static function required(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function derive(): ?Derive {
    return new Derive('{{machine_name}}.com', 'host');
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 280;
  }

}
