#!/usr/bin/env php
<?php

/**
 * @file
 * Playground script to demonstrate Tui helper methods.
 *
 * Run: php playground/tui.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();
Tui::init($output);

echo PHP_EOL;
echo "=== Tui Helper Class Demo ===" . PHP_EOL;
echo PHP_EOL;

// All 16 colors in different styles.
echo "--- All 16 Colors ---" . PHP_EOL;
echo "    Normal | Dim | Underscore | Underscore+Dim | Bold | Bold+Dim | Bold+Underscore | Bold+Underscore+Dim" . PHP_EOL;
echo PHP_EOL;

$colors = [
  30 => 'Black',
  31 => 'Red',
  32 => 'Green',
  33 => 'Yellow',
  34 => 'Blue',
  35 => 'Magenta',
  36 => 'Cyan',
  37 => 'White',
  90 => 'Bright Black',
  91 => 'Bright Red',
  92 => 'Bright Green',
  93 => 'Bright Yellow',
  94 => 'Bright Blue',
  95 => 'Bright Magenta',
  96 => 'Bright Cyan',
  97 => 'Bright White',
];

foreach ($colors as $code => $name) {
  $w = 15;
  $pad = str_repeat(' ', max(0, $w - strlen($name)));

  // Style codes: 1=bold, 2=dim, 4=underscore.
  $normal =              sprintf("\033[%sm%s\033[0m%s", $code, $name, $pad);
  $dim =                 sprintf("\033[2;%sm%s\033[0m%s", $code, $name, $pad);
  $under =               sprintf("\033[4;%sm%s\033[0m%s", $code, $name, $pad);
  $under_dim =           sprintf("\033[2;4;%sm%s\033[0m%s", $code, $name, $pad);
  $bold =                sprintf("\033[1;%sm%s\033[0m%s", $code, $name, $pad);
  $bold_dim =            sprintf("\033[1;2;%sm%s\033[0m%s", $code, $name, $pad);
  $bold_under =          sprintf("\033[1;4;%sm%s\033[0m%s", $code, $name, $pad);
  $bold_under_dim =      sprintf("\033[1;2;4;%sm%s\033[0m%s", $code, $name, $pad);

  echo sprintf("%3d: %s %s %s %s %s %s %s %s", $code, $normal, $dim, $under, $under_dim, $bold, $bold_dim, $bold_under, $bold_under_dim) . PHP_EOL;
}
echo PHP_EOL;

// Tui helper colors.
echo "--- Tui Helper Colors ---" . PHP_EOL;
echo Tui::green("This is green text") . PHP_EOL;
echo Tui::blue("This is blue text") . PHP_EOL;
echo Tui::purple("This is purple text") . PHP_EOL;
echo Tui::yellow("This is yellow text") . PHP_EOL;
echo Tui::cyan("This is cyan text") . PHP_EOL;
echo PHP_EOL;

// Text styles.
echo "--- Text Styles ---" . PHP_EOL;
echo Tui::bold("This is bold text") . PHP_EOL;
echo Tui::underscore("This is underscored text") . PHP_EOL;
echo Tui::dim("This is dimmed text") . PHP_EOL;
echo "This is normal text for comparison" . PHP_EOL;
echo PHP_EOL;

// Combinations.
echo "--- Combinations ---" . PHP_EOL;
echo Tui::bold(Tui::green("Bold green text")) . PHP_EOL;
echo Tui::dim(Tui::cyan("Dimmed cyan text")) . PHP_EOL;
echo Tui::bold(Tui::yellow("Bold yellow text")) . PHP_EOL;
echo PHP_EOL;

// Dim with reset codes (simulating external command output).
echo "--- Dim with embedded resets ---" . PHP_EOL;
$simulated_output = "\033[32mGreen text\033[0m then normal \033[34mblue text\033[0m end";
echo "Original: " . $simulated_output . PHP_EOL;
echo "Dimmed:   " . Tui::dim($simulated_output) . PHP_EOL;
echo PHP_EOL;

// Multiline.
echo "--- Multiline ---" . PHP_EOL;
$multiline = "Line one\nLine two\nLine three";
echo Tui::green($multiline) . PHP_EOL;
echo PHP_EOL;
echo Tui::dim($multiline) . PHP_EOL;
echo PHP_EOL;

// Box.
echo "--- Box ---" . PHP_EOL;
Tui::box("This is content inside a box.\nIt can have multiple lines.", "Box Title");
echo PHP_EOL;

// Info/Note/Error (Laravel Prompts styles).
echo "--- Messages ---" . PHP_EOL;
Tui::info("This is an info message");
Tui::note("This is a note message");
Tui::success("This is a success message");
Tui::error("This is an error message");
echo PHP_EOL;

// List.
echo "--- List ---" . PHP_EOL;
Tui::list([
  'Project name' => 'my_project',
  'Machine name' => 'my_project',
  'Organization' => 'My Organization',
  'Services' => Tui::LIST_SECTION_TITLE,
  'Database' => 'MySQL 8.0',
  'Cache' => 'Redis',
  'Search' => 'Solr',
], 'Configuration Summary');
echo PHP_EOL;

// Terminal width.
$term_width = Tui::terminalWidth();
echo "--- Terminal Info ---" . PHP_EOL;
echo "Terminal width: " . $term_width . " columns" . PHP_EOL;
echo PHP_EOL;

// Ruler function.
$make_ruler = function(int $width): string {
  $ruler_top = '';
  $ruler_num = '';
  $ruler_bot = '';
  for ($i = 1; $i <= $width; $i++) {
    if ($i % 10 === 0) {
      $ruler_top .= '|';
      $num = (string)$i;
      $ruler_num .= $num[strlen($num) - 2] ?? ' ';
      $ruler_bot .= $num[strlen($num) - 1];
    } elseif ($i % 5 === 0) {
      $ruler_top .= '+';
      $ruler_num .= ' ';
      $ruler_bot .= '5';
    } else {
      $ruler_top .= '-';
      $ruler_num .= ' ';
      $ruler_bot .= ' ';
    }
  }
  return $ruler_top . PHP_EOL . $ruler_num . PHP_EOL . $ruler_bot;
};

// Visual terminal boundaries with ruler.
echo "--- Terminal Boundaries with Ruler ---" . PHP_EOL;
echo $make_ruler($term_width) . PHP_EOL;
echo "|" . str_repeat(" ", $term_width - 2) . "|" . PHP_EOL;
echo str_repeat("=", $term_width) . PHP_EOL;
echo PHP_EOL;

// Center.
echo "--- Centered Text (within terminal width: $term_width) ---" . PHP_EOL;
echo "|" . str_repeat("-", $term_width - 2) . "|" . PHP_EOL;
echo Tui::center("Centered Title", $term_width) . PHP_EOL;
echo Tui::center("Line 1\nLine 2\nLonger Line 3", $term_width) . PHP_EOL;
echo "|" . str_repeat("-", $term_width - 2) . "|" . PHP_EOL;
echo PHP_EOL;

// Center with fixed width.
$fixed_width = 60;
echo "--- Centered Text (fixed width: $fixed_width) ---" . PHP_EOL;
echo "|" . str_repeat("-", $fixed_width - 2) . "|" . PHP_EOL;
echo Tui::center("Centered Title", $fixed_width) . PHP_EOL;
echo Tui::center("Short\nMedium text\nThis is a longer line", $fixed_width) . PHP_EOL;
echo "|" . str_repeat("-", $fixed_width - 2) . "|" . PHP_EOL;
echo PHP_EOL;

echo "=== Demo Complete ===" . PHP_EOL;
echo PHP_EOL;
