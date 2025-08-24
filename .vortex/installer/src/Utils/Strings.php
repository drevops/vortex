<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

class Strings {

  public static function isAsciiStart(string $string): ?int {
    $pos = preg_match('/^[\x00-\x7F]/', $string);
    return $pos !== FALSE ? $pos : NULL;
  }

  public static function strlenPlain(string $text): int {
    $clean_text = preg_replace('/\e\[[0-9;]*m/', '', $text);
    return mb_strwidth($clean_text, 'UTF-8');
  }

  /**
   * Checks if a string is a valid regular expression.
   *
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   TRUE if the string is a valid regex, FALSE otherwise.
   */
  public static function isRegex(string $string): bool {
    if ($string === '' || strlen($string) < 3) {
      return FALSE;
    }

    // Extract the first character as the delimiter.
    $delimiter = $string[0];

    if (!in_array($delimiter, ['/', '#', '~'])) {
      return FALSE;
    }

    $last_char = substr($string, -1);
    $before_last_char = substr($string, -2, 1);
    if (
      ($last_char !== $delimiter && !in_array($last_char, ['i', 'm', 's']))
      || ($before_last_char !== $delimiter && in_array($before_last_char, ['i', 'm', 's']))
    ) {
      return FALSE;
    }

    // Test the regex.
    $result = preg_match($string, '');
    return $result !== FALSE && preg_last_error() === PREG_NO_ERROR;
  }

  /**
   * Collapse consecutive empty lines within PHP block comments.
   *
   * Also removes leading/trailing empty lines and removes entirely empty
   * docblocks.
   */
  public static function collapsePhpBlockCommentsEmptyLines(string $content): string {
    // Use simpler regex approach with direct string replacement.
    return preg_replace_callback(
      '/^(\s*)\/\*\*(.*?)\*\/(\n)?/ms',
      [self::class, 'processDocblock'],
      $content
    );
  }

  private static function processDocblock(array $matches): string {
    $full_match = $matches[0];
    $leading_whitespace = $matches[1];
    $comment_content = $matches[2];
    $following_newline = $matches[3] ?? '';

    // Single-line docblocks - return unchanged.
    if (!str_contains($comment_content, "\n")) {
      return $full_match;
    }

    // Split into lines and process.
    $lines = explode("\n", $comment_content);

    // Remove leading/trailing empty lines and check for content in one pass.
    $start = 0;
    $end = count($lines) - 1;
    $has_content = FALSE;

    // Find first non-empty line.
    while ($start <= $end) {
      $line = trim($lines[$start]);
      if ($line !== '' && !preg_match('/^\*\s*$/', $line)) {
        $has_content = TRUE;
        break;
      }
      $start++;
    }

    // Find last non-empty line.
    while ($end >= $start) {
      $line = trim($lines[$end]);
      if ($line !== '' && !preg_match('/^\*\s*$/', $line)) {
        break;
      }
      $end--;
    }

    // No content - remove entire docblock.
    if (!$has_content) {
      return '';
    }

    // Extract working lines.
    $work_lines = array_slice($lines, $start, $end - $start + 1);

    // Get indentation pattern from first line.
    $indent_pattern = ' *';
    if (!empty($work_lines) && preg_match('/^(\s*\*)/', $work_lines[0], $indent_matches)) {
      $indent_pattern = $indent_matches[1];
    }

    // Collapse consecutive empty lines.
    $result_lines = [];
    $prev_empty = FALSE;

    foreach ($work_lines as $line) {
      $line_content = preg_replace('/^\s*\*\s*/', '', $line);
      $is_empty = trim($line_content) === '';

      if ($is_empty) {
        if (!$prev_empty) {
          $result_lines[] = $indent_pattern;
        }
        $prev_empty = TRUE;
      }
      else {
        $result_lines[] = $line;
        $prev_empty = FALSE;
      }
    }

    // Reconstruct docblock.
    if (empty($result_lines)) {
      return '';
    }

    // Get closing indentation from original match.
    $closing_indent = ' ';
    if (preg_match('/\n(\s*)\*\/$/', $full_match, $close_matches)) {
      $closing_indent = $close_matches[1];
    }

    return $leading_whitespace . '/**' . "\n" . implode("\n", $result_lines) . "\n" . $closing_indent . '*/' . $following_newline;
  }

}
