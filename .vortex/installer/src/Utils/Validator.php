<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

/**
 * Converter.
 *
 * Convert strings to different formats.
 *
 * @package DrevOps\Installer
 */
class Validator {

  public static function containerImage(string $value): bool {
    $regex = '/^(?:[a-z0-9.\-]+(?::\d+)?\/)?[a-z0-9]+(?:[._\-][a-z0-9]+)*(?:\/[a-z0-9]+(?:[._\-][a-z0-9]+)*)*(?::[a-zA-Z0-9][a-zA-Z0-9._\-]{0,127})?$/x';

    return (bool) preg_match($regex, $value);
  }

  public static function domain(string $value): bool {
    return !filter_var($value, FILTER_VALIDATE_IP)
      && filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
      && strpos($value, '.') !== FALSE;
  }

  public static function githubProject(string $value): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/', $value);
  }

  public static function dirname(string $value): bool {
    return (bool) preg_match('/^(?!^(?:\.{1,2}|CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$)[\w\-.]+$/i', $value);
  }

  public static function gitCommit(string $value): bool {
    return (bool) preg_match('/^[0-9a-f]{40}$/i', $value);
  }

  public static function gitShortCommit(string $value): bool {
    return (bool) preg_match('/^[0-9a-f]{7}$/i', $value);
  }

}
