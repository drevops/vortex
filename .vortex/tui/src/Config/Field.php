<?php

declare(strict_types=1);

namespace DrevOps\Tui\Config;

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
   *   The declared default value.
   * @param array<string,\DrevOps\Tui\Config\Option> $options
   *   Options for choice-based fields, keyed by option value.
   * @param bool $required
   *   Whether a value is required.
   * @param array<array-key,mixed>|null $when
   *   The raw conditional-visibility rule, evaluated by the engine.
   * @param array<array-key,mixed>|null $derive
   *   The raw derive rule (template + transform), evaluated by the engine.
   * @param array<array-key,mixed>|null $discover
   *   The raw discovery rule (dotenv / json / exists / scan), evaluated by the
   *   engine in update mode.
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
    public ?array $when = NULL,
    public ?array $derive = NULL,
    public ?array $discover = NULL,
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
