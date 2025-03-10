<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use AlexSkrypnyk\Str2Name\Str2Name;

/**
 * Converter.
 *
 * Convert strings to different formats.
 *
 * @package DrevOps\Installer
 */
class Converter extends Str2Name {

  public static function abbreviation(string $string, int $length = 2, array $word_delims = [' ']): string {
    $string = trim($string);

    $parts = preg_split('/[' . implode('', array_map('preg_quote', $word_delims)) . ']/', $string);

    if (count($parts) == 1) {
      return strlen($parts[0]) > $length ? substr($parts[0], 0, $length) : $string;
    }

    $string = implode('', array_map(static function (string $word): string {
      return substr($word, 0, 1);
    }, $parts));

    return substr($string, 0, $length);
  }

  public static function domain(string $string): string {
    $string = trim($string);
    $string = rtrim($string, '/');
    $string = str_replace([' ', '_'], '-', $string);
    $string = preg_replace('/^https?:\/\//', '', $string);

    return (string) preg_replace('/^www\./', '', $string);
  }

  public static function path(string $string): string {
    return str_replace([' '], '-', trim($string, '/'));
  }

  public static function fromList(string $value, string $delimiter = ','): array {
    return array_map('trim', explode($delimiter, $value));
  }

  public static function toList(array $value, string $delimiter = ','): string {
    return implode($delimiter, $value);
  }

  public static function yesNo(string|bool|int $value): string {
    return $value === '1' || $value === 1 || $value === TRUE ? 'Yes' : 'No';
  }

}
