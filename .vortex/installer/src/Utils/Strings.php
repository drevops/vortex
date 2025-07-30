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
   * Recursively search and modify string values in an array using a callback.
   *
   * @param array $array
   *   The array to search and modify (passed by reference).
   * @param callable $modifier
   *   Callback function that receives the current string value and returns
   *   the modified value. Should return the same value if no modification
   *   is needed. Signature: function(string $value): string.
   *
   * @return bool
   *   TRUE if any modifications were made, FALSE otherwise.
   */
  public static function modifyArrayRecursive(array &$array, callable $modifier): bool {
    $modified = FALSE;

    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        if (static::modifyArrayRecursive($value, $modifier)) {
          $modified = TRUE;
        }
      }
      elseif (is_string($value)) {
        $original_value = $value;

        $new_value = $modifier($value);

        if ($new_value !== $original_value) {
          $array[$key] = $new_value;
          $modified = TRUE;
        }
      }
    }

    return $modified;
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
    // First handle the removal of empty docblocks with potential following
    // newlines. Only match docblocks that start at beginning of line
    // (possibly with whitespace).
    $content = preg_replace_callback(
      '/^(\s*)\/\*\*(.*?)\*\/(\n)?/ms',
      function ($matches): string {
        $full_match = $matches[0];
        $leading_whitespace = $matches[1];
        $comment_content = $matches[2];
        $following_newline = $matches[3] ?? '';

        // Handle single-line docblocks - don't modify them.
        if (!str_contains($comment_content, "\n")) {
          return $full_match;
        }

        // Split into lines for processing.
        $lines = explode("\n", $comment_content);

        // Remove leading and trailing empty lines.
        while (!empty($lines)) {
          $first_line = trim($lines[0]);
          if ($first_line === '' || preg_match('/^\*\s*$/', $first_line)) {
            array_shift($lines);
          }
          else {
            break;
          }
        }

        while (!empty($lines)) {
          $last_line = trim($lines[count($lines) - 1]);
          if ($last_line === '' || preg_match('/^\*\s*$/', $last_line)) {
            array_pop($lines);
          }
          else {
            break;
          }
        }

        // Check if there's actual content (more than just * characters and
        // whitespace).
        $has_content = FALSE;
        foreach ($lines as $line) {
          $line_content = preg_replace('/^\s*\*\s*/', '', $line);
          if (trim($line_content) !== '') {
            $has_content = TRUE;
            break;
          }
        }

        // If no real content, remove the entire docblock including following
        // newline.
        if (!$has_content) {
          return '';
        }

        // Detect indentation pattern from the first non-empty line.
        $indentation_pattern = ' *';
        foreach ($lines as $line) {
          $line_content = preg_replace('/^\s*\*\s*/', '', $line);
          if (trim($line_content) !== '') {
            // Extract the indentation part (everything before the content)
            if (preg_match('/^(\s*\*)/', $line, $indent_matches)) {
              $indentation_pattern = $indent_matches[1];
            }
            break;
          }
        }

        // Now collapse consecutive empty lines in the middle.
        $result_lines = [];
        $prev_was_empty = FALSE;

        foreach ($lines as $line) {
          $line_content = preg_replace('/^\s*\*\s*/', '', $line);
          $is_empty_line = trim($line_content) === '';

          if ($is_empty_line) {
            if (!$prev_was_empty) {
              $result_lines[] = $indentation_pattern;
              $prev_was_empty = TRUE;
            }
            // Skip additional consecutive empty lines.
          }
          else {
            $result_lines[] = $line;
            $prev_was_empty = FALSE;
          }
        }

        // Reconstruct the docblock.
        if (empty($result_lines)) {
          return '';
        }

        // Extract the indentation pattern for the closing tag.
        $closing_indentation = ' ';
        if (preg_match('/\n(\s*)\*\/$/', $full_match, $closing_matches)) {
          $closing_indentation = $closing_matches[1];
        }

        return $leading_whitespace . '/**' . "\n" . implode("\n", $result_lines) . "\n" . $closing_indentation . '*/' . $following_newline;
      },
      $content
    );

    return $content;
  }

}
