<?php

/**
 * @file
 * Playground runner for the drevops/customizer engine.
 *
 * Loads playground/config.yml, wires the example handlers, then collects answers
 * either interactively (the panel TUI) or non-interactively (--prompts), and
 * prints the schema-valid JSON result.
 *
 * Usage:
 *   php playground/run.php                                  # interactive TUI
 *   php playground/run.php --prompts='{"name":"My Widget"}' # non-interactive
 *   php playground/run.php --theme=light                    # pick a theme
 *   php playground/run.php --schema                         # print JSON schema
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

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('', ['prompts::', 'theme::', 'schema']);

$config = (new ConfigLoader())->loadFiles([__DIR__ . '/config.yml']);
// Handlers are auto-discovered from the field id (e.g. "name" -> Name).
$registry = new HandlerRegistry(['Playground\\Handler']);
$engine = new Engine($config, $registry);

// Print the JSON schema (what an agent or a form would consume) and exit.
if (array_key_exists('schema', $options)) {
  echo json_encode((new SchemaGenerator($config))->generate(), JSON_PRETTY_PRINT) . PHP_EOL;
  exit(0);
}

$context = new Context((string) getcwd(), [], FALSE, '1.0.0');

$banner = <<<'EOT'
 ___ _              ___                  _
| _ \ |__ _ _  _  / __|__ _ _ _ ___ _  _| |
|  _/ / _` | || |( (_ / _` | '_/ _ \ || | |
|_| |_\__,_|\_, | \___\__,_|_| \___/\_,_|_|
            |__/
EOT;

$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    // Non-interactive: resolve answers from --prompts and the environment.
    $inputs = (new InputResolver('PLAYGROUND_'))->resolve($config->fields(), $prompts, getenv());
    $engine->collect($inputs, $context);
    $answers = $engine->answers();
  }
  else {
    // Interactive: seed the panel TUI with the resolved defaults, then run it.
    $engine->collect([], $context);
    $theme = array_key_exists('theme', $options) && is_string($options['theme']) ? $options['theme'] : 'dark';
    $controller = new PanelController($config, new Theme($theme), $engine->answers()->values, $engine->answers()->provenance, $banner, '1.0.0');
    $answers = $controller->run(new Terminal());
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
