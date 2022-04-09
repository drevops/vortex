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

    $default_settings['hash_salt'] = hash('sha256', getenv('MARIADB_HOST'));
    $default_settings['reverse_proxy'] = TRUE;
    $default_settings['trusted_host_patterns'][] = '^example1\.com|example2/com$';

    $this->assertEquals($default_config, $this->config, 'Config');
    $this->assertEquals($default_settings, $this->settings, 'Settings');

    $this->assertEquals(1, LAGOON_VERSION);
  }

  public function testEnvironmentLocal() {
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
    $default_config['shield.settings']['credentials']['shield']['user'] = '';
    $default_config['shield.settings']['credentials']['shield']['pass'] = '';

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
          'LAGOON_GIT_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => FALSE,
          'LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => FALSE,
          'LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => '',
          'LAGOON_PRODUCTION_BRANCH' => '',
        ],
        ENVIRONMENT_LOCAL,
      ],
      [
        [
          'LAGOON' => 1,
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'LAGOON_PRODUCTION_BRANCH' => 'somebranch',
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
          'MARIADB_DATABASE' => 'db_name',
          'MARIADB_USERNAME' => 'db_user',
          'MARIADB_PASSWORD' => 'db_pass',
          'MARIADB_HOST' => 'db_host',
          'MARIADB_PORT' => 'db_port',
        ],
        [
          'default' => [
            'default' => [
              'database' => 'db_name',
              'username' => 'db_user',
              'password' => 'db_pass',
              'host' => 'db_host',
              'port' => 'db_port',
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
    $app_root = '../../../docroot';
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
    $config['shield.settings']['credentials']['shield']['user'] = '';
    $config['shield.settings']['credentials']['shield']['pass'] = '';
    $config['stage_file_proxy.settings']['origin'] = 'http://your-site-url/';
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
    $settings['fast404_exts'] = '/^(?!robots).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
    $settings['fast404_allow_anon_imagecache'] = TRUE;
    $settings['fast404_whitelist'] = [
      'index.php',
      'rss.xml',
      'install.php',
      'cron.php',
      'update.php',
      'xmlrpc.php',
    ];
    $settings['fast404_string_whitelisting'] = ['/advagg_'];
    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
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
      // Lagoon URL.
      '^.+\.au\.amazee\.io$',
      // #;> LAGOON
    ];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';

    return $settings;
  }

}
