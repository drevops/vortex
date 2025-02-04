<?php

declare(strict_types=1);

namespace DrevOps\Installer;

/**
 * Converter.
 *
 * Convert strings to different formats.
 *
 * @package DrevOps\Installer
 */
class Validator {

  public static function containerImage(string $string): bool {
    $regex = '/^(?:[a-z0-9.\-]+(?::\d+)?\/)?[a-z0-9]+(?:[._\-][a-z0-9]+)*(?:\/[a-z0-9]+(?:[._\-][a-z0-9]+)*)*(?::[a-zA-Z0-9][a-zA-Z0-9._\-]{0,127})?$/x';

    return (bool) preg_match($regex, $string);
  }

}
