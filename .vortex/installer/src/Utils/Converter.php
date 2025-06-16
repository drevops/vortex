<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use AlexSkrypnyk\Str2Name\Str2Name;

class Converter extends Str2Name {

  public static function machineExtended(string $value): string {
    $string = static::strict($value);

    return mb_strtolower(str_replace([' '], '_', $string));
  }

}
