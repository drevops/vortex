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

use DrevOps\Customizer\Widget\TextWidget;

require __DIR__ . '/bootstrap.php';

$interact(new TextWidget('Acme Site'), 'Text');
