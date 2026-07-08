<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Discovers a value by reading a key from the project's `.env` file.
 *
 * @package DrevOps\Tui\Discovery
 */
class Dotenv extends AbstractDiscover {

  /**
   * Construct a dotenv discovery rule.
   *
   * @param string $key
   *   The env key to read.
   */
  public function __construct(public readonly string $key) {
  }

  /**
   * {@inheritdoc}
   */
  public function discover(string $directory): mixed {
    $file = $this->join($directory, '.env');

    if (!is_file($file)) {
      return NULL;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // @codeCoverageIgnoreStart
    if ($lines === FALSE) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }
      if (str_starts_with($line, '#')) {
        continue;
      }

      $pos = strpos($line, '=');
      if ($pos === FALSE) {
        continue;
      }
      if (trim(substr($line, 0, $pos)) !== $this->key) {
        continue;
      }

      return trim(trim(substr($line, $pos + 1)), '"\'');
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return ['dotenv' => $this->key];
  }

}
