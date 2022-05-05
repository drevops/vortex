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
 * ./extract-shell-variables.php path/to/file1 path/to/file2
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

  $files = get_targets(get_config('paths'));

  if (get_config('debug')) {
    print "Scanning files:\n" . implode("\n", $files) . "\n";
  }

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

  if (get_config('ticks')) {
    $all_variables = process_description_ticks($all_variables);
  }

  if (get_config('markdown') == 'table') {
    $csv = render_variables_data($all_variables);
    $csvTable = new CSVTable($csv, get_config('csv_delim'));
    print $csvTable->getMarkup();
  }
  elseif (get_config('markdown')) {
    $markdown_blocks = new MarkdownBlocks($all_variables, get_config('markdown'));
    print $markdown_blocks->getMarkup();
  }
  else {
    print render_variables_data($all_variables);;
  }
}

/**
 * Initialise CLI options.
 */
function init_cli_args_and_options($argv, $argc) {
  $opts = [
    'debug' => 'd',
    'exclude-file:' => 'e:',
    'markdown::' => 'm::',
    'csv-delim:' => 'c:',
    'ticks' => 't',
    'ticks-list:' => 'l:',
    'slugify' => 's',
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
    'paths' => '',
    'debug' => FALSE,
    'exclude-file' => FALSE,
    'markdown' => FALSE,
    'ticks' => FALSE,
    'ticks-list' => FALSE,
    'slugify' => FALSE,
    'filter-prefix' => '',
    'filter-global' => '',
    'unset' => '<UNSET>',
    'csv-delim' => ';',
  ];

  $pos_args = array_slice($argv, $optind);
  $pos_args = array_filter($pos_args);

  if (count($pos_args) < 1) {
    die('ERROR: At least one path to a file or a directory is required.');
  }

  $paths = $pos_args;

  foreach ($paths as $k => $path) {
    if (strpos($path, './') !== 0 && strpos($path, '/') !== 0) {
      $paths[$k] = realpath(getcwd() . DIRECTORY_SEPARATOR . $path);
    }

    if (!$paths[$k] || !is_readable($paths[$k])) {
      die(sprintf('ERROR: Unable to read a "%s" path to scan.', $path));
    }
  }

  $options['paths'] = $paths;

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

  if ($options['markdown'] !== FALSE) {
    // Table or a contents of the file with a template.
    $options['markdown'] = $options['markdown'] == 'table' ? 'table' : (is_readable($options['markdown']) ? file_get_contents($options['markdown']) : FALSE);
  }

  if ($options['ticks-list'] !== FALSE) {
    // A comma-separated list of strings or a file with additional "code" items.
    $options['ticks-list'] = is_readable($options['ticks-list'])
      ? array_filter(explode("\n", file_get_contents($options['ticks-list'])))
      : array_filter(explode(',', $options['ticks-list']));
  }

  set_config('debug', $options['debug']);
  set_config('markdown', $options['markdown']);
  set_config('exclude_file', $options['exclude-file']);
  set_config('csv_delim', $options['csv-delim']);
  set_config('ticks', $options['ticks']);
  set_config('ticks_list', $options['ticks-list']);
  set_config('slugify', $options['slugify']);
  set_config('unset_value', $options['unset']);
  set_config('filter_prefix', $options['filter-prefix']);
  set_config('filter_global', $options['filter-global']);
  set_config('paths', $options['paths']);
}

