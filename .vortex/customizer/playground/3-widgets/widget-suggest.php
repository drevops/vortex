<?php

/**
 * @file
 * Interactive suggest widget: type to filter, Up/Down move, Enter accepts.
 *
 * Usage:
 *   php 3-widgets/widget-suggest.php
 *   php 3-widgets/widget-suggest.php --no-unicode   # textual glyphs
 *   php 3-widgets/widget-suggest.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Tui\Widget\SuggestWidget;

require __DIR__ . '/bootstrap.php';

$interact(new SuggestWidget(['UTC', 'Europe/London', 'Europe/Paris', 'Australia/Sydney'], ''), 'Suggest');
