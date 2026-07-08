<?php

declare(strict_types=1);

namespace DrevOps\Tui\Derive;

use AlexSkrypnyk\Str2Name\Str2Name;

/**
 * Value transforms for derive rules.
 *
 * Extends str2name so every str2name conversion (machine, kebab, pascal, ...)
 * is usable as a derive transform by name, and adds a few TUI-only
 * transforms (host, lower, upper, initials). A config derive rule names a
 * transform; the engine validates the name against supports().
 *
 * @package DrevOps\Tui\Derive
 */
class Transform extends Str2Name {

  /**
   * The TUI-only transforms, in addition to the str2name conversions.
   */
  protected const EXTRA = ['host', 'lower', 'upper', 'initials'];

  /**
   * The str2name conversions exposed as transforms.
   */
  protected const STR2NAME = [
    'machine',
    'kebab',
    'pascal',
    'snake',
    'camel',
    'cobol',
    'train',
    'flat',
    'constant',
    'label',
    'sentence',
    'abbreviation',
    'domain',
    'filepath',
    'id',
    'phpClass',
    'phpFunction',
    'phpMethod',
    'phpNamespace',
    'phpPackage',
    'phpPackageName',
  ];

  /**
   * Apply a named transform to a value.
   *
   * @param string $value
   *   The value.
   * @param string $name
   *   The transform name.
   *
   * @return string
   *   The transformed value, or the value unchanged for an unknown name.
   */
  public static function apply(string $value, string $name): string {
    $callable = [static::class, $name];

    if (!static::supports($name) || !is_callable($callable)) {
      return $value;
    }

    $result = $callable($value);

    return is_string($result) ? $result : $value;
  }

  /**
   * Whether a transform name is supported.
   *
   * @param string $name
   *   The transform name.
   *
   * @return bool
   *   TRUE when the name maps to a known transform.
   */
  public static function supports(string $name): bool {
    return in_array($name, static::names(), TRUE);
  }

  /**
   * The supported transform names.
   *
   * @return list<string>
   *   All transform names (str2name conversions plus the TUI extras).
   */
  public static function names(): array {
    return array_values(array_merge(static::STR2NAME, static::EXTRA));
  }

  /**
   * Lowercase a value.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The lowercased value.
   */
  public static function lower(string $value): string {
    return strtolower($value);
  }

  /**
   * Uppercase a value.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The uppercased value.
   */
  public static function upper(string $value): string {
    return strtoupper($value);
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
  public static function host(string $value): string {
    $out = preg_replace('/[^a-z0-9.]+/', '-', strtolower($value)) ?? '';

    return trim($out, '-.');
  }

  /**
   * Reduce a value to the initials of its underscore-separated words.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The initials (up to four characters).
   */
  public static function initials(string $value): string {
    $letters = '';

    foreach (explode('_', static::machine($value)) as $part) {
      if ($part !== '') {
        $letters .= $part[0];
      }
    }

    return substr($letters, 0, 4);
  }

}
