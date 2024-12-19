<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

/**
 * Printer trait.
 */
trait PrinterTrait {

  protected function out(string $text, ?string $color = NULL, bool $new_line = TRUE): void {
    $styles = [
      'success' => "\033[0;32m%s\033[0m",
      'error' => "\033[31;31m%s\033[0m",
    ];

    $format = '%s';

    if (isset($styles[$color]) && $this->config->get('ANSI')) {
      $format = $styles[$color];
    }

    if ($new_line) {
      $format .= PHP_EOL;
    }

    printf($format, $text);
  }

  protected function debug(mixed $value, string $name = ''): void {
    print PHP_EOL;
    print trim($name . ' DEBUG START') . PHP_EOL;
    print print_r($value, TRUE) . PHP_EOL;
    print trim($name . ' DEBUG FINISH') . PHP_EOL;
    print PHP_EOL;
  }

  protected function printTitle(string $text, string $fill = '-', int $width = 80, string $cols_delim = '|', bool $has_content = FALSE): void {
    $this->printDivider($fill, $width, 'down');
    $lines = explode(PHP_EOL, wordwrap($text, $width - 4, PHP_EOL));
    foreach ($lines as $line) {
      $line = ' ' . $line . ' ';
      print $cols_delim . str_pad($line, $width - 2, ' ', STR_PAD_BOTH) . $cols_delim . PHP_EOL;
    }
    $this->printDivider($fill, $width, $has_content ? 'up' : 'both');
  }

  protected function printSubtitle(string $text, string $fill = '=', int $width = 80): void {
    $is_multiline = strlen($text) + 4 >= $width;
    if ($is_multiline) {
      $this->printTitle($text, $fill, $width, 'both');
    }
    else {
      $text = ' ' . $text . ' ';
      print str_pad($text, $width, $fill, STR_PAD_BOTH) . PHP_EOL;
    }
  }

  protected function printDivider(string $fill = '-', int $width = 80, string $direction = 'none'): void {
    $start = $fill;
    $finish = $fill;
    switch ($direction) {
      case 'up':
        $start = '╰';
        $finish = '╯';
        break;

      case 'down':
        $start = '╭';
        $finish = '╮';
        break;

      case 'both':
        $start = '├';
        $finish = '┤';
        break;
    }

    print $start . str_repeat($fill, $width - 2) . $finish . PHP_EOL;
  }

  protected function printBox(string $content, string $title = '', string $fill = '─', int $padding = 2, int $width = 80): void {
    $cols = '│';

    $max_width = $width - 2 - $padding * 2;
    $lines = explode(PHP_EOL, wordwrap(rtrim($content, PHP_EOL), $max_width, PHP_EOL));
    $pad = str_pad(' ', $padding);
    $mask = sprintf('%s%s%%-%ss%s%s', $cols, $pad, $max_width, $pad, $cols) . PHP_EOL;

    print PHP_EOL;
    if (!empty($title)) {
      $this->printTitle($title, $fill, $width);
    }
    else {
      $this->printDivider($fill, $width, 'down');
    }

    array_unshift($lines, '');
    $lines[] = '';
    foreach ($lines as $line) {
      printf($mask, $line);
    }

    $this->printDivider($fill, $width, 'up');
    print PHP_EOL;
  }

  protected function printTick(?string $text = NULL): void {
    if (!empty($text) && $this->config->isInstallDebug()) {
      print PHP_EOL;
      $this->status($text, self::INSTALLER_STATUS_DEBUG, FALSE);
    }
    else {
      $this->status('.', self::INSTALLER_STATUS_MESSAGE, FALSE, FALSE);
    }
  }

  protected function status(string $message, int $level = self::INSTALLER_STATUS_MESSAGE, bool $use_eol = TRUE, bool $use_prefix = TRUE): void {
    $prefix = '';
    $color = NULL;

    switch ($level) {
      case self::INSTALLER_STATUS_SUCCESS:
        $prefix = '✓️';
        $color = 'success';
        break;

      case self::INSTALLER_STATUS_ERROR:
        $prefix = '✗';
        $color = 'error';
        break;

      case self::INSTALLER_STATUS_MESSAGE:
        $prefix = 'i️';
        $color = 'info';
        break;

      case self::INSTALLER_STATUS_DEBUG:
        $prefix = '  [D]';
        break;
    }

    if ($level != self::INSTALLER_STATUS_DEBUG || $this->config->isInstallDebug()) {
      $this->out(($use_prefix ? $prefix . ' ' : '') . $message, $color, $use_eol);
    }
  }

  /**
   * Format values list.
   *
   * @param array<int|string, mixed> $values
   *   Array of values to format.
   * @param string $delim
   *   Delimiter to use.
   * @param int $width
   *   Width of the line.
   *
   * @return string
   *   Formatted values list.
   */
  protected function formatValuesList(array $values, string $delim = '', int $width = 80): string {
    // Only keep the keys that are not numeric.
    $keys = array_filter(array_keys($values), static fn($key): bool => !is_numeric($key));

    // Line width - length of delimiters * 2 - 2 spacers.
    $line_width = $width - strlen($delim) * 2 - 2;

    // Max name length + spaced on the sides + colon.
    $max_name_width = max(array_map(static fn(string $key): int => strlen($key), $keys)) + 2 + 1;

    // Whole width - (name width + 2 delimiters on the sides + 1 delimiter in
    // the middle + 2 spaces on the sides + 2 spaces for the center delimiter).
    $value_width = max($width - ($max_name_width + strlen($delim) * 2 + strlen($delim) + 2 + 2), 1);

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
        $lines = array_filter(explode(PHP_EOL, chunk_split(strval($value), $value_width, PHP_EOL)));
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

  protected function formatEnabled(mixed $value): string {
    return $value && strtolower((string) $value) !== 'n' ? 'Enabled' : 'Disabled';
  }

  protected function formatYesNo(string $value): string {
    return $value === self::ANSWER_YES ? 'Yes' : 'No';
  }

  protected function formatNotEmpty(mixed $value, mixed $default): mixed {
    return empty($value) ? $default : $value;
  }

}
