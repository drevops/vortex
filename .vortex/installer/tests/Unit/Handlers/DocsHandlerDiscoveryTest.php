<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(PreserveDocsProject::class)]
class DocsHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'preserve project documentation - prompt' => [
      [PreserveDocsProject::id() => Key::ENTER],
      [PreserveDocsProject::id() => TRUE] + $expected_defaults,
    ];
    yield 'preserve project documentation - discovery' => [
      [],
      [PreserveDocsProject::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docs/README.md');
      },
    ];
    yield 'preserve project documentation - discovery - removed' => [
      [],
      [PreserveDocsProject::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'preserve project documentation - discovery - non-Vortex' => [
      [],
      [PreserveDocsProject::id() => TRUE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/docs/README.md');
      },
    ];
    yield 'preserve project documentation - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No docs/README.md and not installed - should fall back to default.
      },
    ];
  }

}
