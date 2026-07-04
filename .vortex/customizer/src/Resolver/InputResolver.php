<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Resolver;

use DrevOps\Customizer\Config\FieldType;

/**
 * Assembles the non-interactive input map from the external answer layers.
 *
 * The customizer stays dependency-free: this small overlay merges the external
 * layers - per-question environment variables (below) and a `--prompts`
 * JSON string or file (above) - into one input map. That map is the top layer
 * the engine resolves against, so the full precedence becomes
 * `--prompts` > env > discovered > derived > default. Environment values are
 * strings, so they are coerced to the field's type; `--prompts` values are
 * already typed by JSON.
 *
 * @package DrevOps\Customizer\Resolver
 */
class InputResolver {

  /**
   * Construct a resolver.
   *
   * @param string $env_prefix
   *   The prefix for per-question env variable names (e.g. "VORTEX_").
   */
  public function __construct(protected string $env_prefix = '') {
  }

  /**
   * Build the input map for the given fields.
   *
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   The fields to resolve inputs for.
   * @param string $prompts
   *   A `--prompts` JSON string, or a path to a JSON file, or empty.
   * @param array<string,string> $env
   *   The environment map (injected for testability).
   *
   * @return array<string,mixed>
   *   The input map keyed by field id.
   */
  public function resolve(array $fields, string $prompts, array $env): array {
    $inputs = [];

    foreach ($fields as $field) {
      $name = $this->envName($field->id);
      if (array_key_exists($name, $env)) {
        $inputs[$field->id] = $this->coerce($env[$name], $field->type);
      }
    }

    foreach ($this->parsePrompts($prompts) as $id => $value) {
      $inputs[$id] = $value;
    }

    return $inputs;
  }

  /**
   * The env variable name for a field id.
   *
   * @param string $id
   *   The field id (snake_case).
   *
   * @return string
   *   The env variable name (prefix + uppercased id).
   */
  public function envName(string $id): string {
    return $this->env_prefix . strtoupper($id);
  }

  /**
   * Coerce a string environment value to the field's type.
   *
   * @param string $value
   *   The raw environment value.
   * @param \DrevOps\Customizer\Config\FieldType $type
   *   The field type.
   *
   * @return mixed
   *   The coerced value.
   */
  protected function coerce(string $value, FieldType $type): mixed {
    return match ($type) {
      FieldType::Confirm => in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], TRUE),
      FieldType::MultiSelect => $this->splitList($value),
      default => $value,
    };
  }

  /**
   * Split a comma-separated string into a list of trimmed values.
   *
   * @param string $value
   *   The comma-separated value.
   *
   * @return list<string>
   *   The list of values.
   */
  protected function splitList(string $value): array {
    if (trim($value) === '') {
      return [];
    }

    return array_values(array_filter(array_map(trim(...), explode(',', $value)), static fn(string $item): bool => $item !== ''));
  }

  /**
   * Parse the `--prompts` operand (JSON string or file) into a map.
   *
   * @param string $prompts
   *   A JSON string, or a path to a JSON file, or empty.
   *
   * @return array<string,mixed>
   *   The decoded map keyed by field id.
   */
  protected function parsePrompts(string $prompts): array {
    if ($prompts === '') {
      return [];
    }

    $json = is_file($prompts) ? (string) file_get_contents($prompts) : $prompts;
    $data = json_decode($json, TRUE);
    if (!is_array($data)) {
      return [];
    }

    $out = [];
    foreach ($data as $key => $value) {
      $out[(string) $key] = $value;
    }

    return $out;
  }

}
