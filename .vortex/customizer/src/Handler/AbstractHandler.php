<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Handler;

use DrevOps\Customizer\Config\Field;

/**
 * Base handler with no-op defaults; concrete handlers override as needed.
 *
 * @package DrevOps\Customizer\Handler
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

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
  }

}
