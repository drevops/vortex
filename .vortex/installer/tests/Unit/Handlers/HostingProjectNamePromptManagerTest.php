<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProjectName::class)]
class HostingProjectNamePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    $clear_keys = implode('', array_fill(0, 20, Key::BACKSPACE));

    return [
      'hosting project name - acquia - prompt' => [
        [
          HostingProvider::id() => Key::DOWN . Key::ENTER . $clear_keys . 'my_acquia-project',
        ],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          HostingProjectName::id() => 'my_acquia-project',
          DeployTypes::id() => [DeployTypes::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
        ] + $expected_defaults,
      ],

      'hosting project name - acquia - prompt - invalid' => [
        [HostingProvider::id() => Key::DOWN . Key::ENTER . $clear_keys . 'my_acquia project'],
        'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.',
      ],

      'hosting project name - acquia - discovery from .env' => [
        [
          HostingProvider::id() => Key::DOWN . Key::ENTER,
        ],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          HostingProjectName::id() => 'discovered_acquia-project',
          DeployTypes::id() => [DeployTypes::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_ACQUIA_APP_NAME', 'discovered_acquia-project');
        },
      ],

      'hosting project name - acquia - discovery from settings.acquia.php' => [
        [
          HostingProvider::id() => Key::DOWN . Key::ENTER,
        ],
        [
          HostingProvider::id() => HostingProvider::ACQUIA,
          HostingProjectName::id() => 'discovered_from_settings',
          DeployTypes::id() => [DeployTypes::ARTIFACT],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::ACQUIA,
          Webroot::id() => Webroot::DOCROOT,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/sites/default/includes/providers/settings.acquia.php', <<<PHP
<?php
// Acquia settings file.
require '/var/www/site-php/discovered_from_settings/discovered_from_settings-settings.inc';
PHP
          );
        },
      ],

      'hosting project name - lagoon - prompt' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::ENTER . $clear_keys . 'my_lagoon-project',
        ],
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          HostingProjectName::id() => 'my_lagoon-project',
          DeployTypes::id() => [DeployTypes::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $expected_defaults,
      ],

      'hosting project name - lagoon - prompt - invalid' => [
        [HostingProvider::id() => Key::DOWN . Key::DOWN . Key::ENTER . $clear_keys . 'my_lagoon project'],
        'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.',
      ],

      'hosting project name - lagoon - discovery from .env' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::ENTER,
        ],
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          HostingProjectName::id() => 'discovered_lagoon-project',
          DeployTypes::id() => [DeployTypes::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('LAGOON_PROJECT', 'discovered_lagoon-project');
        },
      ],

      'hosting project name - lagoon - discovery from drush lagoon.site.yml' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::ENTER,
        ],
        [
          HostingProvider::id() => HostingProvider::LAGOON,
          HostingProjectName::id() => 'discovered_from_drush',
          DeployTypes::id() => [DeployTypes::LAGOON],
          DatabaseDownloadSource::id() => DatabaseDownloadSource::LAGOON,
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/drush/sites/lagoon.site.yml', <<<YAML
'*':
  host: ssh.lagoon.amazeeio.cloud
  user: discovered_from_drush-\${env-name}
  uri: https://nginx-php.\${env-name}.discovered_from_drush.au2.amazee.io
YAML
          );
        },
      ],
    ];
  }

}
