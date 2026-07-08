<?php

/**
 * @file
 * Discovery: detect defaults from an existing project directory (update mode).
 *
 * The fields carry `discover` rules evaluated against `sample/`: a `.env` key,
 * a JSON dot-path, a path-exists check and a directory scan. Per-question env
 * overrides use the form-declared `MYAPP_` prefix instead of the default
 * `TUI_`.
 *
 * Usage:
 *   php 5-discovery/run.php                                 # discover from sample/
 *   MYAPP_TIMEZONE=UTC php 5-discovery/run.php              # env override wins
 *   php 5-discovery/run.php --prompts='{"name":"Renamed"}'  # prompts win over all
 */

declare(strict_types=1);

use DrevOps\Tui\Answers\SummaryFormatter;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Tui;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['prompts::']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$form = Form::create('Discovery demo', 'an existing project')
  // Per-question env overrides read MYAPP_<ID> instead of the default TUI_<ID>.
  ->envPrefix('MYAPP_')
  ->panel('project', 'Project', function (PanelBuilder $p): void {
    // Read a dot-path from a JSON file.
    $p->text('name', 'Project name')->discover(['json' => ['file' => 'composer.json', 'path' => 'name']]);
    // Read a key from the .env file.
    $p->text('timezone', 'Timezone')->default('UTC')->discover(['dotenv' => 'TZ']);
    // Whether a path exists.
    $p->confirm('docker', 'Uses Docker?')->discover(['exists' => 'docker-compose.yml']);
    // List directory entries ("type" is dir / file / any).
    $p->multiselect('modules', 'Custom modules')->options(['alpha' => 'Alpha', 'beta' => 'Beta', 'gamma' => 'Gamma'])->discover(['scan' => ['dir' => 'modules', 'type' => 'dir']]);
  });

$tui = new Tui($form);

try {
  // Update mode (the third argument) is what enables discovery.
  $answers = $tui->collect($prompts, __DIR__ . '/sample', TRUE, '1.0.0');
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

// The summary groups answers by panel and badges non-default provenance:
// "detected" for discovered values, "edited" for env and prompt inputs.
echo (new SummaryFormatter())->format($tui->config(), $answers) . PHP_EOL;
echo PHP_EOL;
echo $answers->toJson() . PHP_EOL;
