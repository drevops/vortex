<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Fixtures\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;

/**
 * Test fixture: a spy handler recording lifecycle calls for the "spy" field.
 *
 * @package DrevOps\Tui\Tests\Fixtures\Handler
 */
class Spy extends AbstractHandler {

  /**
   * The ordered log of lifecycle calls, shared across instances.
   *
   * @var string[]
   */
  public static array $calls = [];

  /**
   * {@inheritdoc}
   */
  public function discover(Field $field, Context $context): mixed {
    self::$calls[] = 'discover';

    return 'discovered';
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    self::$calls[] = 'validate';

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    self::$calls[] = 'transform';

    return is_string($value) ? $value . '!' : $value;
  }

}
