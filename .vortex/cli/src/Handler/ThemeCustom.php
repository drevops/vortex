<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Derive\Derive;

/**
 * Reusable behaviour for the "theme_custom" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ThemeCustom extends AbstractFieldHandler {

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
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.';
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
  public static function id(): string {
    return 'theme_custom';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Custom theme machine name';
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
    return 'We will use this name as a custom theme name.';
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
  public static function when(): ?ConditionInterface {
    return new Condition('theme', eq: Theme::CUSTOM);
  }

  /**
   * {@inheritdoc}
   */
  public static function derive(): ?Derive {
    return new Derive('{{machine_name}}', 'machine');
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 330;
  }

}
