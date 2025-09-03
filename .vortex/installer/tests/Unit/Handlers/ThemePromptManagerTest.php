<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
class ThemePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'theme - prompt' => [
        [Theme::id() => 'mytheme'],
        [Theme::id() => 'mytheme'] + $expected_defaults,
      ],

      'theme - prompt - invalid' => [
        [Theme::id() => 'my theme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - prompt - invalid - capitalization' => [
        [Theme::id() => 'MyTheme'],
        'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'theme - discovery' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        },
      ],

      'theme - discovery - non-Vortex project' => [
        [],
        [Theme::id() => 'discovered_project'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/themes/custom/discovered_project/discovered_project.info');
        },
      ],

      'theme - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No theme files exist and no DRUPAL_THEME in .env - fall back.
        },
      ],
    ];
  }

}
