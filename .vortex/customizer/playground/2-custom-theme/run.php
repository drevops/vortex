<?php

/**
 * @file
 * Custom theme example: register a theme class and drive the TUI with it.
 *
 * Usage:
 *   php 2-custom-theme/run.php                       # interactive, ocean theme
 *   php 2-custom-theme/run.php --prompts='{"name":"Nemo"}'
 */

declare(strict_types=1);

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Resolver\InputResolver;
use DrevOps\Customizer\Tui\PanelController;
use DrevOps\Customizer\Tui\Terminal;
use DrevOps\Customizer\Tui\Theme;

require __DIR__ . '/../../vendor/autoload.php';
// The require makes the class loadable; config.yml names it directly, so no
// Theme::register() call is needed.
require __DIR__ . '/OceanTheme.php';

$options = getopt('', ['prompts::']);

$config = (new ConfigLoader())->loadFiles([__DIR__ . '/config.yml']);
$engine = new Engine($config, new HandlerRegistry());
$context = new Context((string) getcwd());
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$banner = <<<'EOT'
  ~ ~ ~  O C E A N  ~ ~ ~
EOT;

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    $inputs = (new InputResolver('OCEAN_'))->resolve($config->fields(), $prompts, getenv());
    $engine->collect($inputs, $context);
    $answers = $engine->answers();
  }
  else {
    $engine->collect([], $context);
    $controller = new PanelController($config, Theme::create($config->theme), $engine->answers()->values, $engine->answers()->provenance, $banner, '1.0.0');
    $answers = $controller->run(new Terminal());
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
