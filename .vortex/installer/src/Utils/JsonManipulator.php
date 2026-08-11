<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator as ComposerJsonManipulator;

class JsonManipulator extends ComposerJsonManipulator {

  public function __construct(protected string $contents) {
    parent::__construct($this->contents);
  }

  public static function fromFile(string $composer_json): ?self {
    if (!is_readable($composer_json) || !is_file($composer_json)) {
      return NULL;
    }

    $contents = file_get_contents($composer_json);
    if ($contents === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf(
        'Unable to read composer.json from %s: %s',
        $composer_json,
        error_get_last()['message'] ?? 'unknown error'
      ));
      // @codeCoverageIgnoreEnd
    }

    try {
      $instance = new self($contents);
    }
    catch (\Exception) {
      // Invalid JSON.
      return NULL;
    }

    return $instance;
  }

  /**
   * Apply changes to a JSON file in place.
   *
   * @param string $file
   *   Path to the JSON file.
   * @param callable $callback
   *   Callback receiving the manipulator for the file.
   *
   * @throws \RuntimeException
   *   If the file cannot be read, does not contain valid JSON, or cannot be
   *   written back.
   */
  public static function updateFile(string $file, callable $callback): void {
    $instance = self::fromFile($file);

    if (!$instance instanceof self) {
      throw new \RuntimeException(sprintf('Unable to read a JSON file at "%s".', $file));
    }

    $callback($instance);

    $contents = $instance->getContents();
    if (file_put_contents($file, $contents) !== strlen($contents)) {
      throw new \RuntimeException(sprintf('Unable to write a JSON file at "%s".', $file));
    }
  }

  /**
   * Get the value of a composer.json key.
   *
   * @param string $name
   *   Name of the key.
   *
   * @return mixed|null
   *   Value of the key or NULL if not found.
   */
  public function getProperty(string $name): mixed {
    $sub = explode('.', $name);
    $main = array_shift($sub);

    $decoded = JsonFile::parseJson($this->contents);

    if (!isset($decoded[$main])) {
      return NULL;
    }

    if (empty($sub)) {
      return $decoded[$main];
    }

    // Collect from the sub-keys.
    $arr = $decoded[$main];

    foreach ($sub as $key) {
      if (is_array($arr) && array_key_exists($key, $arr)) {
        $arr = $arr[$key];
      }
      else {
        return NULL;
      }
    }

    return $arr;
  }

}
