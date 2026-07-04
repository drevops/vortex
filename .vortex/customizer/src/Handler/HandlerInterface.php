<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Handler;

use DrevOps\Customizer\Config\Field;

/**
 * The contract a consumer's handler implements to provide field behaviour.
 *
 * Metadata (label, options, default, ...) comes from the configuration; a
 * handler adds project-specific behaviour and is auto-discovered by name.
 *
 * @package DrevOps\Customizer\Handler
 */
interface HandlerInterface {

  /**
   * Discover a value from the project/environment (update mode).
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field being discovered.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context (directory, answers, update flag).
   *
   * @return mixed
   *   The discovered value, or NULL when nothing was discovered.
   */
  public function discover(Field $field, Context $context): mixed;

  /**
   * Validate a collected value.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field being validated.
   * @param mixed $value
   *   The value to validate.
   *
   * @return string|null
   *   An error message, or NULL when the value is valid.
   */
  public function validate(Field $field, mixed $value): ?string;

  /**
   * Transform an accepted value before it is stored.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field being transformed.
   * @param mixed $value
   *   The accepted value.
   *
   * @return mixed
   *   The transformed value.
   */
  public function transform(Field $field, mixed $value): mixed;

  /**
   * Apply the collected answer (project-specific side effects).
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field being processed.
   * @param mixed $value
   *   The final value.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context.
   */
  public function process(Field $field, mixed $value, Context $context): void;

}
