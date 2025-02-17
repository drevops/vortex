<?php

namespace DrevOps\Installer\Utils;

class Env {

  private static ?self $instance = null;
  private InstallerConfig $config;

  private function __construct(InstallerConfig $config) {
    $this->config = $config;
  }

  public static function init(InstallerConfig $config): void {
    if (is_null(self::$instance)) {
      self::$instance = new self($config);
    }
  }

  /**
   * Reliable wrapper to work with environment values.
   */
  public static function get(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    return !isset($vars[$name]) || $vars[$name] === '' ? $default : $vars[$name];
  }

  public static function getFromDstDotenv(string $name, mixed $default = NULL): mixed {
    // Environment variables always take precedence.
    $env_value = static::get($name);
    if (!is_null($env_value)) {
      return $env_value;
    }

    $file = self::$instance->config->getDstDir() . '/.env';
    if (!is_readable($file)) {
      return $default;
    }

    $parsed = static::parseDotenv($file);

    return $parsed !== [] ? $parsed[$name] ?? $default : $default;
  }

  /**
   * Load values from .env file into current runtime.
   *
   * @param string $filename
   *   Filename to load.
   * @param bool $override_existing
   *   Override existing values.
   */
  public static function loadAllValuesFromDotenv(string $filename = '.env', bool $override_existing = FALSE): void {
    $values = static::parseDotenv($filename);

    foreach ($values as $var => $value) {
      if (!static::get($var) || $override_existing) {
        putenv($var . '=' . $value);
      }
    }

    $GLOBALS['_ENV'] = $GLOBALS['_ENV'] ?? [];
    $GLOBALS['_SERVER'] = $GLOBALS['_SERVER'] ?? [];

    if ($override_existing) {
      $GLOBALS['_ENV'] = $values + $GLOBALS['_ENV'];
      $GLOBALS['_SERVER'] = $values + $GLOBALS['_SERVER'];
    }
    else {
      $GLOBALS['_ENV'] += $values;
      $GLOBALS['_SERVER'] += $values;
    }
  }

  /**
   * Parse .env file.
   *
   * @param string $filename
   *   Filename to parse.
   *
   * @return array<string,string>
   *   Array of parsed values, key is the variable name.
   */
  protected static function parseDotenv(string $filename = '.env'): array {
    if (!is_readable($filename)) {
      return [];
    }

    $contents = file_get_contents($filename);
    if ($contents === FALSE) {
      return [];
    }

    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    return parse_ini_string($contents) ?: [];
  }

}
