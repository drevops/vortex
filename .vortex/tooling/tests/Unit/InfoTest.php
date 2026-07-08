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

    $this->envSetMultiple([
      'VORTEX_PROJECT' => 'test_project',
      'COMPOSE_PROJECT_NAME' => 'test_compose',
      'LOCALDEV_URL' => 'test-project.docker.amazee.io',
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
  public function testInfo(array $env_vars, array $mocks, array $expected, bool $xdebug_enabled = FALSE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    $php_version_output = 'PHP 8.3.10 (cli) (built: Aug  1 2024 10:00:00) (NTS)';
    if ($xdebug_enabled) {
      $php_version_output .= "\n    with Xdebug v3.3.2, Copyright (c) 2002-2024, by Derick Rethans";
    }
    $this->mockShellExec($php_version_output);

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    $output = $this->runScript('src/vortex-info');

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderInfo(): array {
    return [
      'basic' => [
        [],
        [],
        [
          '* [INFO] Project information:',
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
        [],
        [
          "* Xdebug                      : Enabled ('ahoy up cli' to disable)",
          "! Disabled ('ahoy debug' to enable)",
        ],
        TRUE,
      ],

      'db image' => [
        ['VORTEX_DB_IMAGE' => 'myorg/db:latest'],
        [],
        ['* DB-in-image                 : myorg/db:latest'],
      ],

      'solr port' => [
        ['VORTEX_HOST_SOLR_PORT' => '8983'],
        [],
        ['* Solr URL on host            : http://127.0.0.1:8983'],
      ],

      'selenium vnc port' => [
        ['VORTEX_HOST_SELENIUM_VNC_PORT' => '7900'],
        [],
        ['* Selenium VNC URL on host    : http://localhost:7900/?autoconnect=1&password=secret'],
      ],

      'sequel ace' => [
        ['VORTEX_HOST_HAS_SEQUELACE' => '1'],
        [],
        ["* ('ahoy db' to start SequelAce)"],
      ],

      'show login' => [
        ['VORTEX_SHOW_LOGIN' => '1'],
        [
          ['cmd' => 'php ' . dirname(__DIR__, 2) . '/src/vortex-login', 'output' => 'http://example.com/user/reset/1/abc123/login', 'result_code' => 0],
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
        [],
        [
          '* DB-in-image                 : myorg/db:latest',
          '* Solr URL on host            : http://127.0.0.1:8983',
          '* Selenium VNC URL on host    : http://localhost:7900/?autoconnect=1&password=secret',
          "* ('ahoy db' to start SequelAce)",
          "* Enabled ('ahoy up cli' to disable)",
        ],
        TRUE,
      ],
    ];
  }

}
