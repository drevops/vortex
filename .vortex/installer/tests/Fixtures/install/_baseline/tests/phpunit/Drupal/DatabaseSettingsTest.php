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
              'port' => '3306',
              'charset' => 'utf8mb4',
              'collation' => 'utf8mb4_general_ci',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'DATABASE_NAME' => 'database_db_name',
          'DATABASE_USERNAME' => 'database_db_user',
          'DATABASE_PASSWORD' => 'database_db_pass',
          'DATABASE_HOST' => 'database_db_host',
          'DATABASE_PORT' => 'database_db_port',
          'DATABASE_CHARSET' => 'database_utf8',
          'DATABASE_COLLATION' => 'database_utf8_unicode_ci',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'database_db_name',
              'username' => 'database_db_user',
              'password' => 'database_db_pass',
              'host' => 'database_db_host',
              'port' => 'database_db_port',
              'charset' => 'database_utf8',
              'collation' => 'database_utf8_unicode_ci',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'MARIADB_DATABASE' => 'mariadb_db_name',
          'MARIADB_USERNAME' => 'mariadb_db_user',
          'MARIADB_PASSWORD' => 'mariadb_db_pass',
          'MARIADB_HOST' => 'mariadb_db_host',
          'MARIADB_PORT' => 'mariadb_db_port',
          'MARIADB_CHARSET' => 'mariadb_latin1',
          'MARIADB_COLLATION' => 'mariadb_latin1_swedish_ci',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'mariadb_db_name',
              'username' => 'mariadb_db_user',
              'password' => 'mariadb_db_pass',
              'host' => 'mariadb_db_host',
              'port' => 'mariadb_db_port',
              'charset' => 'mariadb_latin1',
              'collation' => 'mariadb_latin1_swedish_ci',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'DATABASE_DATABASE' => 'database_db_name',
          'DATABASE_USERNAME' => 'database_db_user',
          'DATABASE_PASSWORD' => 'database_db_pass',
          'DATABASE_HOST' => 'database_db_host',
          'DATABASE_PORT' => 'database_db_port',
          'MYSQL_CHARSET' => 'mysql_utf8mb3',
          'MYSQL_COLLATION' => 'mysql_utf8mb3_bin',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'database_db_name',
              'username' => 'database_db_user',
              'password' => 'database_db_pass',
              'host' => 'database_db_host',
              'port' => 'database_db_port',
              'charset' => 'mysql_utf8mb3',
              'collation' => 'mysql_utf8mb3_bin',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],
    ];
  }

}
