<?php

namespace Drevops\Installer\Tests\Traits;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait VfsTrait {

  /**
   * @var vfsStreamDirectory
   */
  protected static $vfsRootDirectory;

  /**
   * Set up the root directory for the virtual file system.
   *
   *
   */
  public static function vfsSetRoot(string $name = 'root'): void {
    self::$vfsRootDirectory = vfsStream::setup($name);
  }

  /**
   * Create a directory under the root.
   *
   *
   * @return vfsStreamDirectory
   * @throws \Exception
   */
  public static function createDirectory(string $path): string {
    $path = static::vfsNormalizePath($path);

    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    return vfsStream::newDirectory($path)->at(static::$vfsRootDirectory)->url();
  }

  public static function createFile($path, $contents = NULL, $permissions = NULL) {
    $path = static::vfsNormalizePath($path);

    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    $file = vfsStream::newFile($path, $permissions)->at(static::$vfsRootDirectory);

    if ($contents) {
      $file->withContent($contents);
    }

    return $file->url();
  }

  protected static function vfsNormalizePath($path): string {
    $prefix = 'vfs://root/';

    if (!str_starts_with((string) $path, $prefix)) {
      throw new \Exception('Fixture path must start with ' . $prefix);
    }

    return substr((string) $path, strlen($prefix));
  }

}
