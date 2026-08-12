<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Installer configuration.
 *
 * Installer config is a config of this installer script.
 */
final class Normalizer {

  /**
   * Normalize options to [{value, label}] format.
   *
   * @param array|null $options
   *   Raw options from handler.
   *
   * @return array<array<string, string>>|null
   *   Normalized options or NULL.
   */
  public static function normalizeOptions(?array $options): ?array {
    if ($options === NULL) {
      return NULL;
    }

    $normalized = [];

    if (array_is_list($options)) {
      foreach ($options as $option) {
        $normalized[] = ['value' => (string) $option, 'label' => (string) $option];
      }
    }
    else {
      foreach ($options as $value => $label) {
        $normalized[] = ['value' => (string) $value, 'label' => (string) $label];
      }
    }

    return $normalized;
  }

}
