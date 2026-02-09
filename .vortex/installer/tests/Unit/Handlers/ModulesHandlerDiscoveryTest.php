<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Modules::class)]
class ModulesHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'modules - prompt' => [
        [Modules::id() => Key::ENTER],
        [Modules::id() => array_keys(Modules::getAvailableModules())] + $expected_defaults,
      ],

      'modules - discovery - some modules' => [
        [],
        [Modules::id() => ['config_split', 'pathauto', 'shield']] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies([
            'drupal/config_split' => '^2.0.2',
            'drupal/pathauto' => '^1.14',
            'drupal/shield' => '^1.8',
          ]);
        },
      ],

      'modules - discovery - all modules' => [
        [],
        [Modules::id() => array_keys(Modules::getAvailableModules())] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies([
            'drupal/admin_toolbar' => '^3.6.2',
            'drupal/coffee' => '^2.0.1',
            'drupal/config_split' => '^2.0.2',
            'drupal/config_update' => '^2@alpha',
            'drupal/environment_indicator' => '^4.0.25',
            'drupal/pathauto' => '^1.14',
            'drupal/redirect' => '^1.12',
            'drupal/robotstxt' => '^1.6',
            'drupal/seckit' => '^2.0.3',
            'drupal/shield' => '^1.8',
            'drupal/stage_file_proxy' => '^3.1.6',
            'drupal/xmlsitemap' => '^2.0',
          ]);
        },
      ],

      'modules - discovery - none' => [
        [],
        [Modules::id() => []] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies([
            'drupal/core-recommended' => '~11.2.5',
          ]);
        },
      ],

      'modules - discovery - filters out core packages' => [
        [],
        [Modules::id() => ['admin_toolbar', 'pathauto']] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies([
            'drupal/core-recommended' => '~11.2.5',
            'drupal/core-composer-scaffold' => '~11.2.5',
            'drupal/core-dev' => '~11.2.5',
            'drupal/admin_toolbar' => '^3.6.2',
            'drupal/pathauto' => '^1.14',
          ]);
        },
      ],

      'modules - discovery - filters out service modules' => [
        [],
        [Modules::id() => ['admin_toolbar', 'pathauto']] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies([
            'drupal/admin_toolbar' => '^3.6.2',
            'drupal/clamav' => '^2.1',
            'drupal/pathauto' => '^1.14',
            'drupal/redis' => '^1.10',
            'drupal/search_api' => '^1.40',
            'drupal/search_api_solr' => '^4.3.10',
          ]);
        },
      ],

      'modules - discovery - invalid composer.json' => [
        [],
        $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // Invalid JSON causes discovery to fail and fall back to defaults.
          File::dump(static::$sut . '/composer.json', 'invalid json content');
        },
      ],

      'modules - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubComposerJsonDependencies([
            'drupal/admin_toolbar' => '^3.6.2',
            'drupal/pathauto' => '^1.14',
          ]);
        },
      ],

      'modules - discovery - non-Vortex project, invalid composer.json' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          // Invalid JSON causes discovery to fail and fall back to defaults.
          File::dump(static::$sut . '/composer.json', 'invalid json content');
        },
      ],
    ];
  }

}
