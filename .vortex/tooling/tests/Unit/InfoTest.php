<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for info script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class InfoTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_PROJECT' => 'test_project',
      'COMPOSE_PROJECT_NAME' => 'test_compose',
      'VORTEX_LOCALDEV_URL' => 'test-project.docker.amazee.io',
      'WEBROOT' => 'web',
      'DATABASE_HOST' => 'database',
      'DATABASE_USERNAME' => 'drupal',
      'DATABASE_PASSWORD' => 'drupal',
      'DATABASE_PORT' => '3306',
      'VORTEX_HOST_DB_PORT' => '33060',
      'VORTEX_SHOW_LOGIN' => '',
    ]);
  }

  #[DataProvider('dataProviderInfo')]
  public function testInfo(array $env_vars, array $mocks, array $expected): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    $output = $this->runScript('src/info');

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderInfo(): array {
    return [
      'basic' => [
        [],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1]],
        [
          '* [INFO] Project information',
          '* Project name                : test_project',
          '* Docker Compose project name : test_compose',
          '* Site local URL              : http://test-project.docker.amazee.io',
          '* Path to web root            :',
          '* /web',
          '* DB host                     : database',
          '* DB username                 : drupal',
          '* DB password                 : drupal',
          '* DB port                     : 3306',
          '* DB port on host             : 33060',
          '* Mailhog URL                 : http://mailhog.docker.amazee.io/',
          "* Xdebug                      : Disabled ('ahoy debug' to enable)",
          "* Use 'ahoy login' to generate Drupal login link.",
          '! DB-in-image',
          '! Solr URL',
          '! Selenium VNC',
          '! SequelAce',
        ],
      ],

      'xdebug enabled' => [
        [],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 0]],
        [
          "* Xdebug                      : Enabled ('ahoy up cli' to disable)",
          "! Disabled ('ahoy debug' to enable)",
        ],
      ],

      'db image' => [
        ['VORTEX_DB_IMAGE' => 'myorg/db:latest'],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1]],
        ['* DB-in-image                 : myorg/db:latest'],
      ],

      'solr port' => [
        ['VORTEX_HOST_SOLR_PORT' => '8983'],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1]],
        ['* Solr URL on host            : http://127.0.0.1:8983'],
      ],

      'selenium vnc port' => [
        ['VORTEX_HOST_SELENIUM_VNC_PORT' => '7900'],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1]],
        ['* Selenium VNC URL on host    : http://localhost:7900/?autoconnect=1&password=secret'],
      ],

      'sequel ace' => [
        ['VORTEX_HOST_HAS_SEQUELACE' => '1'],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1]],
        ["* ('ahoy db' to start SequelAce)"],
      ],

      'show login' => [
        ['VORTEX_SHOW_LOGIN' => '1'],
        [
          ['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 1],
          ['cmd' => 'php ' . dirname(__DIR__, 2) . '/src/login', 'output' => 'http://example.com/user/reset/1/abc123/login', 'result_code' => 0],
        ],
        [
          '* Site login link             :',
          '* http://example.com/user/reset/1/abc123/login',
          "! Use 'ahoy login' to generate Drupal login link.",
        ],
      ],

      'all optional fields' => [
        [
          'VORTEX_DB_IMAGE' => 'myorg/db:latest',
          'VORTEX_HOST_SOLR_PORT' => '8983',
          'VORTEX_HOST_SELENIUM_VNC_PORT' => '7900',
          'VORTEX_HOST_HAS_SEQUELACE' => '1',
        ],
        [['cmd' => 'php -v 2>/dev/null | grep -q Xdebug', 'result_code' => 0]],
        [
          '* DB-in-image                 : myorg/db:latest',
          '* Solr URL on host            : http://127.0.0.1:8983',
          '* Selenium VNC URL on host    : http://localhost:7900/?autoconnect=1&password=secret',
          "* ('ahoy db' to start SequelAce)",
          "* Enabled ('ahoy up cli' to disable)",
        ],
      ],
    ];
  }

}
