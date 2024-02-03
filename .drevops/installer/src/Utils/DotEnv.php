<?php

namespace DrevOps\Installer\Utils;

/**
 * Dotenv utilities.
 */
class DotEnv {

  /**
   * Parse .env file.
   *
   * @param string $filename
   *   The filename.
   *
   * @return array|false
   *   The parsed .env file or FALSE if the file is not readable.
   */
  public static function parseDotenv($filename = '.env'): false|array {
    if (!is_readable($filename)) {
      return FALSE;
    }

    $contents = file_get_contents($filename);
    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    return parse_ini_string($contents);
  }

  /**
   * Load .env file.
   *
   * @param string $filename
   *   The filename.
   * @param bool $override_existing
   *   Whether to override existing environment variables.
   */
  public static function loadDotenv($filename = '.env', $override_existing = FALSE): void {
    $parsed = DotEnv::parseDotenv($filename);

    if ($parsed === FALSE) {
      return;
    }

    foreach ($parsed as $var => $value) {
      if (!Env::get($var) || $override_existing) {
        putenv($var . '=' . $value);
      }
    }

    $GLOBALS['_ENV'] = $GLOBALS['_ENV'] ?? [];
    $GLOBALS['_SERVER'] = $GLOBALS['_SERVER'] ?? [];

    if ($override_existing) {
      $GLOBALS['_ENV'] = $parsed + $GLOBALS['_ENV'];
      $GLOBALS['_SERVER'] = $parsed + $GLOBALS['_SERVER'];
    }
    else {
      $GLOBALS['_ENV'] += $parsed;
      $GLOBALS['_SERVER'] += $parsed;
    }
  }

  /**
   * Get value from .env file.
   *
   * @param string $dst_dir
   *   The destination directory.
   * @param string $name
   *   The name of the variable.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public static function getValueFromDstDotenv(string $dst_dir, $name, mixed $default = NULL): mixed {
    // Environment variables always take precedence.
    $env_value = Env::get($name, NULL);
    if (!is_null($env_value)) {
      return $env_value;
    }

    $file = $dst_dir . '/.env';
    if (!is_readable($file)) {
      return $default;
    }
    $parsed = DotEnv::parseDotenv($file);

    return $parsed ? $parsed[$name] ?? $default : $default;
  }

}
