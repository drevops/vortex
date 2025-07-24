<?php

declare(strict_types=1);

namespace Drupal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ToggleableSettingsTest.
 *
 * Tests for Drupal settings that can be enabled or disabled. These are "unit"
 * tests for the business logic of specific settings' variables.
 *
 * Tests appear in the alphabetical order as per files
 * in "sites/default/includes".
 */
#[Group('drupal_settings')]
class SwitchableSettingsTest extends SettingsTestCase {

  /**
   * Test ClamAV configs in Daemon mode with defaults.
   */
  public function testClamavDaemonCustom(): void {
    $this->setEnvVars([
      'DRUPAL_CLAMAV_ENABLED' => TRUE,
      'DRUPAL_CLAMAV_MODE' => 'daemon',
      'CLAMAV_HOST' => 'custom_clamav_host',
      'CLAMAV_PORT' => 3333,
    ]);

    $this->requireSettingsFile();

    $config['clamav.settings']['scan_mode'] = 0;
    $config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'custom_clamav_host';
    $config['clamav.settings']['mode_daemon_tcpip']['port'] = 3333;

    $this->assertConfigContains($config);
  }

  /**
   * Test ClamAV configs in Executable mode.
   */
  public function testClamavExecutable(): void {
    $this->setEnvVars([
      'DRUPAL_CLAMAV_ENABLED' => TRUE,
      'CLAMAV_HOST' => 'custom_clamav_host',
      'CLAMAV_PORT' => 3333,
    ]);

    $this->requireSettingsFile();

    $config['clamav.settings']['scan_mode'] = 1;
    $config['clamav.settings']['executable_path'] = '/usr/bin/clamscan';

    $this->assertConfigContains($config);
  }

  /**
   * Test ClamAV configs in Daemon mode with defaults.
   */
  public function testClamavDaemonDefaults(): void {
    $this->setEnvVars([
      'DRUPAL_CLAMAV_ENABLED' => TRUE,
      'DRUPAL_CLAMAV_MODE' => 'daemon',
    ]);

    $this->requireSettingsFile();

    $config['clamav.settings']['scan_mode'] = 0;
    $config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'clamav';
    $config['clamav.settings']['mode_daemon_tcpip']['port'] = 3310;

    $this->assertConfigContains($config);
  }

