<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Fixtures\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;

/**
 * Test fixture: a spy handler recording lifecycle calls for the "spy" field.
 *
 * @package DrevOps\Customizer\Tests\Fixtures\Handler
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

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    self::$calls[] = 'process:' . implode(',', array_keys($context->answers));
  }

}
