<?php

declare(strict_types=1);

namespace DrevOps\Tui\Config;

use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\DiscoverInterface;

/**
 * A single question in the configuration model.
 *
 * @package DrevOps\Tui\Config
 */
final readonly class Field {

  /**
   * Construct a field.
   *
   * @param string $id
   *   The unique field id.
   * @param string $label
   *   The human-readable label.
   * @param string $description
   *   The help text.
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The widget type.
   * @param mixed $default
   *   The declared default value, or a `fn (Context): mixed` closure computing
   *   a dynamic default from the run context.
   * @param array<string,\DrevOps\Tui\Config\Option> $options
   *   Options for choice-based fields, keyed by option value.
   * @param bool $required
   *   Whether a value is required.
   * @param \DrevOps\Tui\Condition\ConditionInterface|null $when
   *   The conditional-visibility rule, evaluated by the engine.
   * @param \DrevOps\Tui\Derive\Derive|null $derive
   *   The derive rule, evaluated by the engine.
   * @param \DrevOps\Tui\Discovery\DiscoverInterface|\Closure|null $discover
   *   The discovery rule - or a custom `fn (Context): mixed` detector -
   *   evaluated by the engine in update mode.
   * @param \Closure|null $validate
   *   A declared validator `fn (mixed $value): ?string` returning an error
   *   message, or NULL when the value is valid.
   * @param \Closure|null $transform
   *   A declared transformer `fn (mixed $value): mixed` normalizing an
   *   accepted value.
   * @param int $weight
   *   The processing weight: lower runs earlier. Fields of equal weight process
   *   in reverse declaration order, so specific replacements run before generic
   *   ones without any weights at all.
   */
  public function __construct(
    public string $id,
    public string $label,
    public string $description,
    public FieldType $type,
    public mixed $default,
    public array $options = [],
    public bool $required = FALSE,
    public ?ConditionInterface $when = NULL,
    public ?Derive $derive = NULL,
    public DiscoverInterface|\Closure|null $discover = NULL,
    public ?\Closure $validate = NULL,
    public ?\Closure $transform = NULL,
    public int $weight = 0,
  ) {
  }

  /**
   * Get an option by its value.
   */
  public function option(string $value): ?Option {
    return $this->options[$value] ?? NULL;
  }

}
