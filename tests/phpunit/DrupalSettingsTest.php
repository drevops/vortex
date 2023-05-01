<?php

use PHPUnit\Framework\TestCase;

/**
 * Class DrupalSettingsTest.
 *
 * Tests for Drupal settings.
 *
 * @group site:unit
 * @SuppressWarnings(PHPMD)
 *
 * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
 * phpcs:disable Drupal.NamingConventions.ValidGlobal.GlobalUnderScore
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
 */
class DrupalSettingsTest extends TestCase {

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

  /**
   * Test generic settings.
   */
  public function testGeneric() {
    $this->setEnvVars([
      'DREVOPS_CLAMAV_ENABLED' => FALSE,
    ]);
    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();
    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  /**
   * Test Shield config.
   */
  public function testShield() {
    $this->setEnvVars([
      'DRUPAL_SHIELD_USER' => 'test_shield_user',
      'DRUPAL_SHIELD_PASS' => 'test_shield_pass',
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_config['shield.settings']['credentials']['shield']['user'] = 'test_shield_user';
    $default_config['shield.settings']['credentials']['shield']['pass'] = 'test_shield_pass';
    $default_config['stage_file_proxy.settings']['origin'] = 'https://test_shield_user:test_shield_pass@your-site-url.example/';

    $this->assertEquals($default_config, $this->config, 'Config');
  }

  /**
   * Test Shield partial config.
   */
  public function testShieldPartial() {
    $this->setEnvVars([
      'DRUPAL_SHIELD_USER' => 'test_shield_user',
      'DRUPAL_SHIELD_PASS' => '',
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();

    $this->assertEquals($default_config, $this->config, 'Config');
  }

  // phpcs:ignore #;< CLAMAV
  /**
   * Test ClamAV configs in Daemon mode with defaults.
   */
  public function testClamavDaemonDefaults() {
    $this->setEnvVars([
      'DREVOPS_CLAMAV_ENABLED' => TRUE,
      'CLAMAV_MODE' => 'daemon',
    ]);
    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();

    $default_config['clamav.settings']['scan_mode'] = 0;
    $default_config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'clamav';
    $default_config['clamav.settings']['mode_daemon_tcpip']['port'] = 3310;

    $this->assertEquals($default_config, $this->config, 'Config');
  }

  /**
   * Test ClamAV configs in Daemon mode with defaults.
   */
  public function testClamavDaemonCustom() {
    $this->setEnvVars([
      'DREVOPS_CLAMAV_ENABLED' => TRUE,
      'CLAMAV_MODE' => 'daemon',
      'CLAMAV_HOST' => 'custom_clamav_host',
      'CLAMAV_PORT' => 3333,
    ]);
    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();

    $default_config['clamav.settings']['scan_mode'] = 0;
    $default_config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'custom_clamav_host';
    $default_config['clamav.settings']['mode_daemon_tcpip']['port'] = 3333;

    $this->assertEquals($default_config, $this->config, 'Config');
  }

  /**
   * Test ClamAV configs in Executable mode.
   */
  public function testClamavExecutable() {
    $this->setEnvVars([
      'DREVOPS_CLAMAV_ENABLED' => TRUE,
      'CLAMAV_HOST' => 'custom_clamav_host',
      'CLAMAV_PORT' => 3333,
    ]);
    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();

    $default_config['clamav.settings']['scan_mode'] = 1;
    $default_config['clamav.settings']['executable_path'] = '/usr/bin/clamscan';

    $this->assertEquals($default_config, $this->config, 'Config');
  }
  // phpcs:ignore #;> CLAMAV
  // phpcs:ignore #;< REDIS
  /**
   * Test Redis settings.
   */
  public function testRedis() {
    $this->setEnvVars([
      'DREVOPS_REDIS_ENABLED' => 1,
      'REDIS_HOST' => 'redis_host',
      'REDIS_SERVICE_PORT' => 1234,
    ]);
    $this->requireSettingsFile();

    $default_settings = $this->getGenericSettings();

    $default_settings['redis.connection']['interface'] = 'PhpRedis';
    $default_settings['redis.connection']['host'] = 'redis_host';
    $default_settings['redis.connection']['port'] = 1234;
    $default_settings['cache']['default'] = 'cache.backend.redis';

    $this->assertArrayHasKey('bootstrap_container_definition', $this->settings);
    unset($this->settings['bootstrap_container_definition']);

    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }
  // phpcs:ignore #;> REDIS
  // phpcs:ignore #;< ACQUIA
  /**
   * Test Acquia-specific settings.
   */
  public function testAcquia() {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => TRUE,
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_config['acquia_hosting_settings_autoconnect'] = FALSE;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }
  // phpcs:ignore #;> ACQUIA
  // phpcs:ignore #;< LAGOON
  /**
   * Test Lagoon-specific settings.
   */
  public function testLagoon() {
    $this->setEnvVars([
      'LAGOON' => 1,
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
      'LAGOON_PROJECT' => 'test_project',
      'LAGOON_GIT_SAFE_BRANCH' => 'test_branch',
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_settings['hash_salt'] = hash('sha256', getenv('DREVOPS_MARIADB_HOST'));
    $default_settings['reverse_proxy'] = TRUE;
    $default_settings['trusted_host_patterns'][] = '^example1\.com|example2/com$';
    $default_settings['cache_prefix']['default'] = 'test_project_test_branch';

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');

    $this->assertEquals(1, LAGOON_VERSION);
  }
  // phpcs:ignore #;> LAGOON
  /**
   * Test per-environment overrides for LOCAL environment.
   */
  public function testEnvironmentLocal() {
    $this->setEnvVars([]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();
    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  /**
   * Test per-environment overrides for CI environment.
   */
  public function testEnvironmentCi() {
    $this->setEnvVars([
      'CI' => TRUE,
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_config['environment_indicator.indicator']['name'] = 'ci';
    $default_config['config_split.config_split.ci']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);

    $default_config['automated_cron.settings']['interval'] = 0;

    $default_settings['environment'] = ENVIRONMENT_CI;
    $default_settings['suspend_mail_send'] = TRUE;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }

  // phpcs:ignore #;< ACQUIA
  /**
   * Test per-environment overrides for DEV environment.
   */
  public function testEnvironmentDev() {
    $this->setEnvVars([
      'DREVOPS_ENVIRONMENT_TYPE' => ENVIRONMENT_DEV,
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_config['environment_indicator.indicator']['name'] = 'dev';
    $default_config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    $default_config['config_split.config_split.dev']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    $default_config['shield.settings']['shield_enable'] = TRUE;

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_DEV;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }
  // phpcs:ignore #;> ACQUIA
  // phpcs:ignore #;< ACQUIA
  /**
   * Test per-environment overrides for TEST environment.
   */
  public function testEnvironmentTest() {
    // Use Acquia's settings to set the environment type.
    $this->setEnvVars([
      'DREVOPS_ENVIRONMENT_TYPE' => ENVIRONMENT_TEST,
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_config['environment_indicator.indicator']['name'] = 'test';
    $default_config['environment_indicator.indicator']['bg_color'] = '#fff176';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    $default_config['config_split.config_split.test']['status'] = TRUE;
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    $default_config['shield.settings']['shield_enable'] = TRUE;

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_TEST;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }
  // phpcs:ignore #;> ACQUIA
  // phpcs:ignore #;< ACQUIA
  /**
   * Test per-environment overrides for PROD environment.
   */
  public function testEnvironmentProd() {
    $this->setEnvVars([
      'DREVOPS_ENVIRONMENT_TYPE' => ENVIRONMENT_PROD,
    ]);

    $this->requireSettingsFile();

    $default_config = $this->getGenericConfig();
    $default_settings = $this->getGenericSettings();

    $default_config['environment_indicator.indicator']['name'] = 'prod';
    $default_config['environment_indicator.indicator']['bg_color'] = '#ef5350';
    $default_config['environment_indicator.indicator']['fg_color'] = '#000000';
    unset($default_config['config_split.config_split.local']);
    unset($default_config['system.logging']);
    unset($default_config['stage_file_proxy.settings']);
    unset($default_config['shield.settings']['shield_enable']);

    unset($default_settings['skip_permissions_hardening']);
    $default_settings['environment'] = ENVIRONMENT_PROD;

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');
  }
  // phpcs:ignore #;> ACQUIA
  /**
   * Test the resulting environment based on the provider's configuration.
   *
   * @dataProvider dataProviderEnvironment
   */
  public function testEnvironment($vars, $expected_env) {
    $this->setEnvVars($vars);

    $this->requireSettingsFile();

    $this->assertEquals($expected_env, $this->settings['environment']);
  }

  /**
   * Data provider for testing of the resulting environment.
   */
  public function dataProviderEnvironment() {
    $this->requireSettingsFile();

    return [
      [[], ENVIRONMENT_LOCAL],
      // #;< ACQUIA
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
      // phpcs:ignore #;> ACQUIA
      // phpcs:ignore #;< LAGOON
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
      // phpcs:ignore #;> LAGOON
      // CI.
      [
        [
          'CI' => 1,
        ],
        ENVIRONMENT_CI,
      ],
      // phpcs:ignore #;< LAGOON
      [
        [
          'CI' => 1,
          'LAGOON' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        ENVIRONMENT_DEV,
      ],
      // phpcs:ignore #;> LAGOON
    ];
  }

  /**
   * Test resulting database settings based on environment variables.
   *
   * @dataProvider dataProviderDatabases
   */
  public function testDatabases($vars, $expected) {
    $this->setEnvVars($vars);

    $this->requireSettingsFile();

    $this->assertEquals($expected, $this->databases);
  }

  /**
   * Data provider for resulting database settings.
   */
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
              'host' => 'mariadb',
              'port' => '3306',
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

  /**
   * Set environment variables.
   *
   * @param array $vars
   *   Array of environment variables.
   */
  protected function setEnvVars(array $vars): void {
    if (!isset($vars['CI'])) {
      $vars['CI'] = FALSE;
    }
    if (!isset($vars['LAGOON'])) {
      $vars['LAGOON'] = FALSE;
    }
    $vars['TMP'] = '/tmp-test';

    $this->envVars = $vars + $this->envVars;

    foreach ($this->envVars as $name => $value) {
      putenv("$name=$value");
    }
  }

  /**
   * Set environment variables.
   */
  protected function unsetEnvVars(): void {
    foreach (array_keys($this->envVars) as $name) {
      putenv("$name");
    }
  }

  /**
   * Require settings file.
   */
  protected function requireSettingsFile(): void {
    $app_root = './web';
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

  /**
   * Get generic configurations.
   *
   * Generic configurations are independent of environment or hosting provider.
   *
   * @return array
   *   Array of configurations.
   */
  protected function getGenericConfig(): array {
    $config = [];

    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = 1;
    $config['system.performance']['js']['preprocess'] = 1;
    $config['system.logging']['error_level'] = 'all';
    $config['shield.settings']['print'] = 'YOURSITE';
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['stage_file_proxy.settings']['origin'] = 'https://your-site-url.example/';
    $config['stage_file_proxy.settings']['hotlink'] = FALSE;
    $config['environment_indicator.indicator']['name'] = ENVIRONMENT_LOCAL;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['config_split.config_split.local']['status'] = TRUE;

    return $config;
  }

  /**
   * Get generic settings.
   *
   * Generic settings are independent of environment or hosting provider.
   *
   * @return array
   *   Array of settings.
   */
  protected function getGenericSettings(): array {
    $settings = [];

    $settings['environment'] = ENVIRONMENT_LOCAL;
    $settings['config_sync_directory'] = '../config/default';
    $settings['hash_salt'] = hash('sha256', getenv('MARIADB_HOST') ?: (getenv('DREVOPS_MARIADB_HOST') ?: 'localhost'));
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
      // #;< LAGOON
      '^nginx\-php$',
      // Lagoon URL.
      '^.+\.au\.amazee\.io$',
      // #;> LAGOON
    ];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';

    return $settings;
  }

}
