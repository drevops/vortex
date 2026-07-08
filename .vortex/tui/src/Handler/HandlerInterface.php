<?php

declare(strict_types=1);

namespace DrevOps\Tui\Handler;

use DrevOps\Tui\Config\Field;

/**
 * The contract a consumer's handler implements to provide field behaviour.
 *
 * Metadata (label, options, default, ...) comes from the configuration; a
 * handler adds project-specific behaviour and is auto-discovered by name.
 *
 * @package DrevOps\Tui\Handler
 */
interface HandlerInterface {

  /**
   * Provide a dynamic default computed from the run context.
   *
   * Runs when no input, environment or discovered value is available, before
   * the static declared default. Return NULL to fall back to the declared
   * default.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context (directory, answers, update flag).
   *
   * @return mixed
   *   The dynamic default value, or NULL to use the declared default.
   */
  public function default(Field $field, Context $context): mixed;

  /**
   * Discover a value from the project/environment (update mode).
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field being discovered.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context (directory, answers, update flag).
   *
   * @return mixed
   *   The discovered value, or NULL when nothing was discovered.
   */
  public function discover(Field $field, Context $context): mixed;

  /**
   * Validate a collected value.
   *
   * @param \DrevOps\Tui\Config\Field $field
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
   * @param \DrevOps\Tui\Config\Field $field
   *   The field being transformed.
   * @param mixed $value
   *   The accepted value.
   *
   * @return mixed
   *   The transformed value.
   */
  public function transform(Field $field, mixed $value): mixed;

}
