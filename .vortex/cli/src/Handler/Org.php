<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "org" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Org extends AbstractFieldHandler {

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
    return is_string($value) && Validate::isFilledLabel($value) ? NULL : 'Please enter a valid organization name.';
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
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    File::replaceContentAsync('YOURORG', is_string($value) ? $value : '');
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'org';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Organization name';
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
    return 'We will use this name in the project and documentation.';
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
    return new Derive('{{name}} Org');
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 370;
  }

}
