<?php

/**
 * @file
 * Renders every widget in both Unicode and textual (ASCII) glyph modes.
 *
 * Widgets pull their glyphs from the theme, so the same widget renders with
 * Unicode glyphs under a Unicode theme and ASCII glyphs under an ASCII theme -
 * exactly how the customizer adapts to the terminal (prompty-style: Unicode is
 * auto-detected from the locale, ASCII is the fallback). This showcase forces
 * each mode side by side so the difference is visible without a terminal.
 *
 * Usage:
 *   php 3-widgets/run.php
 */

declare(strict_types=1);

use DrevOps\Customizer\Tui\DarkTheme;
use DrevOps\Customizer\Widget\ConfirmWidget;
use DrevOps\Customizer\Widget\MultiSelectWidget;
use DrevOps\Customizer\Widget\SelectWidget;
use DrevOps\Customizer\Widget\SuggestWidget;
use DrevOps\Customizer\Widget\TextWidget;
use DrevOps\Customizer\Widget\WidgetInterface;

require __DIR__ . '/../../vendor/autoload.php';

// Colour is disabled so the output is plain; only the glyph mode differs.
$unicode = new DarkTheme(FALSE, 76, TRUE);
$ascii = new DarkTheme(FALSE, 76, FALSE);

/**
 * The widgets to showcase, each built freshly (widgets are stateful).
 *
 * @var array<string,callable():\DrevOps\Customizer\Widget\WidgetInterface>
 */
$widgets = [
  'Text' => static fn(): WidgetInterface => new TextWidget('Acme Site'),
  'Select' => static fn(): WidgetInterface => new SelectWidget(['standard' => 'Standard', 'minimal' => 'Minimal', 'demo_umami' => 'Demo Umami'], 'minimal'),
  'MultiSelect' => static fn(): WidgetInterface => new MultiSelectWidget(['redis' => 'Redis', 'solr' => 'Solr', 'clamav' => 'ClamAV'], ['redis', 'solr']),
  'Confirm' => static fn(): WidgetInterface => new ConfirmWidget(TRUE),
  'Suggest' => static fn(): WidgetInterface => new SuggestWidget(['UTC', 'Europe/London', 'Europe/Paris', 'Australia/Sydney'], 'Europe/'),
];

$columns = static function (string $left, string $right, int $gap = 8): string {
  $left_lines = explode("\n", $left);
  $right_lines = explode("\n", $right);
  $width = 0;
  foreach ($left_lines as $line) {
    $width = max($width, mb_strlen($line));
  }

  $rows = [];
  for ($i = 0, $count = max(count($left_lines), count($right_lines)); $i < $count; $i++) {
    $line = $left_lines[$i] ?? '';
    $pad = str_repeat(' ', max(0, $width - mb_strlen($line) + $gap));
    $rows[] = '    ' . $line . $pad . ($right_lines[$i] ?? '');
  }

  return implode("\n", $rows);
};

echo "\n";
echo $columns('UNICODE', 'TEXTUAL (ASCII)') . "\n";
echo str_repeat('-', 60) . "\n\n";

foreach ($widgets as $name => $make) {
  echo $name . "\n";
  echo $columns($make()->view($unicode), $make()->view($ascii)) . "\n\n";
}
