<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use Symfony\Component\Yaml\Yaml as SymfonyYaml;

// @phpstan-ignore-next-line
class Yaml extends SymfonyYaml {

  public static function validateFile(string $path): void {
    // Check if the file exists and is readable.
    if (!file_exists($path) || !is_readable($path)) {
      throw new \InvalidArgumentException('File does not exist or is not readable: ' . $path);
    }

    static::parseFile($path);
  }

  public static function validate(string $content): void {
    static::parse($content);
  }

  public static function collapseEmptyLinesInLiteralBlock(string $content): string {
    $lines = explode("\n", $content);
    $result_lines = [];
    $line_count = count($lines);

    // Pre-compute line properties to avoid repeated calculations.
    $line_data = [];
    for ($i = 0; $i < $line_count; $i++) {
      $line = $lines[$i];
      $trimmed = trim($line);
      $line_data[$i] = [
        'line' => $line,
        'is_empty' => $trimmed === '',
        'indent' => strlen($line) - strlen(ltrim($line)),
        'is_literal_start' => preg_match('/^(\h*)\w+:\h*\|/', $line, $matches) ? strlen($matches[1]) : -1,
        'is_yaml_key' => $trimmed !== '' && preg_match('/^(\h*)\w+:/', $line, $matches) ? strlen($matches[1]) : -1,
      ];
    }

    // Track current literal block state to avoid repeated lookups.
    $current_block_indent = -1;
    $current_block_start = -1;

    for ($i = 0; $i < $line_count; $i++) {
      $data = $line_data[$i];

      // Update block state when encountering literal block starts.
      if ($data['is_literal_start'] >= 0) {
        $current_block_indent = $data['is_literal_start'];
        $current_block_start = $i;
      }
      // Update block state when encountering lines that end the block.
      elseif ($current_block_indent >= 0 && !$data['is_empty'] && $data['indent'] <= $current_block_indent) {
        $current_block_indent = -1;
        $current_block_start = -1;
      }

      // Handle empty lines.
      if ($data['is_empty']) {
        // If not in a literal block, keep the line.
        if ($current_block_indent < 0) {
          $result_lines[] = $data['line'];
          continue;
        }

        // In a literal block - check if next non-empty line ends the block.
        $block_ends = FALSE;
        for ($j = $i + 1; $j < $line_count; $j++) {
          $ahead_data = $line_data[$j];
          if (!$ahead_data['is_empty']) {
            if ($ahead_data['indent'] <= $current_block_indent) {
              $block_ends = TRUE;
              $current_block_indent = -1;
              $current_block_start = -1;
            }
            break;
          }
        }

        // Keep line if block ends, skip if within block.
        if ($block_ends) {
          $result_lines[] = $data['line'];
        }
        continue;
      }

      $result_lines[] = $data['line'];
    }

    return implode("\n", $result_lines);
  }

  /**
   * Collapse *first* repeated empty lines within YAML literal block.
   *
   * This function specifically targets YAML literal block indicated by the
   * pipe character (|) and collapses multiple consecutive empty lines that
   * occur immediately after the pipe. Empty lines in other parts of the
   * content block are left unchanged.
   *
   * @param string $content
   *   The YAML content to process, which may contain literal blocks.
   *
   * @return string
   *   The processed content.
   */
  public static function collapseFirstEmptyLinesInLiteralBlock(string $content): string {
    return preg_replace('/(?<=\|)(\n\s*\n)+/', "\n", $content);
  }

}
