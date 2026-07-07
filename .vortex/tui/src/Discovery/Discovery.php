<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Evaluates config-declared discovery shortcuts against a project directory.
 *
 * A discovery rule is one of: `{dotenv: KEY}` (read a key from `.env`),
 * `{json: {file, path}}` (read a dot-path from a JSON file), `{exists: PATH}`
 * (whether a path exists), or `{scan: {dir, type}}` (list directory entries).
 * These cover the simple cases without a project-specific handler; anything
 * richer belongs in a handler's discover().
 *
 * @package DrevOps\Tui\Discovery
 */
class Discovery {

  /**
   * Detect a value for a discovery rule within a directory.
   *
   * @param array<array-key,mixed> $rule
   *   The discovery rule.
   * @param string $directory
   *   The project directory to inspect.
   *
   * @return mixed
   *   The detected value, or NULL when nothing was found.
   */
  public function detect(array $rule, string $directory): mixed {
    if (isset($rule['dotenv']) && is_scalar($rule['dotenv'])) {
      return $this->dotenv($directory, (string) $rule['dotenv']);
    }

    if (isset($rule['json']) && is_array($rule['json'])) {
      return $this->json($directory, $rule['json']);
    }

    if (isset($rule['exists']) && is_scalar($rule['exists'])) {
      return $this->exists($directory, (string) $rule['exists']);
    }

    if (isset($rule['scan']) && is_array($rule['scan'])) {
      return $this->scan($directory, $rule['scan']);
    }

    return NULL;
  }

  /**
   * Read a key from a `.env` file.
   *
   * @param string $directory
   *   The project directory.
   * @param string $key
   *   The env key.
   *
   * @return string|null
   *   The value, or NULL when absent.
   */
  protected function dotenv(string $directory, string $key): ?string {
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
      if (trim(substr($line, 0, $pos)) !== $key) {
        continue;
      }

      return trim(trim(substr($line, $pos + 1)), '"\'');
    }

    return NULL;
  }

  /**
   * Read a dot-path from a JSON file.
   *
   * @param string $directory
   *   The project directory.
   * @param array<array-key,mixed> $rule
   *   The rule with `file` and `path`.
   *
   * @return mixed
   *   The scalar value at the path, or NULL.
   */
  protected function json(string $directory, array $rule): mixed {
    $file = isset($rule['file']) && is_scalar($rule['file']) ? (string) $rule['file'] : '';
    $path = isset($rule['path']) && is_scalar($rule['path']) ? (string) $rule['path'] : '';
    if ($file === '') {
      return NULL;
    }

    $full = $this->join($directory, $file);
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

    return is_array($data) ? $this->traverse($data, $path) : NULL;
  }

  /**
   * Traverse a decoded structure along a dot-path.
   *
   * @param array<array-key,mixed> $data
   *   The decoded data.
   * @param string $path
   *   The dot-path.
   *
   * @return mixed
   *   The scalar value at the path, or NULL.
   */
  protected function traverse(array $data, string $path): mixed {
    if ($path === '') {
      return NULL;
    }

    $cursor = $data;
    foreach (explode('.', $path) as $segment) {
      if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
        return NULL;
      }

      $cursor = $cursor[$segment];
    }

    return is_scalar($cursor) ? $cursor : NULL;
  }

  /**
   * Whether a path exists within the directory.
   *
   * @param string $directory
   *   The project directory.
   * @param string $relative
   *   The relative path.
   *
   * @return bool
   *   TRUE when the path exists.
   */
  protected function exists(string $directory, string $relative): bool {
    return file_exists($this->join($directory, $relative));
  }

  /**
   * List the entries of a directory, optionally filtered by type.
   *
   * @param string $directory
   *   The project directory.
   * @param array<array-key,mixed> $rule
   *   The rule with `dir` and optional `type` (dir / file / any).
   *
   * @return list<string>
   *   The sorted entry names.
   */
  protected function scan(string $directory, array $rule): array {
    $dir = isset($rule['dir']) && is_scalar($rule['dir']) ? (string) $rule['dir'] : '';
    $type = isset($rule['type']) && is_scalar($rule['type']) ? (string) $rule['type'] : 'any';
    $full = $this->join($directory, $dir);
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
      if ($type === 'dir' && !is_dir($path)) {
        continue;
      }
      if ($type === 'file' && !is_file($path)) {
        continue;
      }

      $out[] = $entry;
    }

    sort($out);

    return $out;
  }

  /**
   * Join a directory and a relative path.
   *
   * @param string $directory
   *   The base directory.
   * @param string $relative
   *   The relative path.
   *
   * @return string
   *   The joined path.
   */
  protected function join(string $directory, string $relative): string {
    return rtrim($directory, '/') . '/' . ltrim($relative, '/');
  }

}
