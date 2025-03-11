<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

class Strings {

  public static function utfPos(string $string): ?int {
    $pos = preg_match('/^[\x00-\x7F]/', $string);
    return $pos !== FALSE ? $pos : NULL;
  }

  public static function strlenPlain(string $text): int {
    $clean_text = preg_replace('/\e\[[0-9;]*m/', '', $text);
    return mb_strwidth($clean_text, 'UTF-8');
  }

}
