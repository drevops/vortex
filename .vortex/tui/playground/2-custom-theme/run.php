<?php

/**
 * @file
 * Custom theme example: the form names a theme class; run it via the facade.
 *
 * Usage:
 *   php 2-custom-theme/run.php                       # interactive, ocean theme
 *   php 2-custom-theme/run.php --prompts='{"name":"Nemo"}'
 */

declare(strict_types=1);

use Playground\CustomTheme\OceanTheme;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Tui;

require __DIR__ . '/../../vendor/autoload.php';
// The require makes the class loadable; the form names it directly, so no
// Theme::register() call is needed.
require __DIR__ . '/OceanTheme.php';

$options = getopt('', ['prompts::']);

$config = Form::create('Ocean theme demo')
  ->theme(OceanTheme::class)
  ->panel('profile', 'Diver profile', function (PanelBuilder $p): void {
    $p->text('name', 'Name')->default('Explorer');
    $p->select('depth', 'Preferred depth')->default('reef')->options(['surface' => 'Surface', 'reef' => 'Reef', 'abyss' => 'Abyss']);
    $p->multiselect('gear', 'Gear')->options(['mask' => 'Mask', 'fins' => 'Fins', 'tank' => 'Tank']);
  })
  ->build();

$customizer = new Tui($config);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$banner = <<<'EOT'
  ~ ~ ~  O C E A N  ~ ~ ~
EOT;

try {
  if ($prompts !== '' || stream_isatty(STDIN) === FALSE) {
    $answers = $customizer->collect($prompts);
  }
  else {
    // The theme comes from the form: ->theme(OceanTheme::class).
    $answers = $customizer->interact(banner: $banner, version: '1.0.0');
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
