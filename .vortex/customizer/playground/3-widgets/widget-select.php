<?php

/**
 * @file
 * Interactive select widget: Up/Down move, Enter accepts, Esc cancels.
 *
 * Usage:
 *   php 3-widgets/widget-select.php
 *   php 3-widgets/widget-select.php --no-unicode   # textual glyphs
 *   php 3-widgets/widget-select.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Customizer\Widget\SelectWidget;

require __DIR__ . '/bootstrap.php';

$interact(new SelectWidget(['standard' => 'Standard', 'minimal' => 'Minimal', 'demo_umami' => 'Demo Umami'], 'minimal'), 'Select');
