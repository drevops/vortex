<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;

/**
 * Builds the widget for a field, seeded with the field's current value.
 *
 * @package DrevOps\Tui\Widget
 */
class WidgetFactory {

  /**
   * Create a widget for a field.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param mixed $current
   *   The current value to seed the widget with.
   *
   * @return \DrevOps\Tui\Widget\WidgetInterface
   *   The widget.
   */
  public function create(Field $field, mixed $current): WidgetInterface {
    $labels = $this->labels($field);

    return match ($field->type) {
      FieldType::Confirm => new ConfirmWidget((bool) $current),
      FieldType::Select => new SelectWidget($labels, is_string($current) ? $current : ''),
      FieldType::MultiSelect => new MultiSelectWidget($labels, $this->toList($current)),
      FieldType::MultiSearch => new MultiSearchWidget($labels, $this->toList($current)),
      FieldType::Suggest => new SuggestWidget(array_keys($labels), is_string($current) ? $current : ''),
      FieldType::Search => new SearchWidget($labels, is_string($current) ? $current : ''),
      FieldType::Number => new NumberWidget(is_int($current) || is_float($current) ? (string) (int) $current : ''),
      FieldType::Textarea => new TextareaWidget(is_string($current) ? $current : ''),
      FieldType::Password => new PasswordWidget(is_string($current) ? $current : ''),
      FieldType::Pause => new PauseWidget(),
      default => new TextWidget(is_string($current) ? $current : ''),
    };
  }

  /**
   * The option value => label map for a field.
   *
   * @param \DrevOps\Tui\Config\Field $field
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
