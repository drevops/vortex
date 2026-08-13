<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexCli\Prompts\Handlers\Theme;
use DrevOps\VortexCli\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Tests\Support\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
#[CoversClass(ThemeCustom::class)]
class ThemeHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    // Core themes have no front-end build, so FrontendBuild is skipped and
    // resolves to null.
    $expected_defaults_core = $expected_defaults;
    $expected_defaults_core[FrontendBuild::id()] = NULL;
    $expected_defaults_core[ThemeCustom::id()] = NULL;
    $expected_installed_core = $expected_installed;
    $expected_installed_core[FrontendBuild::id()] = NULL;
    $expected_installed_core[ThemeCustom::id()] = NULL;
    yield 'theme - prompt - olivero' => [
      [Theme::id() => Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::OLIVERO] + $expected_defaults_core,
    ];
    yield 'theme - prompt - claro' => [
      [Theme::id() => Key::DOWN . Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::CLARO] + $expected_defaults_core,
    ];
    yield 'theme - prompt - stark' => [
      [Theme::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::STARK] + $expected_defaults_core,
    ];
    yield 'theme - prompt - custom' => [
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'mytheme'],
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'mytheme'] + $expected_defaults,
    ];
    yield 'theme - prompt - custom - invalid' => [
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'my theme'],
      'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
    ];
    yield 'theme - prompt - custom - invalid - capitalization' => [
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'MyTheme'],
      'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
    ];
    yield 'theme - discovery - olivero' => [
      [],
      [Theme::id() => Theme::OLIVERO] + $expected_installed_core,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::OLIVERO);
      },
    ];
    yield 'theme - discovery - claro' => [
      [],
      [Theme::id() => Theme::CLARO] + $expected_installed_core,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::CLARO);
      },
    ];
    yield 'theme - discovery - stark' => [
      [],
      [Theme::id() => Theme::STARK] + $expected_installed_core,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::STARK);
      },
    ];
    yield 'theme - discovery - custom' => [
      [],
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'discovered_project'] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
      },
    ];
    yield 'theme - discovery - non-Vortex project' => [
      [],
      [Theme::id() => Theme::CUSTOM, ThemeCustom::id() => 'discovered_project'] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
      },
    ];
    yield 'theme - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No theme files exist and no DRUPAL_THEME in .env - fall back.
      },
    ];
  }

}
