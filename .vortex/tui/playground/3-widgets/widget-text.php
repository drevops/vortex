<?php

/**
 * @file
 * Interactive text widget: type, arrows move the caret, Enter accepts.
 *
 * Usage:
 *   php 3-widgets/widget-text.php
 *   php 3-widgets/widget-text.php --no-unicode   # textual glyphs
 *   php 3-widgets/widget-text.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Tui\Input\KeyParser;
use DrevOps\Tui\Render\Terminal;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\TextWidget;

require __DIR__ . '/../../vendor/autoload.php';

// Forcing the mode with a flag shows the textual (ASCII) or no-colour
// rendering without changing the terminal locale.
$opts = getopt('', ['no-unicode', 'no-ansi']);
$theme = new DarkTheme(!isset($opts['no-ansi']), 76, !isset($opts['no-unicode']));

$widget = new TextWidget('Acme Site');

$terminal = new Terminal();
$parser = new KeyParser();
$terminal->setup();

try {
  while (!$widget->isComplete() && !$widget->isCancelled()) {
    $terminal->render(implode("\n", [$theme->style('title', 'Text widget'), $theme->style('footer', 'edit · Enter accept · Esc cancel'), '', $widget->view($theme)]));

    foreach ($parser->parse($terminal->read()) as $key) {
      $widget->handle($key);
    }
  }
}
finally {
  $terminal->restore();
}

echo 'Text: ' . ($widget->isCancelled() ? '(cancelled)' : (string) json_encode($widget->value())) . PHP_EOL;
