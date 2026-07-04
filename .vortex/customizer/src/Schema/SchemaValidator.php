<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Schema;

use DrevOps\Customizer\Condition\ConditionEvaluator;
use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;

/**
 * Validates an answer set against the configuration.
 *
 * Checks value types, option membership and required questions, and skips
 * questions whose `when` condition is not met by the answer set. Returns a
 * list of actionable error messages (empty when the set is valid).
 *
 * @package DrevOps\Customizer\Schema
 */
class SchemaValidator {

  /**
   * The condition evaluator for activation checks.
   */
  protected ConditionEvaluator $evaluator;

  /**
   * Construct a validator.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration to validate against.
   */
  public function __construct(protected Config $config) {
    $this->evaluator = new ConditionEvaluator();
  }

  /**
   * Validate an answer set.
   *
   * @param array<string,mixed> $answers
   *   The answers keyed by question id.
   *
   * @return list<string>
   *   The validation errors; empty when valid.
   */
  public function validate(array $answers): array {
    $errors = [];

    foreach ($this->config->fields() as $field) {
      if ($field->when !== NULL && !$this->evaluator->matches($field->when, $answers)) {
        continue;
      }

      if (!array_key_exists($field->id, $answers)) {
        if ($field->required) {
          $errors[] = sprintf('Missing required question "%s".', $field->id);
        }

        continue;
      }

      $error = $this->validateValue($field, $answers[$field->id]);
      if ($error !== NULL) {
        $errors[] = $error;
      }
    }

    foreach (array_keys($answers) as $id) {
      if (!$this->config->field((string) $id) instanceof Field) {
        $errors[] = sprintf('Unknown question "%s".', (string) $id);
      }
    }

    return $errors;
  }

  /**
   * Validate a single value against its field.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   The first error, or NULL when valid.
   */
  protected function validateValue(Field $field, mixed $value): ?string {
    if (!$this->isType($field->type, $value)) {
      return sprintf('Question "%s" must be %s.', $field->id, $this->typeName($field->type));
    }

    if ($field->required && $this->isEmpty($value)) {
      return sprintf('Question "%s" is required.', $field->id);
    }

    return $this->checkOptions($field, $value);
  }

  /**
   * Whether the value matches the field type.
   *
   * @param \DrevOps\Customizer\Config\FieldType $type
   *   The field type.
   * @param mixed $value
   *   The value.
   *
   * @return bool
   *   TRUE when the value matches.
   */
  protected function isType(FieldType $type, mixed $value): bool {
    return match ($type) {
      FieldType::Confirm => is_bool($value),
      FieldType::MultiSelect => is_array($value),
      default => is_string($value),
    };
  }

  /**
   * A human name for a field type.
   *
   * @param \DrevOps\Customizer\Config\FieldType $type
   *   The field type.
   *
   * @return string
   *   The human name.
   */
  protected function typeName(FieldType $type): string {
    return match ($type) {
      FieldType::Confirm => 'a boolean',
      FieldType::MultiSelect => 'a list',
      default => 'a string',
    };
  }

  /**
   * Whether a value is empty.
   *
   * @param mixed $value
   *   The value.
   *
   * @return bool
   *   TRUE when empty.
   */
  protected function isEmpty(mixed $value): bool {
    return in_array($value, ['', [], NULL], TRUE);
  }

  /**
   * Check option membership for choice fields.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error, or NULL when valid.
   */
  protected function checkOptions(Field $field, mixed $value): ?string {
    if ($field->options === []) {
      return NULL;
    }

    $valid = array_keys($field->options);

    if ($field->type === FieldType::Select && is_string($value) && !in_array($value, $valid, TRUE)) {
      return sprintf('Question "%s" must be one of: %s.', $field->id, implode(', ', $valid));
    }

    if ($field->type === FieldType::MultiSelect && is_array($value)) {
      foreach ($value as $item) {
        if (!in_array($item, $valid, TRUE)) {
          return sprintf('Question "%s" contains an invalid option "%s".', $field->id, is_scalar($item) ? (string) $item : '?');
        }
      }
    }

    return NULL;
  }

}
