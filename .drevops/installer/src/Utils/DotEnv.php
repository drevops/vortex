<?php

namespace DrevOps\Installer\Utils;

/**
 *
 */
class DotEnv {

  public static function parseDotenv($filename = '.env'): false|array {
    if (!is_readable($filename)) {
      return FALSE;
    }

    $contents = file_get_contents($filename);
    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    return parse_ini_string($contents);
  }

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

  public static function getValueFromDstDotenv(string $dst_dir, $name, $default = NULL) {
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
