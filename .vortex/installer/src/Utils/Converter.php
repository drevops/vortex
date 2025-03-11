<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use AlexSkrypnyk\Str2Name\Str2Name;

class Converter extends Str2Name {

  public static function abbreviation(string $string, int $length = 2, array $word_delims = [' ']): string {
    $string = trim($string);

    $parts = preg_split('/[' . implode('', array_map('preg_quote', $word_delims)) . ']/', $string);

    if ($parts === FALSE) {
      throw new \RuntimeException('Failed to split string.');
    }

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
    return array_filter(array_map('trim', explode($delimiter !== '' ? $delimiter : ',', $value)));
  }

  public static function toList(array $value, string $delimiter = ',', bool $append_end = FALSE): string {
    return implode($delimiter, $value) . ($append_end ? $delimiter : '');
  }

  public static function yesNo(string|bool|int $value): string {
    return $value === '1' || $value === 1 || $value === TRUE ? 'Yes' : 'No';
  }

  /**
   * {@inheritdoc}
   */
  protected static function mbRemove(string $string): string {
    return preg_replace('/[^\x00-\x7F]+/', '', $string);
  }

}
