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

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    $clear_keys = implode('', array_fill(0, 20, Key::BACKSPACE));

    return [
      'theme - prompt - olivero' => [
        [Theme::id() => Key::DOWN . Key::ENTER],
        [Theme::id() => Theme::OLIVERO] + $expected_defaults,
      ],

      'theme - prompt - claro' => [
        [Theme::id() => Key::DOWN . Key::DOWN . Key::ENTER],
        [Theme::id() => Theme::CLARO] + $expected_defaults,
      ],

      'theme - prompt - stark' => [
        [Theme::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER],
        [Theme::id() => Theme::STARK] + $expected_defaults,
      ],

      'theme - prompt - custom' => [
        [Theme::id() => Key::ENTER . $clear_keys . 'mytheme'],
        [Theme::id() => 'mytheme'] + $expected_defaults,
      ],

      'theme - prompt - custom - invalid' => [
        [Theme::id() => Key::ENTER . $clear_keys . 'my theme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - prompt - custom - invalid - capitalization' => [
        [Theme::id() => Key::ENTER . $clear_keys . 'MyTheme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - discovery - olivero' => [
        [],
        [Theme::id() => Theme::OLIVERO] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', Theme::OLIVERO);
        },
      ],

      'theme - discovery - claro' => [
        [],
        [Theme::id() => Theme::CLARO] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', Theme::CLARO);
        },
      ],

      'theme - discovery - stark' => [
        [],
        [Theme::id() => Theme::STARK] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', Theme::STARK);
        },
      ],

      'theme - discovery - custom' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],

      'theme - discovery - non-Vortex project' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],

      'theme - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          // No theme files exist and no DRUPAL_THEME in .env - fall back.
        },
      ],
    ];
  }

}
