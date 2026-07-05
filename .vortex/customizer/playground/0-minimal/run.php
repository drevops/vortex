<?php

/**
 * @file
 * The smallest runner: load a config, collect answers, print the JSON result.
 *
 * The Customizer facade wires the engine internally - there are no handlers, so
 * the engine's defaults do everything.
 *
 * Usage:
 *   php 0-minimal/run.php --prompts='{"name":"Ada","colour":"green"}'
 */

declare(strict_types=1);

use DrevOps\Customizer\Customizer;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['prompts::']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$answers = Customizer::fromFiles([__DIR__ . '/config.yml'])->collect($prompts);

echo $answers->toJson() . PHP_EOL;
