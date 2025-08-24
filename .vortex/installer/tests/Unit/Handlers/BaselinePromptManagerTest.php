<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Internal::class)]
#[CoversClass(PromptManager::class)]
#[CoversClass(JsonManipulator::class)]
#[CoversClass(Converter::class)]
#[CoversClass(Env::class)]
#[CoversClass(File::class)]
#[CoversClass(Git::class)]
#[CoversClass(Tui::class)]
class BaselinePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'defaults' => [
        [],
        $expected_defaults,
      ],

      'installed project' => [
        [],
        $expected_installed,
        function (BaselinePromptManagerTest $test, Config $config): void {
          $test->stubComposerJsonValue('type', 'drupal-project');
          $test->stubComposerJsonValue('name', 'myproject_org/myproject');
          $test->stubVortexProject($config);
        },
      ],

      'installed project - minimal' => [
        [],
        $expected_installed,
        function (BaselinePromptManagerTest $test, Config $config): void {
          $test->stubComposerJsonValue('name', 'myproject_org/myproject');
          $test->stubVortexProject($config);
        },
      ],
    ];
  }

}
