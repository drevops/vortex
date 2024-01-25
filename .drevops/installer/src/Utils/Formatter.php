<?php

namespace DrevOps\Installer\Utils;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class Formatter {

  public static function formatNotEmpty($value, $default) {
    return empty($value) ? $default : $value;
  }

  public static function formatEmpty($value) {
    return empty($value) ? '(empty)' : $value;
  }

  public static function formatYesNo($value): string {
    return empty($value) ? 'No' : 'Yes';
  }

  public static function formatEnabled($value): string {
    return empty($value) ? 'Disabled' : 'Enabled';
  }

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

  public static function printBox($output, $values, $title = '', $style = 'box-double', $pad_rows = 1): void {
    $table = new Table($output);

    if (is_array($values)) {
      $rows = array_map(static function ($key, $value) : TableSeparator|array {
          return $value instanceof TableSeparator ? $value : [$key, $value];
      }, array_keys($values), $values);
    }
    else {
      $rows = [[$values]];
    }

    if ($pad_rows) {
      array_unshift($rows, array_fill(0, $pad_rows, ''));
      $rows[] = array_fill(0, $pad_rows, '');
    }

    $table->setRows($rows);
    if ($title) {
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
