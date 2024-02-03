<?php

namespace DrevOps\Installer\Utils;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

/**
 * Formatter.
 */
class Formatter {

  /**
   * Format a value if it is not empty.
   *
   * @param mixed $value
   *   The value.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The formatted value.
   */
  public static function formatNotEmpty(mixed $value, mixed $default) {
    return empty($value) ? $default : $value;
  }

  /**
   * Format a value if it is empty.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The formatted value.
   */
  public static function formatEmpty(mixed $value) {
    return empty($value) ? '(empty)' : $value;
  }

  /**
   * Format a value as Yes or No.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The formatted value.
   */
  public static function formatYesNo(mixed $value): string {
    return empty($value) ? 'No' : 'Yes';
  }

  /**
   * Format a value as Enabled or Disabled.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The formatted value.
   */
  public static function formatEnabledDisabled(mixed $value): string {
    return empty($value) ? 'Disabled' : 'Enabled';
  }

  /**
   * Format a value as a list of key-value pairs.
   *
   * @param array $values
   *   The values.
   * @param string $delim
   *   The delimiter.
   * @param int $width
   *   The width.
   *
   * @return string
   *   The formatted value.
   */
  public static function formatValuesList($values, $delim = '', $width = 80): string {
    // Line width - length of delimiters * 2 - 2 spacers.
    $line_width = $width - strlen((string) $delim) * 2 - 2;

    // Max name length + spaced on the sides + colon.
    $max_name_width = max(array_map('strlen', array_keys($values))) + 2 + 1;

    // Whole width - (name width + 2 delimiters on the sides + 1 delimiter in
    // the middle + 2 spaces on the sides  + 2 spaces for the center delimiter).
    $value_width = $width - ($max_name_width + strlen((string) $delim) * 2 + strlen((string) $delim) + 2 + 2);

    $mask1 = sprintf('%s %%%ds %s %%-%s.%ss %s', $delim, $max_name_width, $delim, $value_width, $value_width, $delim) . PHP_EOL;
    $mask2 = sprintf('%s%%2$%ss%s', $delim, $line_width, $delim) . PHP_EOL;

    $output = [];
    foreach ($values as $name => $value) {
      $is_multiline_value = strlen((string) $value) > $value_width;

      if (is_numeric($name)) {
        $name = '';
        $mask = $mask2;
        $is_multiline_value = FALSE;
      }
      else {
        $name .= ':';
        $mask = $mask1;
      }

      if ($is_multiline_value) {
        $lines = array_filter(explode(PHP_EOL, chunk_split((string) $value, $value_width, PHP_EOL)));
        $first_line = array_shift($lines);
        $output[] = sprintf($mask, $name, $first_line);
        foreach ($lines as $line) {
          $output[] = sprintf($mask, '', $line);
        }
      }
      else {
        $output[] = sprintf($mask, $name, $value);
      }
    }

    return implode('', $output);
  }

  /**
   * Print a box.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   * @param array|string $values
   *   The values.
   * @param string $title
   *   The title.
   * @param string $style
   *   The style.
   * @param int $pad_rows
   *   The number of padding rows.
   */
  public static function printBox($output, $values, $title = '', $style = 'box-double', $pad_rows = 1): void {
    $table = new Table($output);

    if (is_array($values)) {
      $rows = array_map(static function ($key, $value): TableSeparator|array {
        return $value instanceof TableSeparator ? $value : [$key, $value];
      }, array_keys($values), $values);
    }
    else {
      $rows = [[$values]];
    }

    if ($pad_rows !== 0) {
      array_unshift($rows, array_fill(0, $pad_rows, ''));
      $rows[] = array_fill(0, $pad_rows, '');
    }

    $table->setRows($rows);
    if ($title !== '' && $title !== '0') {
      $table->setHeaderTitle($title);
    }

    if ($style == 'box-double') {
      $style = (new TableStyle())
        ->setHorizontalBorderChars('═', '─')
        ->setVerticalBorderChars('║', '│')
        ->setCrossingChars('┼', '╔', '╤', '╗', '╢', '╝', '╧', '╚', '╟', '╔', '╪', '╗');
    }

    $table->setStyle($style);

    $output->writeln('');
    $table->render();
    $output->writeln('');
  }

}
