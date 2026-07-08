<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Discovers whether a path exists within the project directory.
 *
 * @package DrevOps\Tui\Discovery
 */
class PathExists extends AbstractDiscover {

  /**
   * Construct a path-exists discovery rule.
   *
   * @param string $path
   *   The path, relative to the project directory.
   */
  public function __construct(public readonly string $path) {
  }

  /**
   * {@inheritdoc}
   */
  public function discover(string $directory): mixed {
    return file_exists($this->join($directory, $this->path));
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return ['exists' => $this->path];
  }

}
