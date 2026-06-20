<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FrontendBuild::class)]
class FrontendBuildHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    // Core themes have no front-end build, so FrontendBuild is skipped and
    // resolves to null.
    $expected_defaults_core = $expected_defaults;
    $expected_defaults_core[FrontendBuild::id()] = NULL;

    yield 'frontend build - prompt' => [
      [FrontendBuild::id() => Key::ENTER],
      [FrontendBuild::id() => TRUE] + $expected_defaults,
    ];

    yield 'frontend build - not shown for core theme' => [
      [Theme::id() => Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::OLIVERO] + $expected_defaults_core,
    ];

    yield 'frontend build - discovery - build in container' => [
      [],
      [Theme::id() => 'discovered_project', FrontendBuild::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        $test->stubDotenvValue('VORTEX_FRONTEND_BUILD_SKIP', '0');
      },
    ];

    yield 'frontend build - discovery - skip' => [
      [],
      [Theme::id() => 'discovered_project', FrontendBuild::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        $test->stubDotenvValue('VORTEX_FRONTEND_BUILD_SKIP', '1');
      },
    ];

    yield 'frontend build - discovery - default when absent' => [
      [],
      [Theme::id() => 'discovered_project', FrontendBuild::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
      },
    ];
  }

}
