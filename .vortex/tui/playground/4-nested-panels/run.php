<?php

/**
 * @file
 * Nested panels: a hub with drill-in sub-panels, custom buttons and a fix-up.
 *
 * Usage:
 *   php 4-nested-panels/run.php                                   # interactive TUI
 *   php 4-nested-panels/run.php --prompts='{"environment":"dev","cdn":true}'
 */

declare(strict_types=1);

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Tui;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['prompts::']);
$prompts = array_key_exists('prompts', $options) && is_string($options['prompts']) ? $options['prompts'] : '';

$form = Form::create('Site settings')
  // Custom button labels; the buttons live on the root panel only.
  ->buttons(TRUE, 'Save', 'Discard')
  // Keep the final frame on screen after the TUI exits.
  ->clearOnExit(FALSE)
  // A fix-up reconciles dependent answers on every settle pass: no CDN outside
  // production, whatever was answered.
  ->fixup(['when' => ['field' => 'environment', 'ne' => 'prod'], 'set' => 'cdn', 'to' => FALSE])
  ->panel('identity', 'Identity', function (PanelBuilder $p): void {
    $p->description('Who this site is.');
    $p->text('name', 'Site name')->default('Umami')->required();
    $p->text('machine_name', 'Machine name')->description('Derived from the site name.')->derive(['template' => '{{name}}', 'transform' => 'machine']);
  })
  ->panel('stack', 'Stack', function (PanelBuilder $p): void {
    $p->description('What the site runs on.');
    // Options declared one by one carry their own descriptions.
    $p->select('environment', 'Environment')->default('dev')->option('dev', 'Development', 'Local containers')->option('stage', 'Staging', 'Shared preview')->option('prod', 'Production', 'Live traffic');
    $p->confirm('cdn', 'Serve via CDN?')->default(TRUE);

    // A nested sub-panel: rendered as a drillable row with a value summary.
    $p->panel('services', 'Services', function (PanelBuilder $sp): void {
      $sp->description('Optional backing services.');
      $sp->multiselect('services', 'Enabled services')->options(['solr' => 'Solr', 'redis' => 'Redis', 'clamav' => 'ClamAV']);
      $sp->text('solr_core', 'Solr core')->default('drupal')->when(['field' => 'services', 'contains' => 'solr']);

      // Sub-panels nest to any depth.
      $sp->panel('tuning', 'Tuning', function (PanelBuilder $tp): void {
        $tp->suggest('php_memory', 'PHP memory limit')->default('256M')->options(['128M' => '128M', '256M' => '256M', '512M' => '512M']);
      });
    });
  });

try {
  // Interactive TUI on a terminal; headless when prompts are given or piped.
  $answers = (new Tui($form))->run($prompts, '1.0.0');
}
catch (EngineException $exception) {
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}

echo $answers->toJson() . PHP_EOL;
