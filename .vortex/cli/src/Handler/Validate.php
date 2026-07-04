<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

/**
 * Shared value validation and transformation helpers for Vortex handlers.
 *
 * @package DrevOps\VortexCli\Handler
 */
final class Validate {

  /**
   * Whether a value is a valid machine name.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when valid.
   */
  public static function isMachineName(string $value): bool {
    return $value !== '' && preg_match('/^[a-z0-9_]+$/', $value) === 1;
  }

  /**
   * Whether a value is a valid PHP-package-style name.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when valid.
   */
  public static function isPhpPackageName(string $value): bool {
    return $value !== '' && preg_match('/^[a-z0-9_-]+$/', $value) === 1;
  }

  /**
   * Whether a value is a valid directory name.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when valid.
   */
  public static function isDirname(string $value): bool {
    return preg_match('/^(?!(?:\.{1,2}|CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$)[\w\-.]+$/i', $value) === 1;
  }

  /**
   * Whether a value is a valid container image (with an optional tag).
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when valid.
   */
  public static function isContainerImage(string $value): bool {
    return preg_match('#^[a-z0-9]+(?:[._\-/][a-z0-9]+)*(?::[\w][\w.\-]*)?$#i', $value) === 1;
  }

  /**
   * Whether a value is a valid domain name.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when valid.
   */
  public static function isDomain(string $value): bool {
    if (filter_var($value, FILTER_VALIDATE_IP) !== FALSE) {
      return FALSE;
    }

    return str_contains($value, '.') && filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== FALSE;
  }

  /**
   * Whether a value is a non-empty label.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE when non-empty after trimming.
   */
  public static function isFilledLabel(string $value): bool {
    return trim($value) !== '';
  }

  /**
   * Normalize a value to a bare domain (no scheme, no trailing slash).
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The normalized domain.
   */
  public static function domain(string $value): string {
    $value = (string) preg_replace('#^https?://#i', '', trim($value));

    return strtolower(rtrim($value, '/'));
  }

}
