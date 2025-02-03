<?php

declare(strict_types=1);

namespace DrevOps\Installer;

use AlexSkrypnyk\Str2Name\Str2Name;

/**
 * Converter.
 *
 * Convert strings to different formats.
 *
 * @package DrevOps\Installer
 */
class Converter extends Str2Name {

  public static function toHumanName(string $value): ?string {
    $value = preg_replace('/[^a-zA-Z0-9]/', ' ', $value);
    $value = trim((string) $value);

    return preg_replace('/\s{2,}/', ' ', $value);
  }

  /**
   * Convert string to machine name.
   *
   * @param string $value
   *   Value to convert.
   * @param array<int|string> $preserve_chars
   *   Array of characters to preserve.
   *
   * @return string
   *   Converted value.
   */
  public static function toMachineName(string $value, array $preserve_chars = []): string {
    $preserve = '';
    foreach ($preserve_chars as $char) {
      $preserve .= preg_quote(strval($char), '/');
    }
    $pattern = '/[^a-zA-Z0-9' . $preserve . ']/';

    $value = preg_replace($pattern, '_', $value);

    return strtolower($value);
  }

  public static function toAbbreviation(string $value, int $length = 2, string $word_delim = '_'): string {
    $value = trim($value);
    $value = str_replace(' ', '_', $value);
    $parts = empty($word_delim) ? [$value] : explode($word_delim, $value);

    if (count($parts) == 1) {
      return strlen($parts[0]) > $length ? substr($parts[0], 0, $length) : $value;
    }

    $value = implode('', array_map(static function (string $word): string {
      return substr($word, 0, 1);
    }, $parts));

    return substr($value, 0, $length);
  }

  public static function snakeToPascal(string $string): string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
  }

  public static function toDomain(string $string): string {
    $string = trim($string);
    $string = rtrim($string, '/');
    $string = str_replace([' ', '_'], '-', $string);
    $string = preg_replace('/^https?:\/\//', '', $string);

    return (string) preg_replace('/^www\./', '', $string);
  }

  public static function toPath(string $string): string {
    return str_replace([' '], '-', trim($string, '/'));
  }

  public static function toContainerImage(string $string): string {
    $string = self::toMachineName($string, ['-', '/', ':', '.']);

    return str_contains($string, ':') ? $string : $string . ':latest';
  }

}
