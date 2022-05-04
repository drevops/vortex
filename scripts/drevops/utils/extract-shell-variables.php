#!/usr/bin/env php
<?php

/**
 * @file
 * Scan a file or a directory with shell scripts and extract all variables.
 *
 * Variables can have descriptions and default values that will be printed out
 * to the STDOUT in a CSV format as `name, default_value, description`.
 *
 * This is helpful to maintain a table of variables and their descriptions in
 * documentation.
 *
 * ./extract-shell-variables.php path/to/file
 * ./extract-shell-variables.php path/to/dir
 *
 * With excluded file:
 * ./extract-shell-variables.php -e ../extract-shell-variables-excluded.txt path/to/file
 *
 * Full:
 * ./extract-shell-variables.php  -t -m -e ./extract-shell-variables-excluded.txt -u "<NOT SET>" ../
 */

/**
 * Main install functionality.
 */
function main(array $argv, $argc) {
  init_cli_args_and_options($argv, $argc);

  $files = get_targets(get_config('path'));

  $all_variables = [];
  foreach ($files as $file) {
    $all_variables += extract_variables_from_file($file);
  }

  // Exclude local variables, if set.
  if (get_config('filter_global')) {
    $all_variables = array_filter($all_variables, function ($value) {
      return preg_match('/^[A-Z0-9_]+$/', $value['name']);
    });
  }

  $exclude_file = get_config('exclude_file');
  if ($exclude_file) {
    $excluded_variables = array_filter(explode("\n", file_get_contents($exclude_file)));
    $all_variables = array_diff_key($all_variables, array_flip($excluded_variables));
  }

  $filter_prefix = get_config('filter_prefix');
  if ($filter_prefix) {
    $all_variables = array_filter($all_variables, function ($value) use ($filter_prefix) {
      return strpos($value['name'], $filter_prefix) !== 0;
    });
  }

  ksort($all_variables);

  $csv = render_variables_data($all_variables);

  if (get_config('markdown')) {
    $csvTable = new CSVTable($csv, get_config('csv_delim'));
    print $csvTable->getMarkup();
  }
  else {
    print $csv;
  }
}


/**
 * Initialise CLI options.
 */
function init_cli_args_and_options($argv, $argc) {
  $opts = [
    'exclude-file:' => 'e:',
    'markdown' => 'm',
    'csv-delim:' => 'c:',
    'ticks' => 't',
    'unset:' => 'u:',
    'filter-prefix' => 'p',
    'filter-global' => 'g',
  ];

  $options = getopt(implode('', $opts), array_keys($opts), $optind);

  foreach ($opts as $longopt => $shortopt) {
    $longopt = str_replace(':', '', $longopt);
    $shortopt = str_replace(':', '', $shortopt);

    if (isset($options[$shortopt])) {
      $options[$longopt] = $options[$shortopt] === FALSE ? TRUE : $options[$shortopt];
      unset($options[$shortopt]);
    }
    elseif (isset($options[$longopt])) {
      $options[$longopt] = $options[$longopt] === FALSE ? TRUE : $options[$longopt];
    }
  }

  $options += [
    'path' => '',
    'exclude-file' => FALSE,
    'markdown' => FALSE,
    'ticks' => FALSE,
    'filter-prefix' => '',
    'filter-global' => '',
    'unset' => '<UNSET>',
    'csv-delim' => ';',
  ];


  $pos_args = array_slice($argv, $optind);
  $pos_args = array_filter($pos_args);

  if (count($pos_args) < 1) {
    die('ERROR: Path to a file or a directory is required.');
  }

  $path = reset($pos_args);

  if (strpos($path, './') !== 0 && strpos($path, '/') !== 0) {
    $path = getcwd() . DIRECTORY_SEPARATOR . $path;
  }

  if (!is_readable($path)) {
    die('ERROR: Unable to read a path to scan.');
  }
  $options['path'] = $path;

  $exclude_file = $options['exclude-file'];

  if ($exclude_file) {
    if (strpos($exclude_file, './') !== 0 && strpos($exclude_file, '/') !== 0) {
      $exclude_file = getcwd() . DIRECTORY_SEPARATOR . $exclude_file;
    }
    if (!is_readable($exclude_file)) {
      die('ERROR: Unable to read an exclude file.');
    }
    $options['exclude-file'] = $exclude_file;
  }

  set_config('markdown', $options['markdown']);
  set_config('exclude_file', $options['exclude-file']);
  set_config('csv_delim', $options['csv-delim']);
  set_config('ticks', $options['ticks']);
  set_config('unset_value', $options['unset']);
  set_config('filter_prefix', $options['filter-prefix']);
  set_config('filter_global', $options['filter-global']);
  set_config('path', $options['path']);
}

