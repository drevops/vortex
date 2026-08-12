<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\Internal;
use DrevOps\VortexCli\Form\TuiAdapter;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\Git;
use DrevOps\VortexCli\Utils\JsonManipulator;
use DrevOps\VortexCli\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Internal::class)]
#[CoversClass(VortexForm::class)]
#[CoversClass(TuiAdapter::class)]
#[CoversClass(Processor::class)]
#[CoversClass(JsonManipulator::class)]
#[CoversClass(Converter::class)]
#[CoversClass(Env::class)]
#[CoversClass(File::class)]
#[CoversClass(Git::class)]
#[CoversClass(Tui::class)]
class BaselineHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'defaults' => [
      [],
      $expected_defaults,
    ];
    yield 'installed project' => [
      [],
      $expected_installed,
      function (BaselineHandlerDiscoveryTest $test, Config $config): void {
        $test->stubComposerJsonValue('type', 'drupal-project');
        $test->stubComposerJsonValue('name', 'myproject_org/myproject');
        $test->stubVortexProject($config);
      },
    ];
    yield 'installed project - minimal' => [
      [],
      $expected_installed,
      function (BaselineHandlerDiscoveryTest $test, Config $config): void {
        $test->stubComposerJsonValue('name', 'myproject_org/myproject');
        $test->stubVortexProject($config);
      },
    ];
  }

}
