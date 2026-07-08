<?php

declare(strict_types=1);

namespace DrevOps\Tui\Derive;

use DrevOps\Tui\Config\ConfigException;

/**
 * A derive rule: a `{{field}}` template and an optional named transform.
 *
 * Declared with named arguments - `new Derive('{{name}}', transform:
 * 'machine')` - and owning its computation. The transform name is validated at
 * construction, so a typo fails at declaration time instead of silently
 * passing values through.
 *
 * @package DrevOps\Tui\Derive
 */
final readonly class Derive {

  /**
   * Construct a derive rule.
   *
   * @param string $template
   *   The template with `{{field}}` tokens interpolated from current values.
   * @param string $transform
   *   The transform normalizing the result: any str2name conversion, or one
   *   of host / lower / upper / initials (empty for none).
   */
  public function __construct(public string $template, public string $transform = '') {
    if ($this->transform !== '' && !Transform::supports($this->transform)) {
      throw new ConfigException(sprintf('Unknown derive transform "%s".', $this->transform));
    }
  }

  /**
   * Compute the derived value from the current values.
   *
   * @param array<string,mixed> $values
   *   The current values keyed by field id.
   *
   * @return string
   *   The derived value.
   */
  public function compute(array $values): string {
    $interpolated = trim($this->interpolate($values));

    return $this->transform === '' ? $interpolated : Transform::apply($interpolated, $this->transform);
  }

  /**
   * The rule as the raw array shape used by the JSON schema.
   *
   * @return array<string,string>
   *   The raw rule.
   */
  public function toArray(): array {
    return $this->transform === '' ? ['template' => $this->template] : ['template' => $this->template, 'transform' => $this->transform];
  }

  /**
   * Replace `{{field}}` tokens in the template with the current values.
   *
   * @param array<string,mixed> $values
   *   The current values.
   *
   * @return string
   *   The interpolated string.
   */
  protected function interpolate(array $values): string {
    return (string) preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', static function (array $matches) use ($values): string {
      $value = $values[$matches[1]] ?? '';

      return is_scalar($value) ? (string) $value : '';
    }, $this->template);
  }

}
