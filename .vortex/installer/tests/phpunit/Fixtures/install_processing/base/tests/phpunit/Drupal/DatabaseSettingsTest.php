<?php

declare(strict_types=1);

namespace Drupal;

/**
 * Class DatabaseSettingsTest.
 *
 * Tests for Drupal database settings.
 *
 * @group drupal_settings
 */
class DatabaseSettingsTest extends SettingsTestCase {

  /**
   * Test resulting database settings based on environment variables.
   *
   * @dataProvider dataProviderDatabases
   */
  public function testDatabases(array $vars, array $expected): void {
    $this->setEnvVars($vars);

    $this->requireSettingsFile();

    $this->assertEquals($expected, $this->databases);
  }

  /**
   * Data provider for resulting database settings.
   */
  public static function dataProviderDatabases(): array {
    return [
      [
        [],
        [
          'default' => [
            'default' => [
              'database' => 'drupal',
              'username' => 'drupal',
              'password' => 'drupal',
              'host' => 'localhost',
              'port' => '',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'DATABASE_NAME' => 'test_db_name',
          'DATABASE_USERNAME' => 'test_db_user',
          'DATABASE_PASSWORD' => 'test_db_pass',
          'DATABASE_HOST' => 'test_db_host',
          'DATABASE_PORT' => 'test_db_port',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'test_db_name',
              'username' => 'test_db_user',
              'password' => 'test_db_pass',
              'host' => 'test_db_host',
              'port' => 'test_db_port',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'DATABASE_DATABASE' => 'test_db_name',
          'DATABASE_USERNAME' => 'test_db_user',
          'DATABASE_PASSWORD' => 'test_db_pass',
          'DATABASE_HOST' => 'test_db_host',
          'DATABASE_PORT' => 'test_db_port',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'test_db_name',
              'username' => 'test_db_user',
              'password' => 'test_db_pass',
              'host' => 'test_db_host',
              'port' => 'test_db_port',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],
    ];
  }

}
