<?php

declare(strict_types=1);

namespace Drupal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class EnvironmentSettingsTest.
 *
 * Settings and configs within tests are sorted alphabetically.
 *
 * The main purpose of these tests is to ensure that the settings and configs
 * appear in every environment as expected.
 *
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.AfterLast
 * phpcs:disable Drupal.Classes.ClassDeclaration.CloseBraceAfterBody
 */
#[Group('drupal_settings')]
class EnvironmentSettingsTest extends SettingsTestCase {

  /**
   * Test the detection of the resulting environment type.
   */
  #[DataProvider('dataProviderEnvironmentTypeDetection')]
  public function testEnvironmentTypeDetection(array $vars, string $expected_env): void {
    $this->setEnvVars($vars);

    $this->requireSettingsFile();

    $this->assertEquals($expected_env, $this->settings['environment'], print_r($vars, TRUE));
  }

  /**
   * Data provider for testing environment type detection.
   */
  public static function dataProviderEnvironmentTypeDetection(): array {
    return [
      // By default, the default environment type is local.
      [[], static::ENVIRONMENT_LOCAL],

      // CI.
      [
        [
          'CI' => 1,
        ],
        static::ENVIRONMENT_CI,
      ],

      // Container.
      [
        [
          'VORTEX_LOCALDEV_URL' => 'https://example-site.docker.amazee.io',
        ],
        static::ENVIRONMENT_LOCAL,
      ],

      // #;< SETTINGS_PROVIDER_ACQUIA
      // Acquia.
      [
        [
          'AH_SITE_ENVIRONMENT' => TRUE,
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'prod',
        ],
        static::ENVIRONMENT_PROD,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'stage',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'test',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'dev',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'ode1',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'AH_SITE_ENVIRONMENT' => 'nonode1',
        ],
        static::ENVIRONMENT_DEV,
      ],
      // phpcs:ignore #;> SETTINGS_PROVIDER_ACQUIA

      // phpcs:ignore #;< SETTINGS_PROVIDER_LAGOON
      // Lagoon.
      [
        [
          'LAGOON_KUBERNETES' => 1,
        ],
        static::ENVIRONMENT_DEV,
      ],

      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'production',
        ],
        static::ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'main',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'main',
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        static::ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'main',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'master',
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'master',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'master',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
          'LAGOON_ENVIRONMENT_TYPE' => 'production',
        ],
        static::ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'main',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_GIT_BRANCH' => 'main',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
          'LAGOON_ENVIRONMENT_TYPE' => 'production',
        ],
        static::ENVIRONMENT_PROD,
      ],

      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'release',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'release/1.2.3',
        ],
        static::ENVIRONMENT_STAGE,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'hotfix',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'hotfix/1.2.3',
        ],
        static::ENVIRONMENT_STAGE,
      ],

      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => FALSE,
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => FALSE,
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => FALSE,
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'somebranch',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => '',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => '',
        ],
        static::ENVIRONMENT_DEV,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
          'LAGOON_GIT_BRANCH' => 'mainbranch',
          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'mainbranch',
        ],
        static::ENVIRONMENT_PROD,
      ],
      [
        [
          'LAGOON_KUBERNETES' => 1,
          'LAGOON_ENVIRONMENT_TYPE' => 'development',
        ],
        static::ENVIRONMENT_DEV,
      ],
      // phpcs:ignore #;> SETTINGS_PROVIDER_LAGOON
    ];
  }

  /**
   * Test settings without any environment overrides.
   */
  public function testEnvironmentNoOverrides(): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => static::ENVIRONMENT_SUT,
    ]);

    $this->requireSettingsFile();

    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_SUT;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_SUT;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test environment variable overrides.
   */
  public function testEnvironmentOverrides(): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => static::ENVIRONMENT_SUT,
      // Database configuration.
      'DATABASE_NAME' => 'custom_db',
      'DATABASE_USERNAME' => 'custom_user',
      'DATABASE_PASSWORD' => 'custom_pass',
      'DATABASE_HOST' => 'custom_host',
      'DATABASE_PORT' => '5432',
      'DATABASE_CHARSET' => 'utf8',
      'DATABASE_COLLATION' => 'utf8_general_ci',
      // General Drupal settings.
      'DRUPAL_CONFIG_PATH' => 'custom_config',
      'DRUPAL_PUBLIC_FILES' => 'custom_public',
      'DRUPAL_PRIVATE_FILES' => 'custom_private',
      'DRUPAL_TEMPORARY_FILES' => 'custom_temp',
      'DRUPAL_HASH_SALT' => 'custom_hash_salt',
      'DRUPAL_TIMEZONE' => 'Australia/Melbourne',
      'DRUPAL_MAINTENANCE_THEME' => 'custom_theme',
      // Performance settings.
      'DRUPAL_CACHE_PAGE_MAX_AGE' => '1800',
    ]);

    $this->requireSettingsFile();

    // Verify database settings.
    $databases['default']['default']['database'] = 'custom_db';
    $databases['default']['default']['username'] = 'custom_user';
    $databases['default']['default']['password'] = 'custom_pass';
    $databases['default']['default']['host'] = 'custom_host';
    $databases['default']['default']['port'] = '5432';
    $databases['default']['default']['charset'] = 'utf8';
    $databases['default']['default']['collation'] = 'utf8_general_ci';
    $databases['default']['default']['driver'] = 'mysql';
    $databases['default']['default']['prefix'] = '';
    $this->assertEquals($databases, $this->databases);

    // Verify key config overrides.
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_SUT;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 1800;
    $this->assertConfig($config);

    // Verify settings overrides.
    $settings['config_exclude_modules'] = [];
    $settings['config_sync_directory'] = 'custom_config';
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_SUT;
    $settings['file_public_path'] = 'custom_public';
    $settings['file_private_path'] = 'custom_private';
    $settings['file_temp_path'] = 'custom_temp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = 'custom_hash_salt';
    $settings['maintenance_theme'] = 'custom_theme';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];

    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Local environment.
   */
  public function testEnvironmentLocal(): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => static::ENVIRONMENT_LOCAL,
    ]);

    $this->requireSettingsFile();

    $config['automated_cron.settings']['interval'] = 0;
    $config['config_split.config_split.local']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_LOCAL;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_LOCAL;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

  // phpcs:ignore #;< SETTINGS_PROVIDER_CONTAINER
  /**
   * Test per-environment settings for Local with container provider.
   */
  public function testEnvironmentLocalContainer(): void {
    $this->setEnvVars([
      'VORTEX_LOCALDEV_URL' => 'https://example-site.docker.amazee.io',
    ]);

    $this->requireSettingsFile();

    $config['automated_cron.settings']['interval'] = 0;
    $config['config_split.config_split.local']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_LOCAL;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_LOCAL;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^example-site\.docker\.amazee\.io$',
      '^nginx$',
    ];
    $this->assertSettings($settings);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_CONTAINER

  // phpcs:ignore #;< SETTINGS_PROVIDER_CIRCLECI
  /**
   * Test per-environment settings for CircleCI.
   */
  public function testEnvironmentCircleCi(): void {
    $this->setEnvVars([
      'CI' => TRUE,
    ]);

    $this->requireSettingsFile();

    $config['automated_cron.settings']['interval'] = 0;
    $config['config_split.config_split.ci']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_CI;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_CI;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['suspend_mail_send'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_CIRCLECI

  // phpcs:ignore #;< SETTINGS_PROVIDER_GHA
  /**
   * Test per-environment settings for GitHub Actions.
   */
  public function testEnvironmentGha(): void {
    $this->setEnvVars([
      'CI' => TRUE,
    ]);

    $this->requireSettingsFile();

    $config['automated_cron.settings']['interval'] = 0;
    $config['config_split.config_split.ci']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_CI;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_CI;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['suspend_mail_send'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_GHA

  // phpcs:ignore #;< SETTINGS_PROVIDER_ACQUIA
  /**
   * Test per-environment settings for dynamic environment.
   */
  public function testEnvironmentAcquiaDynamic(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 1,
    ]);

    $this->requireSettingsFile();

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_DEV;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Dev environment.
   */
  public function testEnvironmentAcquiaDev(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 1,
    ]);

    $this->requireSettingsFile();

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_DEV;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Test environment.
   */
  public function testEnvironmentAcquiaStage(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 'stage',
    ]);

    $this->requireSettingsFile();

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['config_split.config_split.stage']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#fff176';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_STAGE;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_STAGE;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Prod environment.
   */
  public function testEnvironmentAcquiaProd(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 'prod',
    ]);

    $this->requireSettingsFile();

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['environment_indicator.indicator']['bg_color'] = '#ef5350';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_PROD;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = TRUE;
    $config['system.performance']['js']['preprocess'] = TRUE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_PROD;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_ACQUIA
  // phpcs:ignore #;< SETTINGS_PROVIDER_LAGOON
  /**
   * Test per-environment settings for preview environment.
   */
  public function testEnvironmentLagoonPreview(): void {
    $this->setEnvVars([
      'LAGOON_KUBERNETES' => 1,
      'LAGOON_ENVIRONMENT_TYPE' => 'development',
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
      'LAGOON_PROJECT' => 'test_project',
      'LAGOON_GIT_BRANCH' => 'test_branch',
      'LAGOON_GIT_SAFE_BRANCH' => 'test_branch',
    ]);

    $this->requireSettingsFile();

    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['cache_prefix']['default'] = 'test_project_test_branch';
    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_DEV;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\-php$',
      '^.+\.au\.amazee\.io$',
      '^example1\.com|example2/com$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Dev environment.
   */
  public function testEnvironmentLagoonDev(): void {
    $this->setEnvVars([
      'LAGOON_KUBERNETES' => 1,
      'LAGOON_ENVIRONMENT_TYPE' => 'development',
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
      'LAGOON_PROJECT' => 'test_project',
      'LAGOON_GIT_BRANCH' => 'develop',
      'LAGOON_GIT_SAFE_BRANCH' => 'develop',
    ]);

    $this->requireSettingsFile();

    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['cache_prefix']['default'] = 'test_project_develop';
    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_DEV;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\-php$',
      '^.+\.au\.amazee\.io$',
      '^example1\.com|example2/com$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Test environment.
   */
  public function testEnvironmentLagoonTest(): void {
    $this->setEnvVars([
      'LAGOON_KUBERNETES' => 1,
      'LAGOON_ENVIRONMENT_TYPE' => 'development',
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
      'LAGOON_PROJECT' => 'test_project',
      'LAGOON_GIT_BRANCH' => 'master',
      'LAGOON_GIT_SAFE_BRANCH' => 'master',
    ]);

    $this->requireSettingsFile();

    $config['config_split.config_split.stage']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#fff176';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_STAGE;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['cache_prefix']['default'] = 'test_project_master';
    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_STAGE;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\-php$',
      '^.+\.au\.amazee\.io$',
      '^example1\.com|example2/com$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Prod environment.
   */
  public function testEnvironmentLagoonProd(): void {
    $this->setEnvVars([
      'LAGOON_KUBERNETES' => 1,
      'LAGOON_ENVIRONMENT_TYPE' => 'production',
      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
      'LAGOON_PROJECT' => 'test_project',
      'LAGOON_GIT_BRANCH' => 'production',
      'LAGOON_GIT_SAFE_BRANCH' => 'production',
      'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'production',
    ]);

    $this->requireSettingsFile();

    $config['environment_indicator.indicator']['bg_color'] = '#ef5350';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_PROD;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = TRUE;
    $config['system.performance']['js']['preprocess'] = TRUE;
    $this->assertConfig($config);

    $settings['cache_prefix']['default'] = 'test_project_production';
    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_PROD;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\-php$',
      '^.+\.au\.amazee\.io$',
      '^example1\.com|example2/com$',
    ];
    $this->assertSettings($settings);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_LAGOON

}
