<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
class ThemeHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    $clear_keys = implode('', array_fill(0, 20, Key::BACKSPACE));
    yield 'theme - prompt - olivero' => [
      [Theme::id() => Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::OLIVERO] + $expected_defaults,
    ];
    yield 'theme - prompt - claro' => [
      [Theme::id() => Key::DOWN . Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::CLARO] + $expected_defaults,
    ];
    yield 'theme - prompt - stark' => [
      [Theme::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::STARK] + $expected_defaults,
    ];
    yield 'theme - prompt - custom' => [
      [Theme::id() => Key::ENTER . $clear_keys . 'mytheme'],
      [Theme::id() => 'mytheme'] + $expected_defaults,
    ];
    yield 'theme - prompt - custom - invalid' => [
      [Theme::id() => Key::ENTER . $clear_keys . 'my theme'],
      'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
    ];
    yield 'theme - prompt - custom - invalid - capitalization' => [
      [Theme::id() => Key::ENTER . $clear_keys . 'MyTheme'],
      'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
    ];
    yield 'theme - discovery - olivero' => [
      [],
      [Theme::id() => Theme::OLIVERO] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::OLIVERO);
      },
    ];
    yield 'theme - discovery - claro' => [
      [],
      [Theme::id() => Theme::CLARO] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::CLARO);
      },
    ];
    yield 'theme - discovery - stark' => [
      [],
      [Theme::id() => Theme::STARK] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', Theme::STARK);
      },
    ];
    yield 'theme - discovery - custom' => [
      [],
      [Theme::id() => 'discovered_project'] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
      },
    ];
    yield 'theme - discovery - non-Vortex project' => [
      [],
      [Theme::id() => 'discovered_project'] + $expected_defaults,
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
