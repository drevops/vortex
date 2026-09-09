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
  public static function dataProviderEnvironmentTypeDetection(): \Iterator {
    // The default environment type is local.
    yield [[], self::ENVIRONMENT_LOCAL];

    // CI.
    yield [
      [
        'CI' => 1,
      ],
      self::ENVIRONMENT_CI,
    ];

    // Container.
    yield [
      [
        'LOCALDEV_URL' => 'https://example-site.docker.amazee.io',
      ],
      self::ENVIRONMENT_LOCAL,
    ];

  }

  /**
   * Test settings without any environment overrides.
   */
  public function testEnvironmentNoOverrides(): void {
    $this->setEnvVars([
      'ENVIRONMENT_TYPE' => self::ENVIRONMENT_SUT,
    ]);

    $this->requireSettingsFile();

    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_SUT);

    $this->assertSettings($settings);
  }

  /**
   * Test environment variable overrides.
   */
  public function testEnvironmentOverrides(): void {
    $this->setEnvVars([
      'ENVIRONMENT_TYPE' => self::ENVIRONMENT_SUT,
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
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
    $config['system.performance']['cache']['page']['max_age'] = 1800;
    $this->assertConfig($config);

    // Verify settings overrides.
    $settings = $this->expectedSettings(self::ENVIRONMENT_SUT);
    $settings['config_sync_directory'] = 'custom_config';
    $settings['file_public_path'] = 'custom_public';
    $settings['file_private_path'] = 'custom_private';
    $settings['file_temp_path'] = 'custom_temp';
    $settings['hash_salt'] = 'custom_hash_salt';
    $settings['maintenance_theme'] = 'custom_theme';

    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Local environment.
   */
  public function testEnvironmentLocal(): void {
    $this->setEnvVars([
      'ENVIRONMENT_TYPE' => self::ENVIRONMENT_LOCAL,
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
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['reroute_email.settings']['enable'] = FALSE;
    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_LOCAL);
    $settings['skip_permissions_hardening'] = TRUE;

    $this->assertSettings($settings);
  }

  /**
   * Test per-environment settings for Local with container provider.
   */
  public function testEnvironmentLocalContainer(): void {
    $this->setEnvVars([
      'LOCALDEV_URL' => 'https://example-site.docker.amazee.io',
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
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['reroute_email.settings']['enable'] = FALSE;
    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_LOCAL);
    $settings['skip_permissions_hardening'] = TRUE;
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^example\\-site\\.docker\\.amazee\\.io$',
      '^nginx$',
    ];

    $this->assertSettings($settings);
  }

  /**
   * Test trusted host patterns for multiple container provider URLs.
   */
  public function testEnvironmentLocalContainerMultipleUrls(): void {
    $this->setEnvVars([
      'LOCALDEV_URL' => 'https://example-site.docker.amazee.io , http://second-site.docker.amazee.io,',
    ]);

    $this->requireSettingsFile();

    $this->assertSettingsContains([
      'trusted_host_patterns' => [
        '^localhost$',
        '^example\-site\.docker\.amazee\.io$',
        '^second\-site\.docker\.amazee\.io$',
        '^nginx$',
      ],
    ]);
  }

  /**
   * Test trusted host patterns for container provider URLs without a scheme.
   */
  public function testEnvironmentLocalContainerSchemelessUrls(): void {
    $this->setEnvVars([
      'LOCALDEV_URL' => 'Example-Site.docker.amazee.io:8080 , second-site.docker.amazee.io/subpath',
    ]);

    $this->requireSettingsFile();

    $this->assertSettingsContains([
      'trusted_host_patterns' => [
        '^localhost$',
        '^example\-site\.docker\.amazee\.io$',
        '^second\-site\.docker\.amazee\.io$',
        '^nginx$',
      ],
    ]);
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
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.logging']['error_level'] = 'all';
    $config['system.mail']['interface']['default'] = 'test_mail_collector';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['reroute_email.settings']['enable'] = FALSE;
    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_CI);
    $settings['skip_permissions_hardening'] = TRUE;

    $this->assertSettings($settings);
  }

  /**
   * Settings that every environment produces.
   *
   * Each test overrides only the entries its environment changes.
   *
   * @param string $environment
   *   The environment type the settings are expected to report.
   *
   * @return array
   *   Array of expected settings.
   */
  protected function expectedSettings(string $environment): array {
    return [
      'auto_create_htaccess' => FALSE,
      'config_exclude_modules' => [
        'devel',
        'generated_content',
        'reroute_email',
        'sdc_devel',
        'testmode',
      ],
      'config_sync_directory' => '../config/default',
      'container_yamls' => [$this->app_root . '/' . $this->site_path . '/services.yml'],
      'entity_update_batch_size' => 50,
      'environment' => $environment,
      'fast404_allow_anon_imagecache' => FALSE,
      'fast404_exts' => '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i',
      'fast404_html' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
      'fast404_path_check' => FALSE,
      'fast404_respect_redirect' => FALSE,
      'fast404_url_whitelisting' => TRUE,
      'fast404_whitelist' => [
        'index.php',
        'rss.xml',
        'cron.php',
        'xmlrpc.php',
      ],
      'file_private_path' => 'sites/default/files/private',
      'file_public_path' => 'sites/default/files',
      'file_scan_ignore_directories' => [
        'node_modules',
        'bower_components',
      ],
      'file_temp_path' => '/tmp',
      'hash_salt' => hash('sha256', getenv('DATABASE_HOST') ?: 'localhost'),
      'maintenance_theme' => 'claro',
      'trusted_host_patterns' => [
        '^localhost$',
      ],
    ];
  }

}
