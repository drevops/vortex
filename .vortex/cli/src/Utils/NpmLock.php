<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

/**
 * Reconcile an npm lock file with the manifest next to it.
 */
class NpmLock {

  /**
   * Name of the lock file sitting next to a manifest.
   */
  const FILE = 'package-lock.json';

  /**
   * Manifest keys that declare edges to other packages.
   */
  const BLOCKS = [
    'dependencies',
    'devDependencies',
    'optionalDependencies',
    'peerDependencies',
  ];

  /**
   * Bring the lock file next to a manifest back in line with it.
   *
   * The lock's root entry is reconciled with the manifest's dependency blocks
   * and every package no longer reachable from a root is dropped.
   *
   * @param string $manifest_file
   *   Path to the "package.json" file.
   *
   * @throws \RuntimeException
   *   If either file holds invalid JSON, if a manifest dependency block is not
   *   a JSON object, if the lock file carries no "packages" map, or if the lock
   *   file cannot be written back.
   */
  public static function sync(string $manifest_file): void {
    $lock_file = dirname($manifest_file) . DIRECTORY_SEPARATOR . self::FILE;

    if (!is_file($lock_file)) {
      return;
    }

    $manifest = self::read($manifest_file);
    $lock = self::read($lock_file);

    if (!isset($lock->packages) || !isset($lock->packages->{''})) {
      throw new \RuntimeException(sprintf('Unsupported lock file format at "%s": a "packages" entry is required.', $lock_file));
    }

    self::reconcileRoot($manifest, $lock->packages->{''}, $manifest_file);
    self::prune($lock->packages);

    self::write($lock_file, $lock);
  }

  /**
   * Copy the manifest's dependency blocks onto the lock's root entry.
   */
  protected static function reconcileRoot(\stdClass $manifest, \stdClass $root, string $manifest_file): void {
    foreach (self::BLOCKS as $block) {
      if (!isset($manifest->{$block})) {
        unset($root->{$block});

        continue;
      }

      if (!$manifest->{$block} instanceof \stdClass) {
        throw new \RuntimeException(sprintf('Unable to read the "%s" block of "%s": a JSON object is required.', $block, $manifest_file));
      }

      $root->{$block} = clone $manifest->{$block};
    }
  }

  /**
   * Remove installed packages that no roots reach any more.
   */
  protected static function prune(\stdClass $packages): void {
    $paths = array_keys(get_object_vars($packages));
    $queue = array_values(array_filter($paths, fn(string $path): bool => !str_starts_with($path, 'node_modules/')));
    $reachable = [];

    while ($queue !== []) {
      $path = array_shift($queue);

      if (isset($reachable[$path])) {
        continue;
      }

      $reachable[$path] = TRUE;

      foreach (self::edges($packages->{$path}) as $name) {
        $resolved = self::resolve($packages, $path, $name);

        if ($resolved !== NULL) {
          $queue[] = $resolved;
        }
      }
    }

    foreach ($paths as $path) {
      if (!isset($reachable[$path])) {
        unset($packages->{$path});
      }
    }
  }

  /**
   * Collect the names a lock entry depends on.
   *
   * @return array<int, string>
   *   Package names.
   */
  protected static function edges(\stdClass $entry): array {
    $names = [];

    foreach (self::BLOCKS as $block) {
      if (isset($entry->{$block})) {
        $names = array_merge($names, array_keys(get_object_vars($entry->{$block})));
      }
    }

    return $names;
  }

  /**
   * Find the entry that satisfies a name for a package at the given path.
   *
   * Node walks the "node_modules" directories from the requiring package
   * outwards, so the innermost copy wins over a hoisted one.
   *
   * @return string|null
   *   The path of the satisfying entry, or NULL when the tree has none.
   */
  protected static function resolve(\stdClass $packages, string $from, string $name): ?string {
    $scope = $from;

    while (TRUE) {
      $candidate = ($scope === '' ? '' : $scope . '/') . 'node_modules/' . $name;

      if (isset($packages->{$candidate})) {
        return $candidate;
      }

      if ($scope === '') {
        return NULL;
      }

      $position = strrpos($scope, '/node_modules/');
      $scope = $position === FALSE ? '' : substr($scope, 0, $position);
    }
  }

  /**
   * Decode a JSON file into objects.
   */
  protected static function read(string $file): \stdClass {
    $contents = file_get_contents($file);

    if ($contents === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Unable to read a JSON file at "%s".', $file));
      // @codeCoverageIgnoreEnd
    }

    try {
      $decoded = json_decode($contents, FALSE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new \RuntimeException(sprintf('Unable to parse a JSON file at "%s": %s', $file, $exception->getMessage()), $exception->getCode(), $exception);
    }

    if (!$decoded instanceof \stdClass) {
      throw new \RuntimeException(sprintf('Unable to parse a JSON file at "%s": a JSON object is required.', $file));
    }

    return $decoded;
  }

  /**
   * Write a JSON file the way npm writes it.
   */
  protected static function write(string $file, \stdClass $data): void {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // Two-space indentation matches what npm writes; PHP indents with four. A
    // JSON string cannot hold a raw newline, so a leading run of spaces is
    // always indentation.
    $json = preg_replace_callback('/^ +/m', fn(array $matches): string => str_repeat(' ', intdiv(strlen($matches[0]), 2)), $json);

    if ($json === NULL) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Unable to format a JSON file at "%s".', $file));
      // @codeCoverageIgnoreEnd
    }

    $json .= "\n";

    if (!is_writable($file) || file_put_contents($file, $json) !== strlen($json)) {
      throw new \RuntimeException(sprintf('Unable to write a JSON file at "%s".', $file));
    }
  }

}
