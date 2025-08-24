<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator as ComposerJsonManipulator;

class JsonManipulator extends ComposerJsonManipulator {

  protected string $contents;

  public function __construct(string $contents) {
    $this->contents = $contents;
    parent::__construct($contents);
  }

  public static function fromFile(string $composer_json): ?self {
    if (!is_readable($composer_json) || !is_file($composer_json)) {
      return NULL;
    }

    $contents = file_get_contents($composer_json);
    if ($contents === FALSE) {
      throw new \RuntimeException('Failed to read composer.json from ' . $composer_json);
    }

    return new self($contents);
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
