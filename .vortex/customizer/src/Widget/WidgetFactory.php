<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;

/**
 * Builds the widget for a field, seeded with the field's current value.
 *
 * @package DrevOps\Customizer\Widget
 */
class WidgetFactory {

  /**
   * Create a widget for a field.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param mixed $current
   *   The current value to seed the widget with.
   *
   * @return \DrevOps\Customizer\Widget\WidgetInterface
   *   The widget.
   */
  public function create(Field $field, mixed $current): WidgetInterface {
    $labels = $this->labels($field);

    return match ($field->type) {
      FieldType::Confirm => new ConfirmWidget((bool) $current),
      FieldType::Select => new SelectWidget($labels, is_string($current) ? $current : ''),
      FieldType::MultiSelect => new MultiSelectWidget($labels, $this->toList($current)),
      FieldType::Suggest => new SuggestWidget(array_keys($labels), is_string($current) ? $current : ''),
      default => new TextWidget(is_string($current) ? $current : ''),
    };
  }

  /**
   * The option value => label map for a field.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   *
   * @return array<string,string>
   *   The labels keyed by value.
   */
  protected function labels(Field $field): array {
    $out = [];

    foreach ($field->options as $option) {
      $out[$option->value] = $option->label;
    }

    return $out;
  }

  /**
   * Coerce a value to a list of strings.
   *
   * @param mixed $value
   *   The value.
   *
   * @return list<string>
   *   The list of strings.
   */
  protected function toList(mixed $value): array {
    if (!is_array($value)) {
      return [];
    }

    $out = [];
    foreach ($value as $item) {
      if (is_string($item)) {
        $out[] = $item;
      }
    }

    return $out;
  }

}
