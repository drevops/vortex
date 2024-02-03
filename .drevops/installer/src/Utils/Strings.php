<?php

namespace DrevOps\Installer\Utils;

/**
 * String utilities.
 */
class Strings {

  /**
   * Convert a string to a machine name.
   *
   * @param string $value
   *   The value.
   * @param array $preserve_chars
   *   The characters to preserve.
   *
   * @return string
   *   The machine name.
   */
  public static function toMachineName(string $value, $preserve_chars = []): string {
    if (empty($value)) {
      return $value;
    }

    // If the value doesn't start with an uppercase or lowercase letter, return as-is.
    if (!preg_match('/^[a-zA-Z]/', trim($value))) {
      return $value;
    }

    $value = trim($value);

    $preserve = '';
    foreach ($preserve_chars as $char) {
      $preserve .= preg_quote((string) $char, '/');
    }
    $pattern = '/[^a-zA-Z0-9' . $preserve . ']/';

    $value = preg_replace($pattern, '_', $value);

    return strtolower($value);
  }

  /**
   * Convert a string to a human name.
   *
   * @param string $value
   *   The value.
   *
   * @return string|null
   *   The human name.
   */
  public static function toHumanName(string $value): ?string {
    $value = preg_replace('/[^a-zA-Z0-9]/', ' ', $value);
    $value = trim($value);

    return preg_replace('/\s{2,}/', ' ', $value);
  }

  /**
   * Convert a string to an abbreviation.
   *
   * @param string $value
   *   The value.
   * @param int $maxlength
   *   The maximum length.
   * @param string $word_delim
   *   The word delimiter.
   *
   * @return string
   *   The abbreviation.
   */
  public static function toAbbreviation(string $value, $maxlength = 2, $word_delim = '_'): string {
    $value = trim($value);
    $value = str_replace(' ', '_', $value);
    $parts = explode($word_delim, $value);
    if (count($parts) == 1) {
      return strlen($parts[0]) > $maxlength ? substr($parts[0], 0, $maxlength) : $value;
    }

    $value = implode('', array_map(static function ($word): string {
      return substr($word, 0, 1);
    }, $parts));

    return substr($value, 0, $maxlength);
  }

  /**
   * Convert a string to a URL.
   *
   * @param string $string
   *   The string.
   *
   * @return string
   *   The URL.
   */
  public static function toUrl(string $string): string {
    // @todo Add more replacements.
    return str_replace([' ', '_'], '-', $string);
  }

  /**
   * Convert a string to a list.
   *
   * @param mixed $value
   *   The value.
   * @param bool $is_multiline
   *   Whether to use multiline.
   *
   * @return string
   *   The value.
   */
  public static function listToString(mixed $value, $is_multiline = FALSE): string {
    if (is_array($value)) {
      $value = implode($is_multiline ? PHP_EOL : ', ', $value);
    }

    return $value;
  }

  /**
   * Check if a string is a regex.
   *
   * @param string $str
   *   The string.
   *
   * @return bool
   *   Whether the string is a regex.
   */
  public static function isRegex(string $str): bool {
    // First character is always the start.
    $start = $str[0];

    // Exclude any of the invalid starting characters.
    if (preg_match('/[*?[:alnum:] \\\\]/', $start)) {
      return FALSE;
    }

    // Find the corresponding ending delimiter.
    $end = $start;
    $pairs = ['{' => '}', '(' => ')', '[' => ']', '<' => '>'];
    if (isset($pairs[$start])) {
      $end = $pairs[$start];
    }

    // The regex should look something like this: /pattern/modifiers
    // Extract the pattern and the modifiers.
    if (!preg_match('#^' . preg_quote($start) . '(.*?)' . preg_quote($end) . '([imsxuADU]*)$#', $str, $matches)) {
      return FALSE;
    }

    $pattern = $matches[1];

    if ($start === $end) {
      $parts = explode($start, $pattern);
      for ($i = 0; $i < count($parts) - 1; $i++) {
        // If the part does not end with a backslash and there's another part
        // left, we found an extra delimiter.
        if (!str_ends_with($parts[$i], '\\') && isset($parts[$i + 1])) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