  /**
   * Test Config Split config.
   */
  #[DataProvider('dataProviderConfigSplit')]
  public function testConfigSplit(string $env, array $expected_present, array $expected_absent): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => $env,
    ]);

    $this->requireSettingsFile();

    $this->assertConfigContains($expected_present);
    $this->assertConfigNotContains($expected_absent);
  }

  /**
   * Data provider for testConfigSplit().
   */
  public static function dataProviderConfigSplit(): array {
    return [
      [
        static::ENVIRONMENT_LOCAL,
        [
          'config_split.config_split.local' => ['status' => TRUE],
        ],
        [
          'config_split.config_split.stage' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_CI,
        [
          'config_split.config_split.ci' => ['status' => TRUE],
        ],
        [
          'config_split.config_split.stage' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'config_split.config_split.dev' => ['status' => TRUE],
        ],
        [
          'config_split.config_split.stage' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_STAGE,
        [
          'config_split.config_split.stage' => ['status' => TRUE],
        ],
        [
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_PROD,
        [],
        [
          'config_split.config_split.stage' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_SUT,
        [],
        [
          'config_split.config_split.stage' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
    ];
  }

  /**
   * Test Environment Indicator config.
   */
  #[DataProvider('dataProviderEnvironmentIndicator')]
  public function testEnvironmentIndicator(string $env, array $expected_present, array $expected_absent = []): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => $env,
    ]);

    $this->requireSettingsFile();

    $this->assertConfigContains($expected_present);
    $this->assertConfigNotContains($expected_absent);
  }

  /**
   * Data provider for testEnvironmentIndicator().
   */
  public static function dataProviderEnvironmentIndicator(): array {
    return [
      [
        static::ENVIRONMENT_LOCAL,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_LOCAL, 'bg_color' => '#006600', 'fg_color' => '#ffffff'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
      [
        static::ENVIRONMENT_CI,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_CI, 'bg_color' => '#006600', 'fg_color' => '#ffffff'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_DEV, 'bg_color' => '#4caf50', 'fg_color' => '#000000'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
      [
        static::ENVIRONMENT_STAGE,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_STAGE, 'bg_color' => '#fff176', 'fg_color' => '#000000'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
      [
        static::ENVIRONMENT_PROD,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_PROD, 'bg_color' => '#ef5350', 'fg_color' => '#000000'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
      [
        static::ENVIRONMENT_SUT,
        [
          'environment_indicator.indicator' => ['name' => static::ENVIRONMENT_SUT, 'bg_color' => '#006600', 'fg_color' => '#ffffff'],
          'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Test Valkey settings.
   */
  public function testValkey(): void {
    $this->setEnvVars([
      'DRUPAL_REDIS_ENABLED' => 1,
      'VALKEY_HOST' => 'valkey_host',
      'VALKEY_SERVICE_PORT' => 1234,
      'VORTEX_REDIS_EXTENSION_LOADED' => 1,
    ]);

    $this->requireSettingsFile();

    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = 'valkey_host';
    $settings['redis.connection']['port'] = 1234;
    $settings['cache']['default'] = 'cache.backend.redis';

    $this->assertArrayHasKey('bootstrap_container_definition', $this->settings);
    unset($this->settings['bootstrap_container_definition']);

    $this->assertSettingsContains($settings);
  }

  /**
   * Test Valkey partial settings.
   */
  public function testValkeyPartial(): void {
    $this->setEnvVars([
      'DRUPAL_REDIS_ENABLED' => 1,
      'VALKEY_HOST' => 'valkey_host',
      'VALKEY_SERVICE_PORT' => 1234,
      'VORTEX_REDIS_EXTENSION_LOADED' => 0,
    ]);

    $this->requireSettingsFile();

    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = 'valkey_host';
    $settings['redis.connection']['port'] = 1234;
    $no_settings['cache']['default'] = 'cache.backend.redis';

    $this->assertArrayNotHasKey('bootstrap_container_definition', $this->settings);

    $this->assertSettingsContains($settings);
    $this->assertSettingsNotContains($no_settings);
  }

  /**
   * Test Redis settings with REDIS_* environment variables.
   */
  public function testRedisVariables(): void {
    $this->setEnvVars([
      'DRUPAL_REDIS_ENABLED' => 1,
      'REDIS_HOST' => 'redis_host',
      'REDIS_SERVICE_PORT' => 6380,
      'VORTEX_REDIS_EXTENSION_LOADED' => 1,
    ]);

    $this->requireSettingsFile();

    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = 'redis_host';
    $settings['redis.connection']['port'] = 6380;
    $settings['cache']['default'] = 'cache.backend.redis';

    $this->assertArrayHasKey('bootstrap_container_definition', $this->settings);
    unset($this->settings['bootstrap_container_definition']);

    $this->assertSettingsContains($settings);
  }

  /**
   * Test Redis fallback when both VALKEY_* and REDIS_* variables are set.
   */
  public function testRedisValkeyPrecedence(): void {
    $this->setEnvVars([
      'DRUPAL_REDIS_ENABLED' => 1,
      'VALKEY_HOST' => 'valkey_host',
      'VALKEY_SERVICE_PORT' => 1234,
      'REDIS_HOST' => 'redis_host',
      'REDIS_SERVICE_PORT' => 6380,
      'VORTEX_REDIS_EXTENSION_LOADED' => 1,
    ]);

    $this->requireSettingsFile();

    // VALKEY_* variables should take precedence over REDIS_* variables.
    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = 'valkey_host';
    $settings['redis.connection']['port'] = 1234;
    $settings['cache']['default'] = 'cache.backend.redis';

    $this->assertArrayHasKey('bootstrap_container_definition', $this->settings);
    unset($this->settings['bootstrap_container_definition']);

    $this->assertSettingsContains($settings);
  }

  /**
   * Test Shield config.
   */
  #[DataProvider('dataProviderShield')]
  public function testShield(string $env, array $vars, array $expected_present, array $expected_absent = []): void {
    $this->setEnvVars($vars + ['DRUPAL_ENVIRONMENT' => $env]);

    $this->requireSettingsFile();

    $this->assertConfigContains($expected_present);
    $this->assertConfigNotContains($expected_absent);
  }

  /**
   * Data provider for testShield().
   */
  public static function dataProviderShield(): array {
    return [
      [
        static::ENVIRONMENT_LOCAL,
        [],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
        [
          'shield.settings' => ['credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_LOCAL,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
        [
          'shield.settings' => ['credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_LOCAL,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_CI,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_STAGE,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_PROD,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
      ],

      [
        static::ENVIRONMENT_SUT,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => '',
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => 0,
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => 1,
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => '0',
        ],
        [
          'shield.settings' => ['shield_enable' => TRUE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => '1',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => 'false',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],
      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
          'DRUPAL_SHIELD_PRINT' => 'drupal_shield_print',
          'DRUPAL_SHIELD_DISABLED' => 'true',
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE, 'credentials' => ['shield' => ['user' => 'drupal_shield_user', 'pass' => 'drupal_shield_pass']], 'print' => 'drupal_shield_print'],
        ],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_SHIELD_DISABLED' => TRUE,
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
      ],
      [
        static::ENVIRONMENT_STAGE,
        [
          'DRUPAL_SHIELD_DISABLED' => TRUE,
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
      ],
      [
        static::ENVIRONMENT_PROD,
        [
          'DRUPAL_SHIELD_DISABLED' => TRUE,
        ],
        [
          'shield.settings' => ['shield_enable' => FALSE],
        ],
      ],
    ];
  }

  /**
   * Test Stage File Proxy config.
   */
  #[DataProvider('dataProviderStageFileProxy')]
  public function testStageFileProxy(string $env, array $vars, array $expected_present, array $expected_absent = []): void {
    $this->setEnvVars($vars + ['DRUPAL_ENVIRONMENT' => $env]);

    $this->requireSettingsFile();

    $this->assertConfigContains($expected_present);
    $this->assertConfigNotContains($expected_absent);
  }

  /**
   * Data provider for testStageFileProxy().
   */
  public static function dataProviderStageFileProxy(): array {
    return [
      [
        static::ENVIRONMENT_LOCAL,
        [],
        [],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://example.com/'],
        ],
      ],
      [
        static::ENVIRONMENT_LOCAL,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://example.com/'],
        ],
        [],
      ],
      [
        static::ENVIRONMENT_LOCAL,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
        [],
      ],
      [
        static::ENVIRONMENT_LOCAL,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://example.com/'],
        ],
        [],
      ],

      [
        static::ENVIRONMENT_CI,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
        [],
      ],

      [
        static::ENVIRONMENT_DEV,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
        [],
      ],

      [
        static::ENVIRONMENT_STAGE,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
        [],
      ],

      [
        static::ENVIRONMENT_PROD,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
      ],

      [
        static::ENVIRONMENT_SUT,
        [
          'DRUPAL_STAGE_FILE_PROXY_ORIGIN' => 'https://example.com/',
          'DRUPAL_SHIELD_USER' => 'drupal_shield_user',
          'DRUPAL_SHIELD_PASS' => 'drupal_shield_pass',
        ],
        [
          'stage_file_proxy.settings' => ['hotlink' => FALSE, 'origin' => 'https://drupal_shield_user:drupal_shield_pass@example.com/'],
        ],
        [],
      ],
    ];
  }

}
