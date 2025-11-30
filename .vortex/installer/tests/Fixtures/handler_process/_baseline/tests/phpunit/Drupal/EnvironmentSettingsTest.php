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
      [[], self::ENVIRONMENT_LOCAL],

      // CI.
      [
        [
          'CI' => 1,
        ],
        self::ENVIRONMENT_CI,
      ],

      // Container.
      [
        [
          'VORTEX_LOCALDEV_URL' => 'https://example-site.docker.amazee.io',
        ],
        self::ENVIRONMENT_LOCAL,
      ],

    ];
  }

  /**
   * Test settings without any environment overrides.
   */
  public function testEnvironmentNoOverrides(): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => self::ENVIRONMENT_SUT,
    ]);

    $this->requireSettingsFile();

    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = self::ENVIRONMENT_SUT;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['config_sync_directory'] = '../config/default';
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
      'DRUPAL_ENVIRONMENT' => self::ENVIRONMENT_SUT,
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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['system.performance']['cache']['page']['max_age'] = 1800;
    $this->assertConfig($config);

    // Verify settings overrides.
    $settings['config_exclude_modules'] = [];
    $settings['config_sync_directory'] = 'custom_config';
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = self::ENVIRONMENT_SUT;
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
      'DRUPAL_ENVIRONMENT' => self::ENVIRONMENT_LOCAL,
    ]);

    $this->requireSettingsFile();

    $config['automated_cron.settings']['interval'] = 0;
    $config['config_split.config_split.local']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = self::ENVIRONMENT_LOCAL;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['config_sync_directory'] = '../config/default';
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = self::ENVIRONMENT_LOCAL;
    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_private_path'] = 'sites/default/files/private';
    $settings['file_temp_path'] = '/tmp';
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['config_sync_directory'] = '../config/default';
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['maintenance_theme'] = 'claro';
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^example-site\.docker\.amazee\.io$',
      '^nginx$',
    ];
    $this->assertSettings($settings);
  }

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = self::ENVIRONMENT_CI;
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
    $settings['config_sync_directory'] = '../config/default';
    $settings['suspend_mail_send'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
    ];
    $this->assertSettings($settings);
  }

}