function get_targets($paths) {
  $files = [];

  foreach ($paths as $path) {
    if (is_file($path)) {
      $files[] = $path;
    }
    else {
      if (is_readable($path . '/.env')) {
        $files[] = $path . '/.env';
      }
      $files = array_merge($files, glob($path . '/*.{bash,sh}', GLOB_BRACE));
    }
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
  $string = trim($string);

  if (!is_comment($string)) {
    // Assignment.
    if (preg_match('/^([a-zA-Z][a-zA-Z0-9_]*)=.*$/', $string, $matches)) {
      return $matches[1];
    }

    // Usage.
    if (preg_match('/\${?([a-zA-Z][a-zA-Z0-9_]*)/', $string, $matches)) {
      return $matches[1];
    }
  }
  return FALSE;
}

function extract_variable_value($string) {
  $value = get_config('unset_value');

  $value_string = '';
  // Assignment.
  if (preg_match('/{?[a-zA-Z][a-zA-Z0-9_]*}?="?([^"]*)"?/', $string, $matches)) {
    $value_string = $matches[1];
  }

  if (empty($value_string)) {
    return $value;
  }

  // Value is in the second part of the assigned value.
  if (strpos($value_string, ':') !== FALSE) {
    if (preg_match('/\${[a-zA-Z][a-zA-Z0-9_]*:-?\$?{?([a-zA-Z][^}]*)/', $value_string, $matches)) {
      $value = $matches[1];
    }
  }
  else {
    // Value is a simple scalar or another value.
    if (preg_match('/{?([a-zA-Z][^}]*)/', $value_string, $matches)) {
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
    $line = trim(ltrim(trim($lines[$k - 1]), $comment_delim));
    // Completely skip special comment lines.
    if (strpos(trim($lines[$k - 1]), '#;<') !== 0 && strpos(trim($lines[$k - 1]), '#;>') !== 0) {
      $comment_lines[] = $line;
    }
    $k--;
  }

  $comment_lines = array_reverse($comment_lines);
  array_walk($comment_lines, function (&$value) {
    $value = empty($value) ? "\n" : trim($value);
  });

  $output = implode(' ', $comment_lines);
  $output = str_replace(" \n ", "\n", $output);
  $output = str_replace(" \n", "\n", $output);
  $output = str_replace("\n ", "\n", $output);

  return $output;
}

function is_comment($string) {
  return strpos(trim($string), '#') === 0;
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

function process_description_ticks($variables) {
  $variables_sorted = $variables;
  krsort($variables_sorted, SORT_NATURAL);

  foreach ($variables as $k => $variable) {
    // Replace in description.
    $replaced = [];
    foreach (array_keys($variables_sorted) as $other_name) {
      // Cleanup and optionally convert variables to links.
      if (strpos($variable['description'], $other_name) !== FALSE) {
        $already_added = (bool) count(array_filter($replaced, function ($v) use ($other_name) {
          return strpos($v, $other_name) !== FALSE;
        }));

        if (!$already_added) {
          if (get_config('slugify')) {
            $other_name_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $other_name));
            $replacement = sprintf('[`$%s`](#%s)', $other_name, $other_name_slug);
          }
          else {
            $replacement = '`$' . $other_name . '`';
          }
          $variable['description'] = preg_replace('/`?\$?' . $other_name . '`?/', $replacement, $variable['description']);
          $replaced[] = $other_name;
        }
      }
    }

    // Convert digits to code values.
    $variable['description'] = preg_replace('/\b((?<!`)[0-9]+)\b/', '`${1}`', $variable['description']);

    // Process all additional code items.
    if (get_config('ticks_list')) {
      foreach (get_config('ticks_list') as $token) {
        $token = trim($token);
        $variable['description'] = preg_replace('/\b((?<!`)' . preg_quote($token, '/') . ')\b/', '`${1}`', $variable['description']);
      }
    }

    $variables[$k] = $variable;
  }

  return $variables;
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
    $this->CSVtoTable();
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
//                                CSVBlock                                    //
// ////////////////////////////////////////////////////////////////////////// //

class MarkdownBlocks {

  protected $array;

  protected $template;

  protected $markup;

  public function __construct($array, $template) {
    $this->array = $array;
    $this->template = $template;
    $this->markup = $this->CSVtoBlock();
  }

  protected function CSVtoBlock() {
    $content = '';

    foreach ($this->array as $item) {
      $placeholders_tokens = array_map(function ($v) {
        return '{{ ' . $v . ' }}';
      }, array_keys($item));

      $placeholders_values = array_map(function ($v) {
        return str_replace("\n", "<br/>", $v);
      }, $item);

      $placeholders = array_combine($placeholders_tokens, $placeholders_values);
      $content .= str_replace("\n\n\n", "\n", strtr($this->template, $placeholders));
    }

    return $content;
  }

  public function toArray($csv) {
    $array = [];
    $parsed = str_getcsv($csv, "\n"); // Parse the rows
    foreach ($parsed as &$row) {
      $row = str_getcsv($row, $this->delim, $this->enclosure); // Parse the items in rows
      array_push($array, $row);
    }
    return $array;
  }

  public function getMarkup() {
    return $this->markup;
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
