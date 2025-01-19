<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

/**
 * Environment trait.
 */
trait EnvTrait {

  protected function getValueFromDstDotenv(string $name, mixed $default = NULL): mixed {
    // Environment variables always take precedence.
    $env_value = static::getenvOrDefault($name, NULL);
    if (!is_null($env_value)) {
      return $env_value;
    }

    $file = $this->config->getDstDir() . '/.env';
    if (!is_readable($file)) {
      return $default;
    }

    $parsed = static::parseDotenv($file);

    return $parsed !== [] ? $parsed[$name] ?? $default : $default;
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

  /**
   * Load .env file.
   *
   * @param string $filename
   *   Filename to load.
   * @param bool $override_existing
   *   Override existing values.
   */
  protected static function loadDotenv(string $filename = '.env', bool $override_existing = FALSE): void {
    $values = static::parseDotenv($filename);

    foreach ($values as $var => $value) {
      if (!static::getenvOrDefault($var) || $override_existing) {
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
   * Reliable wrapper to work with environment values.
   */
  protected static function getenvOrDefault(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] === '') {
      return $default;
    }

    return $vars[$name];
  }

}
