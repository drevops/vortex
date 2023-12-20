<?php

namespace Drupal;

use PHPUnit\Framework\TestCase;

/**
 * Class SettingsTestCase.
 *
 * Base class for testing Drupal settings.
 *
 *  phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
 */
abstract class SettingsTestCase extends TestCase {

  /**
   * Defines a constant for the name of the 'testing' environment.
   *
   * This is used to differentiate between the environment names set in
   * settings.php and the environment used to test configs and settings
   * in environment-less way.
   */
  const ENVIRONMENT_SUT = 'env-testing';

  /**
   * Defines a constant for the name of the 'local' environment.
   */
  const ENVIRONMENT_LOCAL = 'local';

  /**
   * Defines a constant for the name of the 'ci' environment.
   */
  const ENVIRONMENT_CI = 'ci';

  /**
   * Defines a constant for the name of the 'prod' environment.
   */
  const ENVIRONMENT_PROD = 'prod';

  /**
   * Defines a constant for the name of the 'test' environment.
   */
  const ENVIRONMENT_TEST = 'test';

  /**
   * Defines a constant for the name of the 'dev' environment.
   */
  const ENVIRONMENT_DEV = 'dev';

  /**
   * Defines a constant for the temp path used in testing.
   */
  const TMP_PATH_TESTING = '/tmp-test';

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
   * Set environment variables.
   *
   * @param array $vars
   *   Array of environment variables.
   *
   * @SuppressWarnings(PHPMD.ElseExpression)
   */
  protected function setEnvVars(array $vars): void {
    if (!isset($vars['CI'])) {
      $vars['CI'] = FALSE;
    }

    if (!isset($vars['LAGOON'])) {
      $vars['LAGOON'] = FALSE;
    }

    $vars['TMP'] = static::TMP_PATH_TESTING;

    // Filtered real vars without a value to unset them in the lines below.
    $vars_real = self::getRealEnvVarsFilteredNoValues([
      // Service variables.
      'MARIADB_',
      'REDIS_',
      'COMPOSE_',
      'GITHUB_',
      'DOCKER_',
      // DrevOps and Drupal variables.
      'DREVOPS_',
      'DRUPAL_',
    ]);

    // Passed vars + existing vars + filtered real vars.
    $this->envVars = $vars + $this->envVars + $vars_real;

    foreach ($this->envVars as $name => $value) {
      // Unset the variable if it has a value of NULL.
      if (is_null($value)) {
        putenv("$name");
      }
      else {
        putenv("$name=$value");
      }
    }
  }

  /**
   * Get real environment variables with no values.
   *
   * @param array $prefixes
   *   Array of prefixes to filter the variables by.
   *
   * @return array
   *   Array of environment variables.
   */
  protected static function getRealEnvVarsFilteredNoValues(array $prefixes = []): array {
    $vars = getenv();

    $vars = array_filter(array_keys($vars), function ($key) use ($prefixes): bool {
      foreach ($prefixes as $prefix) {
        if (str_starts_with($key, $prefix)) {
          return TRUE;
        }
      }

      return FALSE;
    });

    return array_fill_keys($vars, NULL);
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
    $app_root = getcwd();
    if (empty($app_root)) {
      throw new \RuntimeException('Could not determine application root.');
    }
    $site_path = 'sites/default';
    $config = [];
    $settings = [];
    $databases = [];

    require $app_root . DIRECTORY_SEPARATOR . $site_path . DIRECTORY_SEPARATOR . 'settings.php';

    $this->app_root = $app_root;
    $this->site_path = $site_path;
    $this->config = $config;
    $this->settings = $settings;
    $this->databases = $databases;
  }

  /**
   * Assert that config retrieved from the real settings file match test data.
   *
   * @param array $expected
   *   Array of expected configs.
   * @param array $expected_keys_only
   *   Array of expected configs that will be asserted by keys and data type
   *   only. This is used for cases when the data should exist but the value
   *   is static. Supports only top-level keys.
   */
  protected function assertConfig(array $expected, array $expected_keys_only = []): void {
    $actual_keys_only = array_intersect_key($this->config, $expected_keys_only);
    $actual = array_diff_key($this->config, $expected_keys_only);

    $this->assertEquals($expected, $actual, 'Configs');
    $this->assertArrayContainsKeysTypes($expected_keys_only, $actual_keys_only, 'Config');
  }

