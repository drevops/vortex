<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

class Env {

  const TRUE = 'true';

  const FALSE = 'false';

  const NULL = 'null';

  /**
   * Superseded prefixes, mapped to the prefix that replaced them.
   *
   * Ordered longest-first so the more specific prefix is matched before the
   * shorter one it starts with.
   *
   * @var array<string,string>
   */
  const LEGACY_PREFIXES = [
    'VORTEX_CLI_INSTALL_' => 'VORTEX_INSTALLER_',
    'VORTEX_CLI_' => 'VORTEX_INSTALLER_',
  ];

  /**
   * Legacy names that were read, keyed by the name that supersedes them.
   *
   * @var array<string,string>
   */
  protected static array $legacyUsed = [];

  /**
   * Reliable wrapper to work with environment values.
   */
  public static function get(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    if (array_key_exists($name, $vars)) {
      return $vars[$name];
    }

    $legacy = static::legacyName($name);

    if (!is_null($legacy) && array_key_exists($legacy, $vars)) {
      static::$legacyUsed[$name] = $legacy;

      return $vars[$legacy];
    }

    return $default;
  }

  /**
   * Resolve the superseded name for a current variable name.
   *
   * @return string|null
   *   The legacy name, or NULL when the name carries no superseded prefix.
   */
  public static function legacyName(string $name): ?string {
    foreach (static::LEGACY_PREFIXES as $current => $legacy) {
      if (str_starts_with($name, $current)) {
        return $legacy . substr($name, strlen($current));
      }
    }

    return NULL;
  }

  /**
   * Get the legacy names read so far, keyed by the name that supersedes them.
   *
   * @return array<string,string>
   *   Current name => legacy name that provided the value.
   */
  public static function legacyUsed(): array {
    return static::$legacyUsed;
  }

  /**
   * Forget the recorded legacy reads.
   */
  public static function resetLegacyUsed(): void {
    static::$legacyUsed = [];
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

    $GLOBALS['_ENV'] ??= [];
    $GLOBALS['_SERVER'] ??= [];

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
  public static function parseDotenv(string $filename = '.env'): array {
    if (!is_file($filename) || !is_readable($filename)) {
      return [];
    }

    $contents = file_get_contents($filename);
    if ($contents === FALSE) {
      // @codeCoverageIgnoreStart
      return [];
      // @codeCoverageIgnoreEnd
    }

    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$errors): bool {
      $errors[] = [
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
      ];

      return TRUE;
    });

    $result = parse_ini_string((string) $contents);

    restore_error_handler();

    if ($result === FALSE) {
      $message = array_reduce($errors ?? [], fn(string $carry, array $error): string => $carry . $error['message'] . PHP_EOL, '');

      throw new \RuntimeException(sprintf('Unable to parse file %s: %s', $filename, $message));
    }

    return $result;
  }

  /**
   * Set value in .env file.
   *
   * @param string $name
   *   Variable name.
   * @param string|null $value
   *   Value to set. If null, the variable will be removed if present,
   *   or added with just '=' if not present.
   * @param string $filename
   *   Filename to write to.
   * @param bool $enabled
   *   If TRUE, the variable will be written normally (e.g., VAR=value).
   *   If FALSE, the variable will be written as commented
   *   out (e.g., # VAR=value).
   *   Default is TRUE.
   *
   * @return array<string,string>
   *   Array of parsed values after modification.
   */
  public static function writeValueDotenv(string $name, ?string $value = NULL, string $filename = '.env', bool $enabled = TRUE): array {
    if (!is_readable($filename)) {
      throw new \RuntimeException(sprintf('File %s is not readable.', $filename));
    }

    $contents = file_get_contents($filename);
    if ($contents === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Unable to read file %s.', $filename));
      // @codeCoverageIgnoreEnd
    }

    // Pattern to match the variable name and its value, including multiline
    // quoted values. Matches both normal and commented-out variables.
    $pattern = '/^#?\s*(' . preg_quote($name, '/') . ')=("(?:[^"\\\\]|\\\\.)*"|[^\r\n]*)/m';

    if ($value === NULL) {
      // Remove the variable if setting to null and it exists.
      if (preg_match($pattern, $contents)) {
        // Remove existing variable line.
        $contents = preg_replace($pattern, '', $contents);
        // Clean up any double newlines that might result from removal.
        $contents = preg_replace('/\n\n+/', "\n\n", (string) $contents);
      }
      else {
        // Add empty line if it doesn't exist.
        if (!str_ends_with($contents, "\n")) {
          $contents .= "\n";
        }
        $prefix = $enabled ? '' : '# ';
        $contents .= $prefix . $name . "=\n";
      }
    }
    else {
      // Format the new value with proper quoting.
      $new_value = static::formatValueForDotenv($value);
      $prefix = $enabled ? '' : '# ';
      $replacement = $prefix . '$1=' . $new_value;

      if (preg_match($pattern, $contents)) {
        // Replace existing variable value.
        $contents = preg_replace($pattern, $replacement, $contents);
      }
      else {
        // Add new variable at the end with proper newline.
        if (!str_ends_with($contents, "\n")) {
          $contents .= "\n";
        }
        $contents .= $prefix . $name . '=' . $new_value . "\n";
      }
    }

    if (file_put_contents($filename, $contents) === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Unable to write to file %s.', $filename));
      // @codeCoverageIgnoreEnd
    }

    // Return parsed values after modification.
    return static::parseDotenv($filename);
  }

  /**
   * Format a value for .env file with proper quoting.
   *
   * @param string $value
   *   The value to format.
   *
   * @return string
   *   The formatted value with quotes if needed.
   */
  protected static function formatValueForDotenv(string $value): string {
    if (preg_match('/[\s#$!;&|><*?(){}[\]`\'"]/', $value)) {
      $escaped = str_replace('\\', '\\\\', $value);
      $escaped = str_replace('"', '\\"', $escaped);
      return '"' . $escaped . '"';
    }

    return $value;
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
