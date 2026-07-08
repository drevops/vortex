<?php

declare(strict_types=1);

namespace DrevOps\Tui\Builder;

use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Option;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\DiscoverInterface;

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
   * The conditional-visibility rule.
   */
  protected ?ConditionInterface $when = NULL;

  /**
   * The derive rule.
   */
  protected ?Derive $derive = NULL;

  /**
   * The discovery rule, or a custom detector closure.
   */
  protected DiscoverInterface|\Closure|null $discover = NULL;

  /**
   * The declared validator.
   */
  protected ?\Closure $validate = NULL;

  /**
   * The declared transformer.
   */
  protected ?\Closure $transform = NULL;

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
   *   The default value, or a `fn (Context): mixed` closure computing a
   *   dynamic default from the run context.
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
   * @param \DrevOps\Tui\Condition\ConditionInterface $condition
   *   The condition gating the field, evaluated by the engine.
   *
   * @return $this
   *   The builder.
   */
  public function when(ConditionInterface $condition): self {
    $this->when = $condition;

    return $this;
  }

  /**
   * Set the derive rule.
   *
   * @param \DrevOps\Tui\Derive\Derive $derive
   *   The derive rule, evaluated by the engine.
   *
   * @return $this
   *   The builder.
   */
  public function derive(Derive $derive): self {
    $this->derive = $derive;

    return $this;
  }

  /**
   * Set the discovery rule.
   *
   * @param \DrevOps\Tui\Discovery\DiscoverInterface|\Closure $discover
   *   The discovery rule - or a custom `fn (Context): mixed` detector -
   *   evaluated by the engine in update mode.
   *
   * @return $this
   *   The builder.
   */
  public function discover(DiscoverInterface|\Closure $discover): self {
    $this->discover = $discover;

    return $this;
  }

  /**
   * Set the declared validator.
   *
   * @param \Closure $validator
   *   The validator `fn (mixed $value): ?string` returning an error message,
   *   or NULL when the value is valid.
   *
   * @return $this
   *   The builder.
   */
  public function validate(\Closure $validator): self {
    $this->validate = $validator;

    return $this;
  }

  /**
   * Set the declared transformer.
   *
   * @param \Closure $transformer
   *   The transformer `fn (mixed $value): mixed` normalizing an accepted
   *   value.
   *
   * @return $this
   *   The builder.
   */
  public function transform(\Closure $transformer): self {
    $this->transform = $transformer;

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
      $this->when,
      $this->derive,
      $this->discover,
      $this->validate,
      $this->transform,
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
