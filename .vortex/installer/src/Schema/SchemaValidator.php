<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Schema;

use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\PromptType;

/**
 * Validates prompt values against handler definitions.
 *
 * @package DrevOps\VortexInstaller\Schema
 */
class SchemaValidator {

  /**
   * Constructor.
   *
   * @param array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface> $handlers
   *   An associative array of handler instances keyed by handler ID.
   */
  public function __construct(
    protected array $handlers,
  ) {
  }

  /**
   * Validate a config array against handlers.
   *
   * @param array<string, mixed> $config
   *   The config array to validate, keyed by handler ID.
   *
   * @return array<string, mixed>
   *   Validation result with keys: valid, errors, warnings, resolved.
   */
  public function validate(array $config): array {
    $errors = [];
    $warnings = [];
    $resolved = [];

    // Normalize config keys to handler IDs.
    $normalized = $this->normalizeConfig($config);

    // Report unknown prompt IDs.
    $known_ids = array_keys($this->handlers);
    foreach (array_keys($normalized) as $key) {
      if (!in_array($key, $known_ids, TRUE)) {
        $errors[] = [
          'prompt' => $key,
          'message' => sprintf('Unknown prompt "%s".', $key),
        ];
      }
    }

    foreach ($this->handlers as $id => $handler) {
      if (in_array($id, SchemaGenerator::getExcludedHandlers(), TRUE)) {
        continue;
      }

      $has_value = array_key_exists($id, $normalized);
      $value = $normalized[$id] ?? NULL;

      // Skip prompts not provided in the input.
      if (!$has_value) {
        continue;
      }

      // Check dependency conditions for provided prompts.
      $depends_on = $handler->dependsOn();
      if ($depends_on !== NULL) {
        $dep_result = $this->checkDependency($depends_on, $normalized);

        if ($dep_result === 'skip') {
          $type_error = $this->validateType($handler, $value);
          if ($type_error !== NULL) {
            $errors[] = [
              'prompt' => $id,
              'message' => $type_error,
            ];
            continue;
          }

          $resolved[$id] = $value;
          continue;
        }

        if ($dep_result === FALSE) {
          $dep_values = [];
          foreach ($depends_on as $dep_id => $acceptable) {
            $dep_values[] = $dep_id . '=' . implode('|', array_map(fn($v): string => var_export($v, TRUE), $acceptable));
          }
          $warnings[] = [
            'prompt' => $id,
            'message' => sprintf('Value will be ignored: %s condition is not met.', implode(', ', $dep_values)),
          ];
          continue;
        }
      }

      // Validate value based on type.
      $type_error = $this->validateType($handler, $value);
      if ($type_error !== NULL) {
        $errors[] = [
          'prompt' => $id,
          'message' => $type_error,
        ];
        continue;
      }

      $resolved[$id] = $value;
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
      'warnings' => $warnings,
      'resolved' => $resolved,
    ];
  }

  /**
   * Normalize config keys to handler IDs.
   *
   * Supports both env var names (VORTEX_INSTALLER_PROMPT_*) and handler IDs.
   *
   * @param array<string, mixed> $config
   *   The raw config array.
   *
   * @return array<string, mixed>
   *   Config keyed by handler IDs.
   */
  protected function normalizeConfig(array $config): array {
    $normalized = [];

    foreach ($config as $key => $value) {
      $normalized[$key] = $value;
    }

    return $normalized;
  }

  /**
   * Check if dependency conditions are met.
   *
   * @param array<string, array<mixed>> $depends_on
   *   The dependency conditions.
   * @param array<string, mixed> $config
   *   The normalized config.
   *
   * @return bool|string
   *   TRUE if met, FALSE if not met, 'skip' for system dependencies.
   */
  protected function checkDependency(array $depends_on, array $config): bool|string {
    foreach ($depends_on as $dep_id => $acceptable_values) {
      if ($dep_id === HandlerInterface::DEPENDS_ON_SYSTEM) {
        return 'skip';
      }

      if (!array_key_exists($dep_id, $config)) {
        return FALSE;
      }

      $actual = $config[$dep_id];
      if (!in_array($actual, $acceptable_values, TRUE)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Validate a value against handler type constraints.
   *
   * @param \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface $handler
   *   The handler.
   * @param mixed $value
   *   The value to validate.
   *
   * @return string|null
   *   Error message if invalid, NULL if valid.
   */
  protected function validateType(HandlerInterface $handler, mixed $value): ?string {
    $type = $handler->type();
    $options = $handler->options([]);

    switch ($type) {
      case PromptType::Select:
        if (is_array($options) && !array_key_exists($value, $options)) {
          $valid = implode(', ', array_keys($options));
          return sprintf("Invalid value '%s'. Valid: %s.", (string) $value, $valid);
        }
        break;

      case PromptType::MultiSelect:
        if (!is_array($value)) {
          return sprintf("Expected array for multiselect '%s'.", $handler::id());
        }
        if (is_array($options)) {
          foreach ($value as $item) {
            if (!array_key_exists($item, $options)) {
              $valid = implode(', ', array_keys($options));
              return sprintf("Invalid value '%s' in multiselect. Valid: %s.", (string) $item, $valid);
            }
          }
        }
        break;

      case PromptType::Confirm:
        if (!is_bool($value)) {
          return sprintf("Expected boolean for confirm '%s'.", $handler::id());
        }
        break;

      case PromptType::Text:
      case PromptType::Suggest:
        $validate = $handler->validate();
        if ($validate !== NULL) {
          $error = $validate($value);
          if ($error !== NULL) {
            return $error;
          }
        }
        break;
    }

    return NULL;
  }

}
