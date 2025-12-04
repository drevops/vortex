<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Converter.
 *
 * Convert strings to different formats.
 *
 * @package DrevOps\VortexInstaller
 */
class Validator {

  public static function containerImage(string $value): bool {
    $regex = '/^(?:[a-z0-9.\-]+(?::\d+)?\/)?[a-z0-9]+(?:[._\-][a-z0-9]+)*(?:\/[a-z0-9]+(?:[._\-][a-z0-9]+)*)*(?::[a-zA-Z0-9][a-zA-Z0-9._\-]{0,127})?$/x';

    return (bool) preg_match($regex, $value);
  }

  public static function domain(string $value): bool {
    return !filter_var($value, FILTER_VALIDATE_IP)
      && filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
      && str_contains($value, '.');
  }

  public static function githubProject(string $value): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/', $value);
  }

  public static function dirname(string $value): bool {
    return (bool) preg_match('/^(?!^(?:\.{1,2}|CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$)[\w\-.]+$/i', $value);
  }

  public static function gitCommitSha(string $value): bool {
    return (bool) preg_match('/^[0-9a-f]{40}$/i', $value);
  }

  public static function gitCommitShaShort(string $value): bool {
    return (bool) preg_match('/^[0-9a-f]{7}$/i', $value);
  }

  /**
   * Validate a git reference (tag, branch, or commit).
   *
   * Accepts any valid git reference format including:
   * - Special keywords: "stable", "HEAD"
   * - Commit hashes: 40-character or 7-character SHA-1 hashes
   * - Version tags: "1.2.3", "v1.2.3", "25.11.0", "1.0.0-2025.11.0"
   * - Drupal-style tags: "8.x-1.10", "9.x-2.3"
   * - Pre-release tags: "1.x-rc1", "2.0.0-beta"
   * - Branch names: "main", "develop", "feature/my-feature"
   *
   * Follows git reference naming rules:
   * - Can contain alphanumeric, dot, hyphen, underscore, slash
   * - Cannot start with dot or hyphen
   * - Cannot contain: @, ^, ~, :, ?, *, [, space, \, @{
   * - Cannot end with .lock or contain
   *
   * @param string $value
   *   The reference string to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   *
   * @see https://git-scm.com/docs/git-check-ref-format
   */
  public static function gitRef(string $value): bool {
    // Reserved keywords have special meaning.
    if (in_array($value, ['stable', 'HEAD'], TRUE)) {
      return TRUE;
    }

    // Already supported: commit hashes.
    if (self::gitCommitSha($value) || self::gitCommitShaShort($value)) {
      return TRUE;
    }

    // Git ref naming rules (simplified):
    // - Can contain alphanumeric, dot, hyphen, underscore, slash, plus.
    // - Cannot start with dot or hyphen.
    // - Cannot contain .. or end with .lock.
    // - Cannot end with / or contain //.
    $pattern = '/^(?![.\-])(?!.*\.\.)[a-zA-Z0-9._\/+-]+(?<!\.lock)$/';

    if (!preg_match($pattern, $value)) {
      return FALSE;
    }

    // Reject refs ending with slash or containing consecutive slashes.
    if (str_ends_with($value, '/') || str_contains($value, '//')) {
      return FALSE;
    }

    // Additional disallowed patterns.
    $disallowed = ['@', '^', '~', ':', '?', '*', '[', ' ', '\\', '@{'];
    foreach ($disallowed as $char) {
      if (str_contains($value, $char)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
