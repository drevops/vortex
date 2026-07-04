<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;

/**
 * Shared widget behaviour: accept/cancel, validation and transformation.
 *
 * @package DrevOps\Customizer\Widget
 */
abstract class AbstractWidget implements WidgetInterface {

  /**
   * Whether a valid value has been accepted.
   */
  protected bool $complete = FALSE;

  /**
   * Whether the widget was cancelled.
   */
  protected bool $cancelled = FALSE;

  /**
   * The current validation error, if any.
   */
  protected ?string $error = NULL;

  /**
   * The accepted, transformed value once complete.
   */
  protected mixed $accepted = NULL;

  /**
   * Construct a widget.
   *
   * @param \Closure|null $validate
   *   Optional validator `fn(mixed $value): ?string` returning an error message
   *   or NULL when the value is valid.
   * @param \Closure|null $transform
   *   Optional transformer `fn(mixed $value): mixed` applied on accept.
   */
  public function __construct(
    protected ?\Closure $validate = NULL,
    protected ?\Closure $transform = NULL,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(): bool {
    return $this->complete;
  }

  /**
   * {@inheritdoc}
   */
  public function isCancelled(): bool {
    return $this->cancelled;
  }

  /**
   * {@inheritdoc}
   */
  public function error(): ?string {
    return $this->error;
  }

  /**
   * {@inheritdoc}
   */
  public function value(): mixed {
    return $this->complete ? $this->accepted : $this->liveValue();
  }

  /**
   * The in-progress value before acceptance.
   *
   * @return mixed
   *   The current, not-yet-accepted value.
   */
  abstract protected function liveValue(): mixed;

  /**
   * Cancel the widget when the key is Escape.
   *
   * @param \DrevOps\Customizer\Input\Key $key
   *   The key to test.
   *
   * @return bool
   *   TRUE when the key cancelled the widget.
   */
  protected function handleCancel(Key $key): bool {
    if ($key->is(KeyName::Escape)) {
      $this->cancelled = TRUE;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate and, when valid, transform a value and complete the widget.
   *
   * @param mixed $value
   *   The candidate value.
   *
   * @return bool
   *   TRUE when the value was accepted; FALSE when validation failed.
   */
  protected function accept(mixed $value): bool {
    $error = $this->validate instanceof \Closure ? ($this->validate)($value) : NULL;
    if (is_string($error) && $error !== '') {
      $this->error = $error;

      return FALSE;
    }

    $this->error = NULL;
    $this->accepted = $this->transform instanceof \Closure ? ($this->transform)($value) : $value;
    $this->complete = TRUE;

    return TRUE;
  }

}
