<?php

declare(strict_types=1);

namespace DrevOps\Tui\Handler;

use DrevOps\Tui\Config\Field;

/**
 * Base handler with no-op defaults; concrete handlers override as needed.
 *
 * @package DrevOps\Tui\Handler
 */
abstract class AbstractHandler implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function default(Field $field, Context $context): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(Field $field, Context $context): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return $value;
  }

}
