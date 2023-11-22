<?php

namespace DrevOps\DevTool\Tests\Traits;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;

/**
 * Trait VfsTrait.
 *
 * Provides methods for working with the virtual file system.
 *
 * @code
 *
 * protected function setUp(): void {
 *   $this->testPath = $this->vfsCreateDirectory('somedir')->url();
 *   $this->vfsCreateFile($this->testPath . '/file1');
 *   $this->vfsCreateFile($this->testPath . '/file2');
 * }
 *
 * public function testSomething() {
 *  // The full path will start with 'vfs://' stream wrapper.
 *  $this->assertFileExists($this->testPath . '/file1');
 *  $this->assertFileExists($this->testPath . '/file2');
 * }
 *
 * @endcode
 */
trait VfsTrait {

  /**
   * The root directory for the virtual file system.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected static $vfsRootDirectory;

  /**
   * Set up the root directory for the virtual file system.
   *
   * @param string $name
   *   The name of the root directory.
   */
  public static function vfsSetRoot(string $name = 'root'): void {
    self::$vfsRootDirectory = vfsStream::setup($name);
  }

  /**
   * Create a directory.
   *
   * @param string $path
   *   The path to the directory.
   *
   * @return \org\bovigo\vfs\vfsStreamDirectory
   *   The directory container.
   */
  public static function vfsCreateDirectory(string $path): vfsStreamDirectory {
    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    $path = static::vfsNormalizePath($path);

    $dirs = explode('/', $path);
    $container = static::$vfsRootDirectory;
    foreach ($dirs as $dir) {
      if (!$container->hasChild($dir)) {
        $container = vfsStream::newDirectory($dir)->at($container);
      }
      else {
        $container = $container->getChild($dir);
      }
    }

    return $container;
  }

  /**
   * Create a file.
   *
   * @param string $path
   *   The path to the file.
   * @param string|null $contents
   *   The contents of the file.
   * @param int|null $permissions
   *   The permissions of the file.
   *
   * @return \org\bovigo\vfs\vfsStreamFile
   *   The file container.
   */
  public static function vfsCreateFile($path, $contents = NULL, $permissions = NULL): vfsStreamFile {
    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    $path = static::vfsNormalizePath($path);

    $dirs = explode('/', $path);
    $filename = array_pop($dirs);
    $container = self::vfsCreateDirectory(implode('/', $dirs));

    $container = vfsStream::newFile($filename, $permissions)->at($container);

    if ($contents) {
      $container->withContent($contents);
    }

    return $container;
  }

  protected static function vfsNormalizePath(string $path): string {
    $prefix = static::$vfsRootDirectory ? static::$vfsRootDirectory->url() . '/' : 'vfs://root/';

    if (str_starts_with($path, $prefix)) {
      $path = substr($path, strlen($prefix));
    }

    return $path;
  }

}