function get_targets($path) {
  $files = [];
  if (is_file($path)) {
    $files[] = $path;
  }
  else {
    $files = glob($path . '/*.sh');
  }

  return $files;
}

function extract_variables_from_file($file) {
  $content = file_get_contents($file);

  $lines = explode("\n", $content);

  $variables = [];
  foreach ($lines as $k => $line) {
    $variable_data = [
      'name' => '',
      'default_value' => '',
      'description' => '',
    ];

    $variable_name = extract_variable_name($line);

    // Only use the very first occurrence.
    if (!empty($variables[$variable_name])) {
      continue;
    }

    if ($variable_name) {
      $variable_data['name'] = $variable_name;

      $variable_value = extract_variable_value($line);
      if ($variable_value) {
        $variable_data['default_value'] = $variable_value;
      }

      $variable_desc = extract_variable_description($lines, $k);
      if ($variable_desc) {
        $variable_data['description'] = $variable_desc;
      }

      $variables[$variable_data['name']] = $variable_data;
    }
  }

  return $variables;
}

function extract_variable_name($string) {
  // Assignment.
  if (preg_match('/^([a-zA-Z][a-zA-Z0-9_]*)=.*$/', $string, $matches)) {
    return $matches[1];
  }

  // Usage.
  if (preg_match('/\${?([a-zA-Z][a-zA-Z0-9_]*)/', $string, $matches)) {
    return $matches[1];
  }

  return FALSE;
}

function extract_variable_value($string) {
  $value = get_config('unset_value');

  $value_string = '';
  // Assignment.
  if (preg_match('/{?[a-zA-Z][a-zA-Z0-9_]*}?="?(.*)"?/', $string, $matches)) {
    $value_string = $matches[1];
  }

  if (empty($value_string)) {
    return $value;
  }

  // Value is in the second part of the assigned value.
  if (strpos($value_string, ':') !== FALSE) {
    if (preg_match('/\${[a-zA-Z][a-zA-Z0-9_]*:-?\$?{?([a-zA-Z][a-zA-Z0-9_]*)/', $value_string, $matches)) {
      $value = $matches[1];
    }
  }
  else {
    // Value is a simple scalar or another value.
    if (preg_match('/{?([a-zA-Z][a-zA-Z0-9_]*)/', $value_string, $matches)) {
      $value = $matches[1];
    }
    else {
      $value = $value_string;
    }
  }

  return $value;
}

function extract_variable_description($lines, $k, $comment_delim = '#') {
  $comment_lines = [];

  // Look up until the first non-comment line.
  while ($k > 0 && strpos(trim($lines[$k - 1]), $comment_delim) === 0) {
    $comment_lines[] = trim(ltrim(trim($lines[$k - 1]), $comment_delim));
    $k--;
  }

  return implode(' ', array_reverse(array_filter($comment_lines)));
}

function render_variables_data($variables) {
  $csv = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');

  fputcsv($csv, ['Name', 'Default value', 'Description'], get_config('csv_delim'));
  foreach ($variables as $variable) {
    if (get_config('ticks')) {
      $variable['name'] = '`' . $variable['name'] . '`';
      if (!empty($variable['default_value'])) {
        $variable['default_value'] = '`' . $variable['default_value'] . '`';
      }
    }
    fputcsv($csv, $variable, get_config('csv_delim'));
  }

  rewind($csv);

  return stream_get_contents($csv);
}

/**
 * Get configuration.
 */
function get_config($name, $default = NULL) {
  global $_config;

  return $_config[$name] ?? $default;
}

function set_config($name, $value) {
  global $_config;

  if (!is_null($value)) {
    $_config[$name] = $value;
  }
}

