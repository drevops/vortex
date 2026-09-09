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

  // phpcs:ignore #;< SETTINGS_PROVIDER_ACQUIA

  /**
   * Path to the Acquia settings file fixture.
   */
  protected ?string $acquiaSettingsFixture = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!is_null($this->acquiaSettingsFixture) && file_exists($this->acquiaSettingsFixture)) {
      unlink($this->acquiaSettingsFixture);
    }

    parent::tearDown();
  }

  // phpcs:ignore #;> SETTINGS_PROVIDER_ACQUIA

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

    // phpcs:ignore #;< SETTINGS_PROVIDER_ACQUIA
    // Acquia.
    yield [
      [
        'AH_SITE_ENVIRONMENT' => TRUE,
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'prod',
      ],
      self::ENVIRONMENT_PROD,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'stage',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'test',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'dev',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'ode1',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'AH_SITE_ENVIRONMENT' => 'nonode1',
      ],
      self::ENVIRONMENT_DEV,
    ];
    // phpcs:ignore #;> SETTINGS_PROVIDER_ACQUIA

    // phpcs:ignore #;< SETTINGS_PROVIDER_LAGOON
    // Lagoon.
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
      ],
      self::ENVIRONMENT_DEV,
    ];

    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'production',
      ],
      self::ENVIRONMENT_PROD,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'main',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'main',
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
      ],
      self::ENVIRONMENT_PROD,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'main',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'master',
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'master',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'master',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        'LAGOON_ENVIRONMENT_TYPE' => 'production',
      ],
      self::ENVIRONMENT_PROD,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'main',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_GIT_BRANCH' => 'main',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
        'LAGOON_ENVIRONMENT_TYPE' => 'production',
      ],
      self::ENVIRONMENT_PROD,
    ];

    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'release',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'release/1.2.3',
      ],
      self::ENVIRONMENT_STAGE,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'hotfix',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'hotfix/1.2.3',
      ],
      self::ENVIRONMENT_STAGE,
    ];

    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => FALSE,
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => FALSE,
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'somebranch',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => FALSE,
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'somebranch',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => '',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => '',
      ],
      self::ENVIRONMENT_DEV,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
        'LAGOON_GIT_BRANCH' => 'mainbranch',
        'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'mainbranch',
      ],
      self::ENVIRONMENT_PROD,
    ];
    yield [
      [
        'LAGOON_KUBERNETES' => 1,
        'LAGOON_ENVIRONMENT_TYPE' => 'development',
      ],
      self::ENVIRONMENT_DEV,
    ];
    // phpcs:ignore #;> SETTINGS_PROVIDER_LAGOON
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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
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
    // phpcs:ignore #;< MIGRATION
    $databases['migrate']['default']['database'] = 'drupal';
    $databases['migrate']['default']['username'] = 'drupal';
    $databases['migrate']['default']['password'] = 'drupal';
    $databases['migrate']['default']['host'] = 'localhost';
    $databases['migrate']['default']['port'] = '';
    $databases['migrate']['default']['prefix'] = '';
    $databases['migrate']['default']['driver'] = 'mysql';
    // phpcs:ignore #;> MIGRATION
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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_LOCAL);
    $settings['skip_permissions_hardening'] = TRUE;

    $this->assertSettings($settings);
  }

  // phpcs:ignore #;< SETTINGS_PROVIDER_CONTAINER
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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_CI);
    $settings['skip_permissions_hardening'] = TRUE;

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
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_CI);
    $settings['skip_permissions_hardening'] = TRUE;

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['auto_create_htaccess'] = TRUE;

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['auto_create_htaccess'] = TRUE;

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_STAGE;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_STAGE);
    $settings['auto_create_htaccess'] = TRUE;

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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_PROD;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['reroute_email.settings']['enable'] = FALSE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = TRUE;
    $config['system.performance']['js']['preprocess'] = TRUE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_PROD);
    $settings['auto_create_htaccess'] = TRUE;

    $this->assertSettings($settings);
  }

  /**
   * Test Acquia config_sync_directory override with DRUPAL_CONFIG_PATH.
   */
  public function testEnvironmentAcquiaConfigPathOverride(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 1,
      'DRUPAL_CONFIG_PATH' => 'custom_acquia_config',
    ]);

    $this->requireSettingsFile();

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['auto_create_htaccess'] = TRUE;
    $settings['config_sync_directory'] = 'custom_acquia_config';

    $this->assertSettings($settings);
  }

  /**
   * Test Acquia config_sync_directory fallback to config_vcs_directory.
   *
   * When DRUPAL_CONFIG_PATH is not set but config_vcs_directory is provided
   * by the Acquia-included settings file, config_sync_directory should fall
   * back to config_vcs_directory.
   */
  public function testEnvironmentAcquiaConfigVcsDirectoryFallback(): void {
    $this->setEnvVars([
      'AH_SITE_ENVIRONMENT' => 1,
    ]);

    // Pre-seed config_vcs_directory to simulate the value set by the
    // Acquia-included settings file from /var/www/site-php/.
    $this->requireSettingsFile([
      'config_vcs_directory' => '/var/www/site-php/mysite/config',
    ]);

    $config['acquia_hosting_settings_autoconnect'] = FALSE;
    $config['config_split.config_split.dev']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['auto_create_htaccess'] = TRUE;
    $settings['config_sync_directory'] = '/var/www/site-php/mysite/config';
    $settings['config_vcs_directory'] = '/var/www/site-php/mysite/config';

    $this->assertSettings($settings);
  }

  /**
   * Test the temporary file path resolution on Acquia.
   */
  #[DataProvider('dataProviderEnvironmentAcquiaTempPath')]
  public function testEnvironmentAcquiaTempPath(array $vars, string $expected_path): void {
    $this->acquiaSettingsFixture = $this->createAcquiaSettingsFixture();

    $this->setEnvVars($vars + ['DRUPAL_ACQUIA_SETTINGS_FILE' => $this->acquiaSettingsFixture]);

    $this->requireSettingsFile();

    $this->assertSettingsContains(['file_temp_path' => $expected_path]);
  }

  /**
   * Data provider for testEnvironmentAcquiaTempPath().
   */
  public static function dataProviderEnvironmentAcquiaTempPath(): \Iterator {
    yield 'default' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite'],
      '/tmp',
    ];

    yield 'shared mount' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '1'],
      '/mnt/gfs/mysite.dev/tmp',
    ];

    yield 'shared mount without a site group' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'DRUPAL_TMP_PATH_IS_SHARED' => '1'],
      '/tmp',
    ];

    yield 'shared mount variable set to an empty value' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => ''],
      '/tmp',
    ];

    yield 'shared mount variable set to zero' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '0'],
      '/tmp',
    ];

    yield 'shared mount variable set to a non-numeric truthy value' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => 'true'],
      '/tmp',
    ];

    yield 'explicit override' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH' => '/custom/tmp'],
      '/custom/tmp',
    ];

    yield 'explicit override wins over the shared mount' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '1', 'DRUPAL_TMP_PATH' => '/custom/tmp'],
      '/custom/tmp',
    ];

    yield 'explicit override set to an empty value' => [
      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH' => ''],
      '/tmp',
    ];
  }

  /**
   * Create an Acquia settings file fixture.
   *
   * The settings file is required when a site group is set, so the shared
   * mount branch can only be reached through a file that exists.
   *
   * @return string
   *   Path to the settings file fixture.
   */
  protected function createAcquiaSettingsFixture(): string {
    $dir = getcwd() . '/.artifacts/tmp';

    if (!is_dir($dir)) {
      mkdir($dir, 0777, TRUE);
    }

    $file = $dir . '/' . uniqid('acquia-settings-') . '.inc';
    file_put_contents($file, "<?php\n");

    return $file;
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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['cache_prefix']['default'] = 'test_project_test_branch';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\\-php$',
      '^.+\\.amazee\\.io$',
      '^example1\\.com$',
      '^example2$',
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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
    $settings['cache_prefix']['default'] = 'test_project_develop';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\\-php$',
      '^.+\\.amazee\\.io$',
      '^example1\\.com$',
      '^example2$',
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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_STAGE;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
    $config['reroute_email.settings']['enable'] = TRUE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['shield.settings']['shield_enable'] = TRUE;
    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_STAGE);
    $settings['cache_prefix']['default'] = 'test_project_master';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\\-php$',
      '^.+\\.amazee\\.io$',
      '^example1\\.com$',
      '^example2$',
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
    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_PROD;
    $config['environment_indicator.settings']['favicon'] = TRUE;
    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
    $config['reroute_email.settings']['enable'] = FALSE;
    $config['reroute_email.settings']['address'] = 'webmaster@your-site-domain.example';
    $config['reroute_email.settings']['allowed'] = '*@your-site-domain.example';
    $config['system.performance']['cache']['page']['max_age'] = 900;
    $config['system.performance']['css']['preprocess'] = TRUE;
    $config['system.performance']['js']['preprocess'] = TRUE;
    $this->assertConfig($config);

    $settings = $this->expectedSettings(self::ENVIRONMENT_PROD);
    $settings['cache_prefix']['default'] = 'test_project_production';
    $settings['reverse_proxy'] = TRUE;
    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
    $settings['trusted_host_patterns'] = [
      '^localhost$',
      '^nginx\\-php$',
      '^.+\\.amazee\\.io$',
      '^example1\\.com$',
      '^example2$',
    ];

    $this->assertSettings($settings);
  }

  /**
   * Test trusted host patterns for Lagoon routes without a scheme.
   */
  public function testEnvironmentLagoonSchemelessRoutes(): void {
    $this->setEnvVars([
      'LAGOON_KUBERNETES' => 1,
      'LAGOON_ENVIRONMENT_TYPE' => 'development',
      'LAGOON_ROUTES' => 'Example1.com:8443 , example2.com/subpath',
    ]);

    $this->requireSettingsFile();

    $this->assertSettingsContains([
      'trusted_host_patterns' => [
        '^localhost$',
        '^nginx\-php$',
        '^.+\.amazee\.io$',
        '^example1\.com$',
        '^example2\.com$',
      ],
    ]);
  }
  // phpcs:ignore #;> SETTINGS_PROVIDER_LAGOON

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
        // phpcs:ignore #;< MODULE_DEVEL
        'devel',
        // phpcs:ignore #;> MODULE_DEVEL
        // phpcs:ignore #;< MODULE_GENERATED_CONTENT
        'generated_content',
        // phpcs:ignore #;> MODULE_GENERATED_CONTENT
        // phpcs:ignore #;< MODULE_REROUTE_EMAIL
        'reroute_email',
        // phpcs:ignore #;> MODULE_REROUTE_EMAIL
        // phpcs:ignore #;< MODULE_SDC_DEVEL
        'sdc_devel',
        // phpcs:ignore #;> MODULE_SDC_DEVEL
        // phpcs:ignore #;< MODULE_TESTMODE
        'testmode',
        // phpcs:ignore #;> MODULE_TESTMODE
      ],
      'config_sync_directory' => '../config/default',
      'container_yamls' => [$this->app_root . '/' . $this->site_path . '/services.yml'],
      'entity_update_batch_size' => 50,
      'environment' => $environment,
      // phpcs:ignore #;< MODULE_FAST_404
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
      // phpcs:ignore #;> MODULE_FAST_404
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
