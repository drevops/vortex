<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Fixtures\Handler;

/**
 * Test fixture: reusable static behaviour discovered for "machine_name".
 *
 * @package DrevOps\Tui\Tests\Fixtures\Handler
 */
class MachineName {

  /**
   * Validate a machine name.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && $value !== '' ? NULL : 'A machine name is required.';
  }

  /**
   * Normalize a machine name.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? strtolower($value) : $value;
  }

}
