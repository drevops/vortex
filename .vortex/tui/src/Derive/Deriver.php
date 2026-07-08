<?php

declare(strict_types=1);

namespace DrevOps\Tui\Derive;

/**
 * Computes derived field values from templates and settles chains.
 *
 * A derive rule is `{template, transform?}`, where the template contains
 * `{{field}}` tokens interpolated from the current values and the optional
 * transform normalizes the result via {@see Transform} (any str2name
 * conversion, or one of host / lower / upper / initials).
 * Derived values are recomputed to a fixpoint so chains settle, and fields the
 * user has pinned (overridden) are left untouched.
 *
 * @package DrevOps\Tui\Derive
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
    $interpolated = trim($this->interpolate($template, $values));
    $transform = isset($rule['transform']) && is_scalar($rule['transform']) ? (string) $rule['transform'] : '';

    return $transform === '' ? $interpolated : Transform::apply($interpolated, $transform);
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

}
