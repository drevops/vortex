<?php

/**
 * @file
 * Custom theme example: the config names a theme class; run it via the facade.
 *
 * Usage:
 *   php 2-custom-theme/run.php                       # interactive, ocean theme
 *   php 2-custom-theme/run.php --prompts='{"name":"Nemo"}'
 */

declare(strict_types=1);
use DrevOps\Tui\Engine\EngineException;

require __DIR__ . '/../../vendor/autoload.php';
// The require makes the class loadable; config.yml names it directly, so no
// Theme::register() call is needed.
require __DIR__ . '/OceanTheme.php';

$options = getopt('', ['prompts::']);

$customizer = Customizer::fromFiles([__DIR__ . '/config.yml']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$banner = <<<'EOT'
  ~ ~ ~  O C E A N  ~ ~ ~
EOT;

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    $answers = $customizer->collect($prompts);
  }
  else {
    // The theme comes from the config: theme: '\Playground\CustomTheme\OceanTheme'.
    $answers = $customizer->run(banner: $banner, version: '1.0.0');
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
