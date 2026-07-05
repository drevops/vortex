<?php

/**
 * @file
 * Package scaffolder runner: interactive TUI or non-interactive collection.
 *
 * Usage:
 *   php 1-scaffolder/run.php                                  # interactive TUI
 *   php 1-scaffolder/run.php --prompts='{"name":"My Widget"}' # non-interactive
 *   php 1-scaffolder/run.php --schema                         # print JSON schema
 */

declare(strict_types=1);

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Resolver\InputResolver;
use DrevOps\Customizer\Schema\SchemaGenerator;
use DrevOps\Customizer\Tui\PanelController;
use DrevOps\Customizer\Tui\Terminal;
use DrevOps\Customizer\Tui\Theme;

require __DIR__ . '/../../vendor/autoload.php';
// A require, not autoload: the handler namespace lives inside this example.
require __DIR__ . '/Name.php';

$options = getopt('', ['prompts::', 'schema']);

$config = (new ConfigLoader())->loadFiles([__DIR__ . '/config.yml']);
$registry = new HandlerRegistry(['Playground\\Scaffolder']);
$engine = new Engine($config, $registry);

if (array_key_exists('schema', $options)) {
  echo (string) json_encode((new SchemaGenerator($config))->generate(), JSON_PRETTY_PRINT), PHP_EOL;
  exit(0);
}

$context = new Context((string) getcwd(), [], FALSE, '1.0.0');
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    $inputs = (new InputResolver('SCAFFOLD_'))->resolve($config->fields(), $prompts, getenv());
    $engine->collect($inputs, $context);
    $answers = $engine->answers();
  }
  else {
    $engine->collect([], $context);
    $controller = new PanelController($config, Theme::create('dark'), $engine->answers()->values, $engine->answers()->provenance);
    $answers = $controller->run(new Terminal());
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
