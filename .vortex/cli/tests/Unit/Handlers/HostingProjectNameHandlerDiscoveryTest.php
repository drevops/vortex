<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\DeployTypes;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexCli\Prompts\Handlers\Webroot;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Tests\Support\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProjectName::class)]
class HostingProjectNameHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();

    $clear_keys = implode('', array_fill(0, 20, Key::BACKSPACE));
    yield 'hosting project name - acquia - prompt' => [
      [
        HostingProvider::id() => HostingProvider::ACQUIA,
        HostingProjectName::id() => 'my_acquia-project',
      ],
      [
        HostingProvider::id() => HostingProvider::ACQUIA,
        HostingProjectName::id() => 'my_acquia-project',
        DeployTypes::id() => [DeployTypes::ARTIFACT],
        DatabaseFetchSource::id() => DatabaseFetchSource::ACQUIA,
        Webroot::id() => Webroot::DOCROOT,
      ] + $expected_defaults,
    ];
    yield 'hosting project name - acquia - prompt - invalid' => [
      [HostingProvider::id() => HostingProvider::ACQUIA, HostingProjectName::id() => 'my_acquia project'],
      'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.',
    ];
    yield 'hosting project name - acquia - discovery from .env' => [
      [
        HostingProvider::id() => HostingProvider::ACQUIA,
      ],
      [
        HostingProvider::id() => HostingProvider::ACQUIA,
        HostingProjectName::id() => 'discovered_acquia-project',
        DeployTypes::id() => [DeployTypes::ARTIFACT],
        DatabaseFetchSource::id() => DatabaseFetchSource::ACQUIA,
        Webroot::id() => Webroot::DOCROOT,
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_ACQUIA_APP_NAME', 'discovered_acquia-project');
      },
    ];
    yield 'hosting project name - lagoon - prompt' => [
      [
        HostingProvider::id() => HostingProvider::LAGOON,
        HostingProjectName::id() => 'my_lagoon-project',
      ],
      [
        HostingProvider::id() => HostingProvider::LAGOON,
        HostingProjectName::id() => 'my_lagoon-project',
        DeployTypes::id() => [DeployTypes::LAGOON],
        DatabaseFetchSource::id() => DatabaseFetchSource::LAGOON,
      ] + $expected_defaults,
    ];
    yield 'hosting project name - lagoon - prompt - invalid' => [
      [HostingProvider::id() => HostingProvider::LAGOON, HostingProjectName::id() => 'my_lagoon project'],
      'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.',
    ];
    yield 'hosting project name - lagoon - discovery from .env' => [
      [
        HostingProvider::id() => HostingProvider::LAGOON,
      ],
      [
        HostingProvider::id() => HostingProvider::LAGOON,
        HostingProjectName::id() => 'discovered_lagoon-project',
        DeployTypes::id() => [DeployTypes::LAGOON],
        DatabaseFetchSource::id() => DatabaseFetchSource::LAGOON,
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('LAGOON_PROJECT', 'discovered_lagoon-project');
      },
    ];
    yield 'hosting project name - lagoon - discovery from drush lagoon.site.yml' => [
      [
        HostingProvider::id() => HostingProvider::LAGOON,
      ],
      [
        HostingProvider::id() => HostingProvider::LAGOON,
        HostingProjectName::id() => 'discovered_from_drush',
        DeployTypes::id() => [DeployTypes::LAGOON],
        DatabaseFetchSource::id() => DatabaseFetchSource::LAGOON,
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/drush/sites/lagoon.site.yml', <<<YAML
'*':
  host: ssh.lagoon.amazeeio.cloud
  user: discovered_from_drush-\${env-name}
  uri: https://nginx-php.\${env-name}.discovered_from_drush.au2.amazee.io
YAML
        );
      },
    ];
  }

}
