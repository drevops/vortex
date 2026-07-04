<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Derive;

/**
 * Computes derived field values from templates and settles chains.
 *
 * A derive rule is `{template, transform?}`, where the template contains
 * `{{field}}` tokens interpolated from the current values and the optional
 * transform normalizes the result (`machine` / `host` / `lower` / `upper`).
 * Derived values are recomputed to a fixpoint so chains settle, and fields the
 * user has pinned (overridden) are left untouched.
 *
 * @package DrevOps\Customizer\Derive
 */
class Deriver {

  /**
   * Recompute derived values until they stop changing.
   *
   * @param array<string,array<array-key,mixed>> $rules
   *   Derive rules keyed by field id.
   * @param array<string,mixed> $values
   *   The current values keyed by field id.
   * @param array<string,bool> $overridden
   *   Field ids the user has pinned; these are not recomputed.
   *
   * @return array<string,mixed>
   *   The values with derived fields recomputed.
   */
  public function derive(array $rules, array $values, array $overridden): array {
    $limit = count($rules) + 1;

    for ($i = 0; $i <= $limit; $i++) {
      $changed = FALSE;

      foreach ($rules as $id => $rule) {
        if ($overridden[$id] ?? FALSE) {
          continue;
        }

        $computed = $this->compute($rule, $values);
        if (($values[$id] ?? NULL) !== $computed) {
          $values[$id] = $computed;
          $changed = TRUE;
        }
      }

      if (!$changed) {
        break;
      }
    }

    return $values;
  }

  /**
   * Compute a single derived value from its rule and the current values.
   *
   * @param array<array-key,mixed> $rule
   *   The derive rule.
   * @param array<string,mixed> $values
   *   The current values.
   *
   * @return string
   *   The derived value.
   */
  protected function compute(array $rule, array $values): string {
    $template = isset($rule['template']) && is_scalar($rule['template']) ? (string) $rule['template'] : '';
    $interpolated = $this->interpolate($template, $values);
    $transform = isset($rule['transform']) && is_scalar($rule['transform']) ? (string) $rule['transform'] : '';

    return $transform === '' ? $interpolated : $this->applyTransform($interpolated, $transform);
  }

  /**
   * Replace `{{field}}` tokens in a template with the current values.
   *
   * @param string $template
   *   The template.
   * @param array<string,mixed> $values
   *   The current values.
   *
   * @return string
   *   The interpolated string.
   */
  protected function interpolate(string $template, array $values): string {
    return (string) preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', static function (array $matches) use ($values): string {
      $value = $values[$matches[1]] ?? '';

      return is_scalar($value) ? (string) $value : '';
    }, $template);
  }

  /**
   * Apply a named value transform.
   *
   * @param string $value
   *   The value.
   * @param string $name
   *   The transform name (machine / host / lower / upper).
   *
   * @return string
   *   The transformed value.
   */
  protected function applyTransform(string $value, string $name): string {
    return match ($name) {
      'lower' => strtolower($value),
      'upper' => strtoupper($value),
      'machine' => $this->machine($value),
      'host' => $this->host($value),
      default => $value,
    };
  }

  /**
   * Normalize a value to a machine name (lowercase, underscore-separated).
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The machine name.
   */
  protected function machine(string $value): string {
    $out = preg_replace('/[^a-z0-9]+/', '_', strtolower($value)) ?? '';

    return trim($out, '_');
  }

  /**
   * Normalize a value to a hostname (lowercase, hyphen-separated, dots kept).
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The hostname.
   */
  protected function host(string $value): string {
    $out = preg_replace('/[^a-z0-9.]+/', '-', strtolower($value)) ?? '';

    return trim($out, '-.');
  }

}
