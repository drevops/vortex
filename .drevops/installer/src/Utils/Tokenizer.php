<?php

namespace DrevOps\Installer\Utils;

use ReflectionClass;
use RuntimeException;

class Tokenizer {

  public static function removeTokenWithContentFromDir(string $token, $dir): void {
    self::validateToken($token);
    $files = Files::scandirRecursive($dir, Files::ignorePaths());
    foreach ($files as $filename) {
      self::removeTokenFromFile($filename, '#;< ' . $token, '#;> ' . $token, TRUE);
    }
  }

  public static function removeTokenLineFromDir($token, $dir): void {
    if (!empty($token)) {
      self::validateToken($token);
      $files = Files::scandirRecursive($dir, Files::ignorePaths());
      foreach ($files as $filename) {
        self::removeTokenFromFile($filename, $token, NULL);
      }
    }
  }

  public static function removeTokenFromFile($filename, $token_begin, $token_end = NULL, $with_content = FALSE): void {
    if (Files::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $content = file_get_contents($filename);
    $newContent = self::removeTokensFromString($content, $token_begin, $token_end, $with_content);
    file_put_contents($filename, $newContent);
  }

  public static function removeTokensFromString($content, $token_begin, $token_end = NULL, $with_content = FALSE): string {
    $token_end = $token_end ?? $token_begin;

    if ($token_begin != $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote((string) $token_begin) . '/', (string) $content);
      $token_end_count = preg_match_all('/' . preg_quote((string) $token_end) . '/', (string) $content);
      if ($token_begin_count !== $token_end_count) {
        throw new RuntimeException(sprintf('Invalid begin and end token count: begin is %s(%s), end is %s(%s).', $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = explode(PHP_EOL, (string) $content);
    foreach ($lines as $line) {
      if (str_contains($line, (string) $token_begin)) {
        if ($with_content) {
          $within_token = TRUE;
        }
        continue;
      }
      elseif (str_contains($line, (string) $token_end)) {
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
