<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Fixtures\Handler;

/**
 * Test fixture: static behaviour recording lifecycle calls for "spy".
 *
 * @package DrevOps\Tui\Tests\Fixtures\Handler
 */
class Spy {

  /**
   * The ordered log of lifecycle calls.
   *
   * @var string[]
   */
  public static array $calls = [];

  /**
   * Record and pass validation.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   Always NULL.
   */
  public static function validate(mixed $value): ?string {
    self::$calls[] = 'validate';

    return NULL;
  }

  /**
   * Record and mark the value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The marked value.
   */
  public static function transform(mixed $value): mixed {
    self::$calls[] = 'transform';

    return is_string($value) ? $value . '!' : $value;
  }

}
