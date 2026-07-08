<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Discovers a scalar by reading a dot-path from a JSON file.
 *
 * @package DrevOps\Tui\Discovery
 */
class JsonValue extends AbstractDiscover {

  /**
   * Construct a JSON discovery rule.
   *
   * @param string $file
   *   The JSON file, relative to the project directory.
   * @param string $path
   *   The dot-path to the scalar value (e.g. "extra.drupal.webroot").
   */
  public function __construct(public readonly string $file, public readonly string $path) {
  }

  /**
   * {@inheritdoc}
   */
  public function discover(string $directory): mixed {
    if ($this->file === '' || $this->path === '') {
      return NULL;
    }

    $full = $this->join($directory, $this->file);

    if (!is_file($full)) {
      return NULL;
    }

    $contents = file_get_contents($full);
    // @codeCoverageIgnoreStart
    if ($contents === FALSE) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    $data = json_decode($contents, TRUE);

    return is_array($data) ? $this->traverse($data) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    return ['json' => ['file' => $this->file, 'path' => $this->path]];
  }

  /**
   * Traverse a decoded structure along the dot-path.
   *
   * @param array<array-key,mixed> $data
   *   The decoded data.
   *
   * @return mixed
   *   The scalar value at the path, or NULL.
   */
  protected function traverse(array $data): mixed {
    $cursor = $data;

    foreach (explode('.', $this->path) as $segment) {
      if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
        return NULL;
      }

      $cursor = $cursor[$segment];
    }

    return is_scalar($cursor) ? $cursor : NULL;
  }

}
