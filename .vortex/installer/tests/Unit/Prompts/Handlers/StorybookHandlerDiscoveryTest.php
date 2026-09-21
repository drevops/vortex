<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\FrontendBuild;
use DrevOps\VortexInstaller\Prompts\Handlers\Storybook;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Storybook::class)]
class StorybookHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    // Core themes have no Storybook, so the prompt is skipped and resolves to
    // null, as does the front-end build prompt beside it.
    $expected_defaults_core = $expected_defaults;
    $expected_defaults_core[FrontendBuild::id()] = NULL;
    $expected_defaults_core[Storybook::id()] = NULL;

    yield 'storybook - prompt - disabled' => [
      [Storybook::id() => Key::ENTER],
      [Storybook::id() => FALSE] + $expected_defaults,
    ];
    yield 'storybook - prompt - enabled' => [
      [Storybook::id() => Key::LEFT . Key::ENTER],
      [Storybook::id() => TRUE] + $expected_defaults,
    ];
    yield 'storybook - not shown for core theme' => [
      [Theme::id() => Key::DOWN . Key::ENTER],
      [Theme::id() => Theme::OLIVERO] + $expected_defaults_core,
    ];
    yield 'storybook - discovery - provision script present' => [
      [],
      [Theme::id() => 'discovered_project', Storybook::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
        File::dump(static::$sut . '/scripts/provision-50-storybook.sh');
      },
    ];
    yield 'storybook - discovery - provision script absent' => [
      [],
      [Theme::id() => 'discovered_project', Storybook::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_THEME', 'discovered_project');
      },
    ];
  }

}