function get_configs() {
  global $_config;
  return $_config;
}

// ////////////////////////////////////////////////////////////////////////// //
//                                CSVTable                                    //
// ////////////////////////////////////////////////////////////////////////// //

// Credits: https://github.com/mre/CSVTable
class CSVTable {

  public function __construct($csv, $delim = ',', $enclosure = '"', $table_separator = '|') {
    $this->csv = $csv;
    $this->delim = $delim;
    $this->enclosure = $enclosure;
    $this->table_separator = $table_separator;

    // Fill the rows with Markdown output
    $this->header = ""; // Table header
    $this->rows = ""; // Table rows
    $this->CSVtoTable($this->csv);
  }

  private function CSVtoTable() {
    $parsed_array = $this->toArray($this->csv);
    $this->length = $this->minRowLength($parsed_array);
    $this->col_widths = $this->maxColumnWidths($parsed_array);

    $header_array = array_shift($parsed_array);
    $this->header = $this->createHeader($header_array);
    $this->rows = $this->createRows($parsed_array);
  }

  /**
   * Convert the CSV into a PHP array
   */
  public function toArray($csv) {
    $parsed = str_getcsv($csv, "\n"); // Parse the rows
    $output = [];
    foreach ($parsed as &$row) {
      $row = str_getcsv($row, $this->delim, $this->enclosure); // Parse the items in rows
      array_push($output, $row);
    }
    return $output;
  }

  private function createHeader($header_array) {
    return $this->createRow($header_array) . $this->createSeparator();
  }

  private function createSeparator() {
    $output = "";
    for ($i = 0; $i < $this->length - 1; ++$i) {
      $output .= str_repeat("-", $this->col_widths[$i]);
      $output .= $this->table_separator;
    }
    $last_index = $this->length - 1;
    $output .= str_repeat("-", $this->col_widths[$last_index]);
    return $output . "\n";
  }

  protected function createRows($rows) {
    $output = "";
    foreach ($rows as $row) {
      $output .= $this->createRow($row);
    }
    return $output;
  }

  /**
   * Add padding to a string
   */
  private function padded($str, $width) {
    if ($width < strlen($str)) {
      return $str;
    }
    $padding_length = $width - strlen($str);
    $padding = str_repeat(" ", $padding_length);
    return $str . $padding;
  }

  protected function createRow($row) {
    $output = "";
    // Only create as many columns as the minimal number of elements
    // in all rows. Otherwise this would not be a valid Markdown table
    for ($i = 0; $i < $this->length - 1; ++$i) {
      $element = $this->padded($row[$i], $this->col_widths[$i]);
      $output .= $element;
      $output .= $this->table_separator;
    }
    // Don't append a separator to the last element
    $last_index = $this->length - 1;
    $element = $this->padded($row[$last_index], $this->col_widths[$last_index]);
    $output .= $element;
    $output .= "\n"; // row ends with a newline
    return $output;
  }

  private function minRowLength($arr) {
    $min = PHP_INT_MAX;
    foreach ($arr as $row) {
      $row_length = count($row);
      if ($row_length < $min) {
        $min = $row_length;
      }
    }
    return $min;
  }

  /*
   * Calculate the maximum width of each column in characters
   */
  private function maxColumnWidths($arr) {
    // Set all column widths to zero.
    $column_widths = array_fill(0, $this->length, 0);
    foreach ($arr as $row) {
      foreach ($row as $k => $v) {
        if ($column_widths[$k] < strlen($v)) {
          $column_widths[$k] = strlen($v);
        }
        if ($k == $this->length - 1) {
          // We don't need to look any further since these elements
          // will be dropped anyway because all table rows must have the
          // same length to create a valid Markdown table.
          break;
        }
      }
    }
    return $column_widths;
  }

  public function getMarkup() {
    return $this->header . $this->rows;
  }
}

// ////////////////////////////////////////////////////////////////////////// //
//                                ENTRYPOINT                                  //
// ////////////////////////////////////////////////////////////////////////// //

ini_set('display_errors', 1);

if (PHP_SAPI != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

// Do not run this script if INSTALLER_SKIP_RUN is set. Useful when requiring
// this file from other scripts (e.g. for testing).

main($argv, $argc);
