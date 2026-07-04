<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

use AlexSkrypnyk\Str2Name\Str2Name;

/**
 * String conversions used across Vortex handlers.
 *
 * @package DrevOps\VortexCli\Utils
 */
class Converter extends Str2Name {

  /**
   * Convert a value to an extended machine name.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The extended machine name.
   */
  public static function machineExtended(string $value): string {
    $string = static::strict($value);

    return mb_strtolower(str_replace([' '], '_', $string));
  }

}
