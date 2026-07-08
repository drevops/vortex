<?php

/**
 * @file
 * The smallest runner: build a form, collect answers, print the JSON result.
 *
 * The Tui facade wires the engine internally - there are no handlers, so the
 * engine's defaults do everything.
 *
 * Usage:
 *   php 0-minimal/run.php --prompts='{"name":"Ada","colour":"green"}'
 */

declare(strict_types=1);

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Tui;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['prompts::']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$form = Form::create('Minimal')
  ->panel('main', 'Main', function (PanelBuilder $p): void {
    $p->text('name', 'Your name')->required();
    $p->select('colour', 'Favourite colour')->default('blue')->options(['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']);
  });

$answers = (new Tui($form))->collect($prompts);

echo $answers->toJson() . PHP_EOL;
