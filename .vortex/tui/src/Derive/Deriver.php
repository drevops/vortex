<?php

declare(strict_types=1);

namespace DrevOps\Tui\Derive;

/**
 * Recomputes derived field values until chains settle to a fixpoint.
 *
 * Each rule is a {@see Derive} owning its own computation; fields the user
 * has pinned (overridden) are left untouched.
 *
 * @package DrevOps\Tui\Derive
 */
class Deriver {

  /**
   * Recompute derived values until they stop changing.
   *
   * @param array<string,\DrevOps\Tui\Derive\Derive> $rules
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

        $computed = $rule->compute($values);
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

}
