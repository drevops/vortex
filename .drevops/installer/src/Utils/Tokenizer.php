<?php

namespace DrevOps\Installer\Utils;

use ReflectionClass;
use RuntimeException;

class Tokenizer {

  public static function removeTokenWithContentFromDir($token, $dir) {
    self::validateToken($token);
    $files = Files::scandirRecursive($dir, Files::ignorePaths());
    foreach ($files as $filename) {
      self::removeTokenFromFile($filename, "#;< $token", "#;> $token", TRUE);
    }
  }

  public static function removeTokenLineFromDir($token, $dir) {
    if (!empty($token)) {
      self::validateToken($token);
      $files = Files::scandirRecursive($dir, Files::ignorePaths());
      foreach ($files as $filename) {
        self::removeTokenFromFile($filename, $token, NULL);
      }
    }
  }

  public static function removeTokenFromFile($filename, $token_begin, $token_end = NULL, $with_content = FALSE) {
    if (Files::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $content = file_get_contents($filename);
    $newContent = self::removeTokensFromString($content, $token_begin, $token_end, $with_content);
    file_put_contents($filename, $newContent);
  }

  public static function removeTokensFromString($content, $token_begin, $token_end = NULL, $with_content = FALSE) {
    $token_end = $token_end ?? $token_begin;

    if ($token_begin != $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote($token_begin) . '/', $content);
      $token_end_count = preg_match_all('/' . preg_quote($token_end) . '/', $content);
      if ($token_begin_count != $token_end_count) {
        throw new RuntimeException(sprintf('Invalid begin and end token count: begin is %s(%s), end is %s(%s).', $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = explode(PHP_EOL, $content);
    foreach ($lines as $line) {
      if (strpos($line, $token_begin) !== FALSE) {
        if ($with_content) {
          $within_token = TRUE;
        }
        continue;
      }
      elseif (strpos($line, $token_end) !== FALSE) {
        if ($with_content) {
          $within_token = FALSE;
        }
        continue;
      }

      if ($with_content && $within_token) {
        continue;
      }

      $out[] = $line;
    }

    return implode(PHP_EOL, $out);
  }

  /**
   * Validate token.
   *
   * @param string $token
   *   Token to validate.
   *
   * @throws \RuntimeException
   *   If token is not defined in Token interface.
   */
  protected static function validateToken(string $token) {
    $interface = 'DrevOps\Installer\TokenInterface';
    $reflection = new ReflectionClass($interface);

    $constants = $reflection->getConstants();
    if (!in_array($token, $constants) && (str_starts_with($token, '!') && !in_array(substr($token, 1), $constants))) {
      throw new RuntimeException(sprintf('Token %s is not defined in %s.', $token, $interface));
    }
  }

}
