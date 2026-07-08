<?php

declare(strict_types=1);

namespace DrevOps\Tui\Schema;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;

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
        'default' => $field->default,
        'required' => $field->required,
        'when' => $field->when,
        'derive' => $field->derive,
        'discover' => $field->discover,
        'depends_on' => $this->dependsOn($field->when),
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

  /**
   * Extract the field ids a `when` rule depends on.
   *
   * @param array<array-key,mixed>|null $when
   *   The condition rule.
   *
   * @return list<string>
   *   The referenced field ids.
   */
  protected function dependsOn(?array $when): array {
    if ($when === NULL) {
      return [];
    }

    $ids = [];
    $this->collectFieldRefs($when, $ids);

    return array_values(array_unique($ids));
  }

  /**
   * Recursively collect `field` references from a condition rule.
   *
   * @param array<array-key,mixed> $when
   *   The condition rule.
   * @param list<string> $ids
   *   Accumulator, populated in place.
   */
  protected function collectFieldRefs(array $when, array &$ids): void {
    foreach ($when as $key => $value) {
      if ($key === 'field' && is_scalar($value)) {
        $ids[] = (string) $value;
      }
      elseif (is_array($value)) {
        $this->collectFieldRefs($value, $ids);
      }
    }
  }

}
