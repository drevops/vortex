<?php

namespace DrevOps\Installer\Utils;

class Strings {

  public static function utfPos(string $string): ?int {
    return preg_match('/^[\x00-\x7F]/', $string);
  }

  public static function strlenPlain(string $text): int {
    return strlen(preg_replace('/\e\[\d+m/', '', $text));
  }

}
