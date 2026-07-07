<?php

/**
 * @file
 * Runs every widget interactively, one after another.
 *
 * Usage:
 *   php 3-widgets/widgets.php
 *   php 3-widgets/widgets.php --no-unicode   # textual glyphs
 *   php 3-widgets/widgets.php --no-ansi      # no colour
 */

declare(strict_types=1);

use DrevOps\Customizer\Widget\ConfirmWidget;
use DrevOps\Customizer\Widget\MultiSelectWidget;
use DrevOps\Customizer\Widget\SelectWidget;
use DrevOps\Customizer\Widget\SuggestWidget;
use DrevOps\Customizer\Widget\TextWidget;

require __DIR__ . '/bootstrap.php';

$interact(new TextWidget('Acme Site'), 'Text');
$interact(new SelectWidget(['standard' => 'Standard', 'minimal' => 'Minimal', 'demo_umami' => 'Demo Umami'], 'minimal'), 'Select');
$interact(new MultiSelectWidget(['redis' => 'Redis', 'solr' => 'Solr', 'clamav' => 'ClamAV'], ['redis']), 'MultiSelect');
$interact(new ConfirmWidget(TRUE), 'Confirm');
$interact(new SuggestWidget(['UTC', 'Europe/London', 'Europe/Paris', 'Australia/Sydney'], ''), 'Suggest');
