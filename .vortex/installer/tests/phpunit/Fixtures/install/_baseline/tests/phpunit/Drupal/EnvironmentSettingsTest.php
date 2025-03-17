<?php

declare(strict_types=1);

namespace Drupal;

/**
 * Class EnvironmentSettingsTest.
 *
 * Settings and configs within tests are sorted alphabetically.
 *
 * The main purpose of these tests is to ensure that the settings and configs
 * appear in every environment as expected.
 *
 * @group drupal_settings
 *
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.AfterLast
 * phpcs:disable Drupal.Classes.ClassDeclaration.CloseBraceAfterBody
 */
class EnvironmentSettingsTest extends SettingsTestCase {

  /**
   * Test the resulting environment based on the provider's configuration.
   *
   * @dataProvider dataProviderEnvironmentTypeResolution
   */
  public function testEnvironmentTypeResolution(array $vars, string $expected_env): void {
    $this->setEnvVars($vars);

    $this->requireSettingsFile();

    $this->assertEquals($expected_env, $this->settings['environment'], print_r($vars, TRUE));
  }

  /**
   * Data provider for testing of the resulting environment.
   */
  public static function dataProviderEnvironmentTypeResolution(): array {
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

    ];
  }

  /**
   * Test generic settings without any environment overrides.
   */
  public function testEnvironmentGeneric(): void {
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
    $config['system.performance']['css']['preprocess'] = 1;
    $config['system.performance']['js']['preprocess'] = 1;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_SUT;
    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['trusted_host_patterns'] = [
      '^.+\.docker\.amazee\.io$',
      '^nginx$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for LOCAL environment.
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
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = 1;
    $config['system.performance']['js']['preprocess'] = 1;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_LOCAL;
    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^.+\.docker\.amazee\.io$',
      '^nginx$',
    ];
    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for CI environment.
   */
  public function testEnvironmentCi(): void {
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
    $config['system.performance']['css']['preprocess'] = 1;
    $config['system.performance']['js']['preprocess'] = 1;
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $this->assertConfig($config);

    $settings['config_exclude_modules'] = [];
    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
    $settings['entity_update_batch_size'] = 50;
    $settings['environment'] = static::ENVIRONMENT_CI;
    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
    $settings['file_scan_ignore_directories'] = [
      'node_modules',
      'bower_components',
    ];
    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['suspend_mail_send'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^.+\.docker\.amazee\.io$',
      '^nginx$',
    ];
    $this->assertSettings($settings);
  }

}
