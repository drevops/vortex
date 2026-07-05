<?php

/**
 * @file
 * The smallest runner: load a config, collect answers, print the JSON result.
 *
 * There are no handlers - the engine's defaults do everything.
 *
 * Usage:
 *   php 0-minimal/run.php --prompts='{"name":"Ada","colour":"green"}'
 */

declare(strict_types=1);

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Resolver\InputResolver;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['prompts::']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$config = (new ConfigLoader())->loadFiles([__DIR__ . '/config.yml']);
$engine = new Engine($config, new HandlerRegistry());

$inputs = (new InputResolver('EXAMPLE_'))->resolve($config->fields(), $prompts, getenv());
$engine->collect($inputs, new Context((string) getcwd()));

echo $engine->answers()->toJson() . PHP_EOL;
