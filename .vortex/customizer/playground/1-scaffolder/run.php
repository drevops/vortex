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
use DrevOps\Tui\Engine\EngineException;

require __DIR__ . '/../../vendor/autoload.php';
// A require, not autoload: the handler namespace lives inside this example.
require __DIR__ . '/Name.php';

$options = getopt('', ['prompts::', 'schema']);

$customizer = Customizer::fromFiles([__DIR__ . '/config.yml'], ['Playground\\Scaffolder']);

if (array_key_exists('schema', $options)) {
  echo (string) json_encode($customizer->schema(), JSON_PRETTY_PRINT), PHP_EOL;
  exit(0);
}

$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    $answers = $customizer->collect($prompts, version: '1.0.0');
  }
  else {
    $answers = $customizer->run(version: '1.0.0');
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
