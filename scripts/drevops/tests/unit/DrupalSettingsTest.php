<?php

namespace Drevops\Tests;

/**
 * Class DrupalSettingsTest.
 *
 * Tests for Drupal settings.
 *
 * @package Drevops\Tests
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
 * phpcs:disable Drupal.NamingConventions.ValidGlobal.GlobalUnderScore
 */
class DrupalSettingsTest extends DrupalTestCase {

  /**
   * Application root.
   *
   * @var string
   */
  protected $app_root;

  /**
   * Site path.
   *
   * @var string
   */
  protected $site_path;

  /**
   * Array of configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * Array of settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Array of databases.
   *
   * @var array
   */
  protected $databases;

  /**
   * Array of environment variables.
   *
   * @var array
   */
  protected $envVars = [];

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->unsetEnvVars();

    parent::tearDown();
  }

  public function testDefaults() {
    $this->setEnvVars([]);
    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_settings = $this->getDefaultSettings();
    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testAcquiaGeneric() {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => TRUE,
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_settings = $this->getDefaultSettings();

    $default_config['acquia_hosting_settings_autoconnect'] = FALSE;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testLagoonGeneric() {
    $this->setEnvVars([
      'LAGOON' => 1,
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_settings = $this->getDefaultSettings();

    $default_settings['hash_salt'] = hash('sha256', getenv('DREVOPS_MARIADB_HOST'));
    $default_settings['reverse_proxy'] = TRUE;
    $default_settings['trusted_host_patterns'][] = '^example1\.com|example2/com$';

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');

    $this->assertEquals(1, LAGOON_VERSION);
  }

  public function testEnvironmentLocal() {
    $this->setEnvVars([]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_settings = $this->getDefaultSettings();
    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testEnvironmentCi() {
    $this->setEnvVars([
      'CI' => TRUE,
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_settings = $this->getDefaultSettings();

    $default_config['environment_indicator.indicator']['name'] = 'ci';
    $default_config['config_split.config_split.ci']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);

    $default_settings['environment'] = ENVIRONMENT_CI;
    $default_settings['suspend_mail_send'] = TRUE;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testEnvironmentDev() {
    // Use Acquia's settings to set the environment type.
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 'dev',
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_config['acquia_hosting_settings_autoconnect'] = FALSE;
    $default_settings = $this->getDefaultSettings();

    $default_config['environment_indicator.indicator']['name'] = 'dev';
    $default_config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    $default_config['config_split.config_split.dev']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    $default_config['shield.settings']['credentials']['shield']['user'] = 'CHANGE_ME';
    $default_config['shield.settings']['credentials']['shield']['pass'] = 'CHANGE_ME';
    $default_config['shield.settings']['shield_enable'] = TRUE;

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_DEV;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testEnvironmentTest() {
    // Use Acquia's settings to set the environment type.
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 'test',
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_config['acquia_hosting_settings_autoconnect'] = FALSE;
    $default_settings = $this->getDefaultSettings();

    $default_config['environment_indicator.indicator']['name'] = 'test';
    $default_config['environment_indicator.indicator']['bg_color'] = '#fff176';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    $default_config['config_split.config_split.test']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    $default_config['shield.settings']['credentials']['shield']['user'] = 'CHANGE_ME';
    $default_config['shield.settings']['credentials']['shield']['pass'] = 'CHANGE_ME';
    $default_config['shield.settings']['shield_enable'] = TRUE;

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_TEST;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  public function testEnvironmentProd() {
    // Use Acquia's settings to set the environment type.
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 'prod',
    ]);

    $this->requireSettings();

    $default_config = $this->getDefaultConfig();
    $default_config['acquia_hosting_settings_autoconnect'] = FALSE;
    $default_settings = $this->getDefaultSettings();

    $default_config['environment_indicator.indicator']['name'] = 'prod';
    $default_config['environment_indicator.indicator']['bg_color'] = '#ef5350';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    unset($default_config['stage_file_proxy.settings']);
    $default_config['shield.settings']['credentials']['shield']['user'] = 'CHANGE_ME';
    $default_config['shield.settings']['credentials']['shield']['pass'] = 'CHANGE_ME';
    unset($default_config['shield.settings']['shield_enable']);

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_PROD;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  /**
   * @dataProvider dataProviderEnvironment
   */
  public function testEnvironment($vars, $expected_env) {
    $this->setEnvVars($vars);

    $this->requireSettings();

    $this->assertEquals($expected_env, $this->settings['environment']);
  }

  public function dataProviderEnvironment() {
    $this->requireSettings();
    return [
      [[], ENVIRONMENT_LOCAL],

      // Acquia.
      [
        [
          'AH_SITE_ENVIRONMENT' => TRUE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'prod',
        ],
        ENVIRONMENT_PROD,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'test',
        ],
        ENVIRONMENT_TEST,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'dev',
        ],
        ENVIRONMENT_DEV,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'ode1',
        ],
        ENVIRONMENT_DEV,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'nonode1',
        ],
        ENVIRONMENT_LOCAL,
      ],

      // Lagoon.
      [
        [
          'LAGOON' => 1,
        ],
        ENVIRONMENT_LOCAL,
      ],

      [
        [
          'LAGOON' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'production',
        ],
        ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'main',
          'DREVOPS_PRODUCTION_BRANCH' => 'main',
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        ENVIRONMENT_PROD,
      ],

      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'release',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'release/1.2.3',
        ],
        ENVIRONMENT_TEST,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'hotfix',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'hotfix/1.2.3',
        ],
        ENVIRONMENT_TEST,
      ],

      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'DREVOPS_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => FALSE,
          'DREVOPS_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'DREVOPS_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => FALSE,
          'DREVOPS_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'DREVOPS_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => '',
          'DREVOPS_PRODUCTION_BRANCH' => '',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'DREVOPS_PRODUCTION_BRANCH' => 'somebranch',
        ],
        ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        ENVIRONMENT_DEV,
      ],

      // CI.
      [
        [
          'CI' => 1,
        ],
        ENVIRONMENT_CI,
      ],
      [
        [
          'CI' => 1,
          'LAGOON' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        ENVIRONMENT_DEV,
      ],
    ];
  }

  /**
   * @dataProvider dataProviderDatabases
   */
  public function testDatabases($vars, $expected) {
    $this->setEnvVars($vars);

    $this->requireSettings();

    $this->assertEquals($expected, $this->databases);
  }

  public function dataProviderDatabases() {
    return [
      [
        [],
        [
          'default' => [
            'default' => [
              'database' => 'drupal',
              'username' => 'drupal',
              'password' => 'drupal',
              // Special case: this value can vary depending on how
              // settings.generated.php was created - MYSQL_HOST variable could
              // have been set during creation.
              // @see Utilities\composer\DrupalSettings::getDefaultDrupalSettingsContent()
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
          'DREVOPS_MARIADB_DATABASE' => 'test_db_name',
          'DREVOPS_MARIADB_USERNAME' => 'test_db_user',
          'DREVOPS_MARIADB_PASSWORD' => 'test_db_pass',
          'DREVOPS_MARIADB_HOST' => 'test_db_host',
          'DREVOPS_MARIADB_PORT' => 'test_db_port',
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
          'DREVOPS_MARIADB_DATABASE' => 'test_db_name',
          'DREVOPS_MARIADB_USERNAME' => 'test_db_user',
          'DREVOPS_MARIADB_PASSWORD' => 'test_db_pass',
          'DREVOPS_MARIADB_HOST' => 'test_db_host',
          'DREVOPS_MARIADB_PORT' => 'test_db_port',
          'MARIADB_DATABASE' => 'test2_db_name',
          'MARIADB_USERNAME' => 'test2_db_user',
          'MARIADB_PASSWORD' => 'test2_db_pass',
          'MARIADB_HOST' => 'test2_db_host',
          'MARIADB_PORT' => 'test2_db_port',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'test2_db_name',
              'username' => 'test2_db_user',
              'password' => 'test2_db_pass',
              'host' => 'test2_db_host',
              'port' => 'test2_db_port',
              'driver' => 'mysql',
              'prefix' => '',
            ],
          ],
        ],
      ],

      [
        [
          'MARIADB_DATABASE' => 'test_db_name',
          'MARIADB_USERNAME' => 'test_db_user',
          'MARIADB_PASSWORD' => 'test_db_pass',
          'MARIADB_HOST' => 'test_db_host',
          'MARIADB_PORT' => 'test_db_port',
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

  protected function setEnvVars(array $vars) {
    if (empty($vars['CI'])) {
      $vars['CI'] = FALSE;
    }
    if (empty($vars['LAGOON'])) {
      $vars['LAGOON'] = FALSE;
    }
    $vars['TMP'] = '/tmp-test';
    $vars['DRUPAL_SHIELD_USER'] = 'CHANGE_ME';
    $vars['DRUPAL_SHIELD_PASS'] = 'CHANGE_ME';
    $this->envVars = $vars + $this->envVars;
    foreach ($this->envVars as $name => $value) {
      putenv("$name=$value");
    }
  }

  protected function unsetEnvVars() {
    foreach (array_keys($this->envVars) as $name) {
      putenv("$name");
    }
  }

  protected function requireSettings() {
    $app_root = '../../../web';
    $site_path = 'sites/default';
    $config = [];
    $settings = [];
    $databases = [];

    require getcwd() . DIRECTORY_SEPARATOR . $app_root . '/' . $site_path . '/settings.php';

    $this->app_root = $app_root;
    $this->site_path = $site_path;
    $this->config = $config;
    $this->settings = $settings;
    $this->databases = $databases;
  }

  protected function getDefaultConfig() {
    $config = [];

    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = 1;
    $config['system.performance']['js']['preprocess'] = 1;
    $config['system.logging']['error_level'] = 'all';
    $config['shield.settings']['print'] = 'YOURSITE';
    $config['shield.settings']['credentials']['shield']['user'] = 'CHANGE_ME';
    $config['shield.settings']['credentials']['shield']['pass'] = 'CHANGE_ME';
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['stage_file_proxy.settings']['origin'] = 'https://CHANGE_ME:CHANGE_ME@your-site-url.example/';
    $config['stage_file_proxy.settings']['hotlink'] = FALSE;
    $config['environment_indicator.indicator']['name'] = ENVIRONMENT_LOCAL;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['config_split.config_split.local']['status'] = TRUE;

    return $config;
  }

  protected function getDefaultSettings() {
    $settings = [];

    $settings['environment'] = ENVIRONMENT_LOCAL;
    $settings['config_sync_directory'] = '../config/default';
    $settings['hash_salt'] = hash('sha256', 'CHANGE_ME');
    $settings['file_temp_path'] = '/tmp-test';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['entity_update_batch_size'] = 50;
    $settings['config_exclude_modules'] = [];
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['trusted_host_patterns'] = [
      // Local URL.
      '^.+\.docker\.amazee\.io$',
      // URL when accessed from Behat tests.
      '^nginx$',
      '^nginx\-php$',
      // #;< LAGOON
      // Lagoon URL.
      '^.+\.au\.amazee\.io$',
      // #;> LAGOON
    ];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';

    return $settings;
  }

}
