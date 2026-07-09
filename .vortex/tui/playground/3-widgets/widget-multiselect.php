<?php

/**
 * @file
 * Interactive multiselect: Up/Down move, Space toggles, Enter accepts.
 *
 * Usage:
 *   php 3-widgets/widget-multiselect.php
 *   php 3-widgets/widget-multiselect.php --no-unicode   # textual glyphs
 *   php 3-widgets/widget-multiselect.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Tui\Input\KeyParser;
use DrevOps\Tui\Render\Terminal;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\MultiSelectWidget;

require __DIR__ . '/../../vendor/autoload.php';

// Forcing the mode with a flag shows the textual (ASCII) or no-colour
// rendering without changing the terminal locale.
$opts = getopt('', ['no-unicode', 'no-ansi']);
$theme = new DarkTheme(!isset($opts['no-ansi']), 76, !isset($opts['no-unicode']));

$widget = new MultiSelectWidget(['redis' => 'Redis', 'solr' => 'Solr', 'clamav' => 'ClamAV'], ['redis']);

$terminal = new Terminal();
$parser = new KeyParser();
$terminal->setup();

try {
  while (!$widget->isComplete() && !$widget->isCancelled()) {
    $terminal->render(implode("\n", [$theme->style('title', 'MultiSelect widget'), $theme->style('footer', 'edit · Enter accept · Esc cancel'), '', $widget->view($theme)]));

    foreach ($parser->parse($terminal->read()) as $key) {
      $widget->handle($key);
    }
  }
}
finally {
  $terminal->restore();
}

echo 'MultiSelect: ' . ($widget->isCancelled() ? '(cancelled)' : (string) json_encode($widget->value())) . PHP_EOL;
