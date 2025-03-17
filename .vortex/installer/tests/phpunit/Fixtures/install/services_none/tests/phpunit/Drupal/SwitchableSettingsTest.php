<?php

declare(strict_types=1);

namespace Drupal;

/**
 * Class ToggleableSettingsTest.
 *
 * Tests for Drupal settings that can be enabled or disabled. These are "unit"
 * tests for the business logic of specific settings' variables.
 *
 * Tests appear in the alphabetical order as per files
 * in "sites/default/includes".
 *
 * @group drupal_settings
 */
class SwitchableSettingsTest extends SettingsTestCase {

  /**
   * Test Config Split config.
   *
   * @dataProvider dataProviderConfigSplit
   */
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
          'config_split.config_split.test' => NULL,
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
          'config_split.config_split.test' => NULL,
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
          'config_split.config_split.test' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_STAGE,
        [
          'config_split.config_split.test' => ['status' => TRUE],
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
          'config_split.config_split.test' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
      [
        static::ENVIRONMENT_SUT,
        [],
        [
          'config_split.config_split.test' => NULL,
          'config_split.config_split.dev' => NULL,
          'config_split.config_split.ci' => NULL,
          'config_split.config_split.local' => NULL,
        ],
      ],
    ];
  }

  /**
   * Test Environment Indicator config.
   *
   * @dataProvider dataProviderEnvironmentIndicator
   */
  public function testEnvironmentIndicator(string $env, array $expected_present, array $expected_absent = []): void {
    $this->setEnvVars([
      'DRUPAL_ENVIRONMENT' => $env,
    ]);

    $this->requireSettingsFile();

    $this->assertConfigContains($expected_present);
    $this->assertConfigNotContains($expected_absent);
  }

  /**
   * Data provider for testEntityPrint().
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
   * Test Shield config.
   *
   * @dataProvider dataProviderShield
   */
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
    ];
  }

  /**
   * Test Stage File Proxy config.
   *
   * @dataProvider dataProviderStageFileProxy
   */
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
