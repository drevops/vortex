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

use DrevOps\Customizer\Widget\MultiSelectWidget;

require __DIR__ . '/bootstrap.php';

$interact(new MultiSelectWidget(['redis' => 'Redis', 'solr' => 'Solr', 'clamav' => 'ClamAV'], ['redis']), 'MultiSelect');
