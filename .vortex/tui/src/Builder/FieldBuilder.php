<?php

declare(strict_types=1);

namespace DrevOps\Tui\Builder;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Option;

/**
 * A fluent builder for a single Field.
 *
 * @package DrevOps\Tui\Builder
 */
final class FieldBuilder {

  /**
   * The help text.
   */
  protected string $description = '';

  /**
   * Whether an explicit default was set (otherwise the type default is used).
   */
  protected bool $hasDefault = FALSE;

  /**
   * The explicit default value, when set.
   */
  protected mixed $default = NULL;

  /**
   * The options, keyed by value.
   *
   * @var array<string,\DrevOps\Tui\Config\Option>
   */
  protected array $options = [];

  /**
   * Whether a value is required.
   */
  protected bool $required = FALSE;

  /**
   * Whether the value must be a machine name.
   */
  protected bool $machine = FALSE;

  /**
   * The raw conditional-visibility rule.
   *
   * @var array<array-key,mixed>|null
   */
  protected ?array $when = NULL;

  /**
   * The raw derive rule.
   *
   * @var array<array-key,mixed>|null
   */
  protected ?array $derive = NULL;

  /**
   * The raw discovery rule.
   *
   * @var array<array-key,mixed>|null
   */
  protected ?array $discover = NULL;

  /**
   * The processing weight.
   */
  protected int $weight = 0;

  /**
   * Construct a field builder.
   *
   * @param string $id
   *   The unique field id.
   * @param string $label
   *   The human-readable label.
   * @param \DrevOps\Tui\Config\FieldType $fieldType
   *   The widget type.
   */
  public function __construct(protected string $id, protected string $label, protected FieldType $fieldType) {
  }

  /**
   * Set the help text.
   *
   * @param string $description
   *   The help text.
   *
   * @return $this
   *   The builder.
   */
  public function description(string $description): self {
    $this->description = $description;

    return $this;
  }

  /**
   * Set the default value.
   *
   * @param mixed $default
   *   The default value.
   *
   * @return $this
   *   The builder.
   */
  public function default(mixed $default): self {
    $this->hasDefault = TRUE;
    $this->default = $default;

    return $this;
  }

  /**
   * Mark the field required.
   *
   * @param bool $required
   *   Whether a value is required.
   *
   * @return $this
   *   The builder.
   */
  public function required(bool $required = TRUE): self {
    $this->required = $required;

    return $this;
  }

  /**
   * Mark the field's value a machine name.
   *
   * @param bool $machine
   *   Whether the value must be a machine name.
   *
   * @return $this
   *   The builder.
   */
  public function machine(bool $machine = TRUE): self {
    $this->machine = $machine;

    return $this;
  }

  /**
   * Set the processing weight.
   *
   * @param int $weight
   *   The weight; lower runs earlier.
   *
   * @return $this
   *   The builder.
   */
  public function weight(int $weight): self {
    $this->weight = $weight;

    return $this;
  }

  /**
   * Set the conditional-visibility rule.
   *
   * @param array<array-key,mixed> $rule
   *   The raw rule, evaluated by the engine.
   *
   * @return $this
   *   The builder.
   */
  public function when(array $rule): self {
    $this->when = $rule;

    return $this;
  }

  /**
   * Set the derive rule.
   *
   * @param array<array-key,mixed> $rule
   *   The raw rule (template + transform), evaluated by the engine.
   *
   * @return $this
   *   The builder.
   */
  public function derive(array $rule): self {
    $this->derive = $rule;

    return $this;
  }

  /**
   * Set the discovery rule.
   *
   * @param array<array-key,mixed> $rule
   *   The raw rule, evaluated by the engine in update mode.
   *
   * @return $this
   *   The builder.
   */
  public function discover(array $rule): self {
    $this->discover = $rule;

    return $this;
  }

  /**
   * Add a single option.
   *
   * @param string $value
   *   The option value.
   * @param string $label
   *   The option label (defaults to the value).
   * @param string $description
   *   The option description.
   *
   * @return $this
   *   The builder.
   */
  public function option(string $value, string $label = '', string $description = ''): self {
    $this->options[$value] = new Option($value, $label === '' ? $value : $label, $description);

    return $this;
  }

  /**
   * Add several options from a value => label map.
   *
   * @param array<array-key,string> $options
   *   The options, keyed by value with a label.
   *
   * @return $this
   *   The builder.
   */
  public function options(array $options): self {
    foreach ($options as $value => $label) {
      $this->option((string) $value, $label);
    }

    return $this;
  }

  /**
   * Build the immutable Field.
   *
   * @return \DrevOps\Tui\Config\Field
   *   The field.
   */
  public function build(): Field {
    return new Field(
      $this->id,
      $this->label,
      $this->description,
      $this->fieldType,
      $this->hasDefault ? $this->default : $this->defaultFor($this->fieldType),
      $this->options,
      $this->required,
      $this->machine,
      $this->when,
      $this->derive,
      $this->discover,
      $this->weight,
    );
  }

  /**
   * The engine default for a field type when none is declared.
   *
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The field type.
   *
   * @return mixed
   *   The type default.
   */
  protected function defaultFor(FieldType $type): mixed {
    return match ($type) {
      FieldType::MultiSelect => [],
      FieldType::Confirm => FALSE,
      default => '',
    };
  }

}
