<?php

declare(strict_types=1);

namespace DrevOps\Tui\Schema;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Discovery\DiscoverInterface;

/**
 * Generates a machine-readable schema of every configured question.
 *
 * The shape mirrors the installer's schema (`{prompts: [{id, type, label,
 * description, options, default, required, depends_on}]}`) and extends each
 * entry with the TUI's native `when`, `derive` and `discover` rules, so
 * existing tooling keeps working while richer rules are available.
 *
 * @package DrevOps\Tui\Schema
 */
class SchemaGenerator {

  /**
   * Construct a generator.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration to describe.
   */
  public function __construct(protected Config $config) {
  }

  /**
   * Generate the schema.
   *
   * @return array<string,mixed>
   *   The schema, keyed by `prompts`.
   */
  public function generate(): array {
    $prompts = [];

    foreach ($this->config->fields() as $field) {
      $prompts[] = [
        'id' => $field->id,
        'type' => $field->type->value,
        'label' => $field->label,
        'description' => $field->description,
        'options' => $this->options($field),
        'default' => $field->default instanceof \Closure ? NULL : $field->default,
        'required' => $field->required,
        'when' => $field->when?->toArray(),
        'derive' => $field->derive?->toArray(),
        'discover' => $field->discover instanceof DiscoverInterface ? $field->discover->toArray() : NULL,
        'depends_on' => $field->when === NULL ? [] : $field->when->fields(),
      ];
    }

    return ['prompts' => $prompts];
  }

  /**
   * Describe a field's options.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   *
   * @return array<int,array<string,string>>
   *   The options as a list of {value, label, description}.
   */
  protected function options(Field $field): array {
    $out = [];

    foreach ($field->options as $option) {
      $out[] = [
        'value' => $option->value,
        'label' => $option->label,
        'description' => $option->description,
      ];
    }

    return $out;
  }

}
