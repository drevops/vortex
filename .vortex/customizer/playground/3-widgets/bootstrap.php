<?php

/**
 * @file
 * Shared bootstrap for the interactive widget playground (mirrors prompty).
 *
 * Parses the mode flags, builds a theme and exposes an $interact helper that
 * drives a single widget against the real terminal. Forcing the mode with a
 * flag lets you see the textual (ASCII) or no-colour rendering without changing
 * your terminal locale.
 *
 * Flags:
 *   --no-unicode   Use ASCII (textual) glyphs instead of Unicode.
 *   --no-ansi      Disable ANSI colour.
 */

declare(strict_types=1);

use DrevOps\Customizer\Input\KeyParser;
use DrevOps\Customizer\Theme\DarkTheme;
use DrevOps\Customizer\Tui\Terminal;
use DrevOps\Customizer\Widget\WidgetInterface;

require __DIR__ . '/../../vendor/autoload.php';

$opts = getopt('', ['no-unicode', 'no-ansi']);
$theme = new DarkTheme(!isset($opts['no-ansi']), 76, !isset($opts['no-unicode']));

/**
 * Drive a widget to completion against the real terminal, then print the value.
 *
 * @var callable(\DrevOps\Customizer\Widget\WidgetInterface,string):void $interact
 */
$interact = static function (WidgetInterface $widget, string $label) use ($theme): void {
  $terminal = new Terminal();
  $parser = new KeyParser();
  $terminal->setup();

  try {
    while (!$widget->isComplete() && !$widget->isCancelled()) {
      $lines = [
        $theme->style('title', $label . ' widget'),
        $theme->style('footer', 'edit · Enter accept · Esc cancel'),
        '',
        $widget->view($theme),
      ];

      $error = $widget->error();
      if ($error !== NULL) {
        $lines[] = '';
        $lines[] = $theme->style('description', $error);
      }

      $terminal->render(implode("\n", $lines));

      foreach ($parser->parse($terminal->read()) as $key) {
        $widget->handle($key);
      }
    }
  }
  finally {
    $terminal->restore();
  }

  echo $label . ': ' . ($widget->isCancelled() ? '(cancelled)' : (string) json_encode($widget->value())) . PHP_EOL;
};