  /**
   * Assert that config contains partial data.
   *
   * @param array $expected
   *   Array of expected configs.
   */
  protected function assertConfigContains(array $expected): void {
    $this->assertArraySubset($expected, $this->config, 'Config array contains');
  }

  /**
   * Assert that config does not contain partial data.
   *
   * @param array $expected
   *   Array of expected configs.
   */
  protected function assertConfigNotContains(array $expected): void {
    $this->assertArrayNotSubset($expected, $this->config, 'Config array does not contain');
  }

  /**
   * Assert that settings retrieved from the real settings file match test data.
   *
   * @param array $expected
   *   Array of expected setting.
   * @param array $expected_keys_only
   *   Array of expected setting that will be asserted by keys and data type
   *   only. This is used for cases when the data should exist but the value
   *   is static. Supports only top-level keys.
   */
  protected function assertSettings(array $expected, array $expected_keys_only = []): void {
    $actual_keys_only = array_intersect_key($this->settings, $expected_keys_only);
    $actual = array_diff_key($this->settings, $expected_keys_only);

    $this->assertEquals($expected, $actual, 'Settings');
    $this->assertArrayContainsKeysTypes($expected_keys_only, $actual_keys_only, 'Settings');
  }

  /**
   * Assert that settings contain partial data.
   *
   * @param array $expected
   *   Array of expected settings.
   */
  protected function assertSettingsContains(array $expected): void {
    $this->assertArraySubset($expected, $this->settings, 'Settings array contains');
  }

  /**
   * Assert that settings do not contain partial data.
   *
   * @param array $expected
   *   Array of expected settings.
   */
  protected function assertSettingsNotContains(array $expected): void {
    $this->assertArrayNotSubset($expected, $this->settings, 'Settings array does not contain');
  }

  /**
   * Assert that an array contains a subset.
   *
   * @param array $subset
   *   Array of subset to search for.
   * @param array $haystack
   *   Array to search in.
   * @param string $message
   *   Message to display on failure.
   *
   * @SuppressWarnings(PHPMD.ElseExpression)
   */
  protected function assertArraySubset(array $subset, array $haystack, string $message = ''): void {
    foreach ($subset as $key => $value) {
      $this->assertTrue(array_key_exists($key, $haystack), $message . ": Key {$key} does not exist.");

      if (is_array($value)) {
        $this->assertArraySubset($value, $haystack[$key], $message);
      }
      else {
        $this->assertEquals($value, $haystack[$key], $message);
      }
    }
  }

  /**
   * Assert that an array does not contain a subset.
   *
   * This is not a mirror of the assertArraySubset: it does not check the value
   * of the key, only the key itself.
   *
   * @param array $subset
   *   Array of subset to search for.
   * @param array $haystack
   *   Array to search in.
   * @param string $message
   *   Message to display on failure.
   */
  protected function assertArrayNotSubset(array $subset, array $haystack, string $message = ''): void {
    foreach ($subset as $key => $value) {
      if (is_array($value)) {
        $this->assertArrayNotSubset($value, $haystack[$key] ?? [], $message);
        continue;
      }

      $this->assertFalse(array_key_exists($key, $haystack), $message . ": Key {$key} exists at the deepest level.");
    }
  }

  /**
   * Assert that an array contains a subset by keys and value data types.
   *
   * Used for cases when the data in array path should exist but the value
   * itself does not matter.
   *
   * @param array $subset
   *   Array to search for.
   * @param array $haystack
   *   Array to search in.
   * @param string $message
   *   Message to display on failure.
   */
  protected function assertArrayContainsKeysTypes(array $subset, array $haystack, string $message = ''): void {
    $message = !empty($message) ? $message . ': ' : $message;
    foreach ($subset as $key => $value) {
      $this->assertArrayHasKey($key, $haystack, $message . 'Keys of key-only values match');
      $this->assertEquals(gettype($value), gettype($haystack[$key]), $message . 'Types of key-only values match');
    }
  }

}
