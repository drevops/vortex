<?php

/**
 * @file
 * Interactive confirm widget: Left/Right or y/n toggle, Enter accepts.
 *
 * Usage:
 *   php 3-widgets/widget-confirm.php
 *   php 3-widgets/widget-confirm.php --no-unicode   # textual glyphs
 *   php 3-widgets/widget-confirm.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Tui\Widget\ConfirmWidget;

require __DIR__ . '/bootstrap.php';

$interact(new ConfirmWidget(TRUE), 'Confirm');
