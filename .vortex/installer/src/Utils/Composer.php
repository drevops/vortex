<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

class Composer {

  /**
   * Get the value of a composer.json key.
   *
   * @param string $name
   *   Name of the key.
   * @param string $composer_json
   *   Path to the composer.json file.
   *
   * @return mixed|null
   *   Value of the key or NULL if not found.
   */
  public static function getJsonValue(string $name, string $composer_json): mixed {
    if (is_readable($composer_json)) {
      $contents = file_get_contents($composer_json);
      if ($contents === FALSE) {
        return NULL;
      }

      $json = json_decode($contents, TRUE);
      if (isset($json[$name])) {
        return $json[$name];
      }
    }

    return NULL;
  }

}
