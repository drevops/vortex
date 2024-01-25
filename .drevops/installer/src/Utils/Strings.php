<?php

namespace DrevOps\Installer\Utils;

/**
 *
 */
class Strings {

  public static function toMachineName($value, $preserve_chars = []) {
    if (empty($value)) {
      return $value;
    }

    // If the value doesn't start with an uppercase or lowercase letter, return as-is.
    if (!preg_match('/^[a-zA-Z]/', trim((string) $value))) {
      return $value;
    }

    $value = trim((string) $value);

    $preserve = '';
    foreach ($preserve_chars as $char) {
      $preserve .= preg_quote((string) $char, '/');
    }
    $pattern = '/[^a-zA-Z0-9' . $preserve . ']/';

    $value = preg_replace($pattern, '_', $value);

    return strtolower($value);
  }

  public static function toHumanName($value): ?string {
    $value = preg_replace('/[^a-zA-Z0-9]/', ' ', (string) $value);
    $value = trim($value);

    return preg_replace('/\s{2,}/', ' ', $value);
  }

  public static function toAbbreviation($value, $maxlength = 2, $word_delim = '_'): string|array {
    $value = trim((string) $value);
    $value = str_replace(' ', '_', $value);
    $parts = explode($word_delim, $value);
    if (count($parts) == 1) {
      return strlen($parts[0]) > $maxlength ? substr($parts[0], 0, $maxlength) : $value;
    }

    $value = implode('', array_map(static function ($word) : string {
        return substr($word, 0, 1);
    }, $parts));

    return substr($value, 0, $maxlength);
  }

  public static function toUrl(string $string): string {
    // @todo Add more replacements.
    return str_replace([' ', '_'], '-', $string);
  }

  public static function listToString(mixed $value, $is_multiline = FALSE): mixed {
    if (is_array($value)) {
      $value = implode($is_multiline ? PHP_EOL : ', ', $value);
    }

    return $value;
  }

  public static function isRegex($str): bool {
    // First character is always the start.
    $start = $str[0];

    // Exclude any of the invalid starting characters.
    if (preg_match('/[*?[:alnum:] \\\\]/', (string) $start)) {
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
    if (!preg_match('#^' . preg_quote((string) $start) . '(.*?)' . preg_quote((string) $end) . '([imsxuADU]*)$#', (string) $str, $matches)) {
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
