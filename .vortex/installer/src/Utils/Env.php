<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

class Env {

  const TRUE = 'true';

  const FALSE = 'false';

  const NULL = 'null';

  /**
   * Reliable wrapper to work with environment values.
   */
  public static function get(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    return !isset($vars[$name]) || $vars[$name] === '' ? $default : $vars[$name];
  }

  public static function put(string $name, string $value): void {
    putenv($name . '=' . $value);
  }

  public static function getFromDotenv(string $name, string $dir): ?string {
    // Environment variables always take precedence.
    $env_value = static::get($name);
    if (!is_null($env_value)) {
      return $env_value;
    }

    $file = $dir . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($file)) {
      return NULL;
    }

    $parsed = static::parseDotenv($file);

    return $parsed !== [] ? ($parsed[$name] ?? NULL) : NULL;
  }

  /**
   * Load values from .env file into current runtime.
   *
   * @param string $filename
   *   Filename to load.
   * @param bool $override_existing
   *   Override existing values.
   */
  public static function putFromDotenv(string $filename = '.env', bool $override_existing = FALSE): void {
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

  public static function toValue(string $value): mixed {
    if (str_contains($value, ',')) {
      return Converter::fromList($value);
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    if ($value === static::TRUE) {
      return TRUE;
    }

    if ($value === static::FALSE) {
      return FALSE;
    }

    if ($value === static::NULL) {
      return NULL;
    }

    return $value;
  }

}
