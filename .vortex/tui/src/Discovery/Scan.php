<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Discovers a list of directory entries, optionally filtered by type.
 *
 * @package DrevOps\Tui\Discovery
 */
class Scan extends AbstractDiscover {

  /**
   * Construct a scan discovery rule.
   *
   * @param string $dir
   *   The directory to scan, relative to the project directory.
   * @param string $type
   *   The entry type to keep: "dir", "file" or "any".
   */
  public function __construct(public readonly string $dir, public readonly string $type = 'any') {
  }

  /**
   * {@inheritdoc}
   */
  public function discover(string $directory): mixed {
    $full = $this->join($directory, $this->dir);

    if (!is_dir($full)) {
      return [];
    }

    $entries = scandir($full);
    // @codeCoverageIgnoreStart
    if ($entries === FALSE) {
      return [];
    }
    // @codeCoverageIgnoreEnd
    $out = [];

    foreach ($entries as $entry) {
      if ($entry === '.') {
        continue;
      }
      if ($entry === '..') {
        continue;
      }
      $path = $full . '/' . $entry;
      if ($this->type === 'dir' && !is_dir($path)) {
        continue;
      }
      if ($this->type === 'file' && !is_file($path)) {
        continue;
      }

      $out[] = $entry;
    }

    sort($out);

    return $out;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return ['scan' => ['dir' => $this->dir, 'type' => $this->type]];
  }

}
