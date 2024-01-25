<?php

namespace Drevops\Installer\Tests\Traits;

/**
 *
 */
trait FixturesTrait {

  use VfsTrait;

  /**
   * Array of fixture destination directories in different states.
   *
   * @var array
   *   Array of fixture destination directories in different states with names
   *   as keys and absolute paths as values.
   */
  public static $fixtureDstDirs = [
    'empty' => 'vfs://root/src_empty',
    'installed' => 'vfs://root/src_installed',
  ];

  protected static function fixturesPrepare() {
    static::fixturesCreateDirs(static::$fixtureDstDirs);
  }

  /**
   * Create fixture directories.
   *
   * @param array $dirs
   *   Array of directories to create.
   *
   * @throws \Exception
   */
  protected static function fixturesCreateDirs($dirs) {
    foreach ($dirs as $name => $dir) {
      static::createDirectory($dir);

      // Special case to prepare a directory with installed files.
      if (str_starts_with($name, 'installed')) {
        static::fixturesCreateReadme($dir);
      }
    }
  }

  protected static function fixturesCreateReadme(string $dir) {
    return static::createFile($dir . DIRECTORY_SEPARATOR . 'README.md', 'badge/DrevOps-');
  }

  protected static function fixturesCreateComposerjson(string $dir, $values) {
    // Convert the values to a JSON string with pretty print.
    $json = json_encode($values, JSON_PRETTY_PRINT);

    // If there was an error encoding the JSON data.
    if ($json === FALSE) {
      throw new \RuntimeException('Error encoding values to JSON');
    }

    return static::createFile($dir . '/composer.json', $json);
  }

}
