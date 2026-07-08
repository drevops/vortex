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

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Tui;

require __DIR__ . '/../../vendor/autoload.php';
// A require, not autoload: the handler namespace lives inside this example.
require __DIR__ . '/Name.php';

$options = getopt('', ['prompts::', 'schema']);

$config = Form::create('Package scaffolder')
  ->panel('general', 'General', function (PanelBuilder $p): void {
    $p->description('Naming and identity.');
    // A required free-text field. The custom Name handler trims and validates.
    $p->text('name', 'Package name')->description('A human-readable name, e.g. "My Widget".')->required();
    // Derived: machine name of the package name (str2name "machine").
    $p->text('machine_name', 'Machine name')->description('Derived from the package name.')->derive(['template' => '{{name}}', 'transform' => 'machine']);
    // A plain default that other fields derive from.
    $p->text('vendor', 'Vendor')->default('acme');
    // Derived through a chain: "{{vendor}}/{{machine_name}}", lowercased.
    $p->text('package', 'Composer package')->description('Derived from vendor and machine name.')->derive(['template' => '{{vendor}}/{{machine_name}}', 'transform' => 'lower']);
    // Derived PHP namespace (str2name "pascal").
    $p->text('namespace', 'PHP namespace')->derive(['template' => '{{name}}', 'transform' => 'pascal']);
  })
  ->panel('build', 'Build & features', function (PanelBuilder $p): void {
    $p->description('What the package ships with.');
    // A single-choice list.
    $p->select('type', 'Package type')->default('library')->options(['library' => 'Library', 'application' => 'Application', 'cli' => 'CLI tool']);
    // A multi-select list.
    $p->multiselect('features', 'Features')->description('Space to toggle, type to filter.')->options(['tests' => 'Tests', 'ci' => 'CI', 'docker' => 'Docker', 'docs' => 'Docs']);
    // Conditional: only shown when "docker" is among the selected features.
    $p->text('docker_image', 'Docker base image')->default('php:8.4-cli')->when(['field' => 'features', 'contains' => 'docker']);
    // A multi-field conditional: conditions compose with all/any/not, so a field
    // can depend on any number of others - here docker selected AND type application.
    $p->confirm('docker_compose', 'Generate a docker-compose.yml?')->default(TRUE)->when(['all' => [['field' => 'features', 'contains' => 'docker'], ['field' => 'type', 'eq' => 'application']]]);
    // An autocomplete with free-text fallback.
    $p->suggest('php_version', 'PHP version')->default('8.4')->options(['8.1' => '8.1', '8.2' => '8.2', '8.3' => '8.3', '8.4' => '8.4']);
    // A yes/no toggle.
    $p->confirm('private', 'Private package?')->default(FALSE);
  })
  ->build();

$customizer = new Tui($config, ['Playground\\Scaffolder']);

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
    $answers = $customizer->interact(version: '1.0.0');
  }
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
