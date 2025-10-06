<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployType;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(HostingProvider::class)]
class HostingProviderPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'hosting provider - prompt' => [
        [HostingProvider::id() => Key::ENTER],
        [HostingProvider::id() => HostingProvider::NONE] + $expected_defaults,
      ],

      'hosting provider - discovery - Acquia' => [
        [],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          HostingProjectName::id() => 'myproject',
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/hooks/somehook');
        },
      ],

      'hosting provider - discovery - Acquia from env' => [
        [],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          HostingProjectName::id() => 'myproject',
          Webroot::id() => Webroot::DOCROOT,
          DeployType::id() => [DeployType::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::ACQUIA);
        },
      ],

      'hosting provider - discovery - Lagoon' => [
        [],
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          HostingProjectName::id() => 'myproject',
          DeployType::id() => [DeployType::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/.lagoon.yml');
        },
      ],

      'hosting provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No hooks, .lagoon.yml, or ACQUIA env var - fall back to default.
        },
      ],
    ];
  }

}
