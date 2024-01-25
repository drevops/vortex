<?php

namespace Drevops\Installer\Tests\Unit;

use DrevOps\Installer\Tests\Traits\ClosureWrapperTrait;
use Drevops\Installer\Tests\Traits\TestHelperTrait;
use DrevOps\Installer\Utils\Files;
use Drevops\Installer\Tests\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestBase extends TestCase {

  use ClosureWrapperTrait;
  use ReflectionTrait;

  /**
   * Fixture directory.
   *
   * @var string
   */
  protected $fixtureDir;

  /**
   * Prepare fixture directory.
   */
  public function prepareFixtureDir(): void {
    // Using tempdir() from the install file itself.
    $this->fixtureDir = Files::tempdir();
  }

  /**
   * Cleanup fixture directory.
   */
  public function cleanupFixtureDir(): void {
    $this->fileExists();
    $fs = new Filesystem();
    $fs->remove($this->fixtureDir);
  }

  /**
   * Create fixture files.
   */
  protected function createFixtureFiles($files, $basedir = NULL, $append_rand = TRUE) {
    $fs = new Filesystem();
    $created = [];
    foreach ($files as $file) {
      $basedir = $basedir ?? dirname((string) $file);
      $relative_dst = ltrim(str_replace($basedir, '', (string) $file), '/') . ($append_rand ? rand(1000, 9999) : '');
      $new_name = $this->fixtureDir . DIRECTORY_SEPARATOR . $relative_dst;
      $fs->copy($file, $new_name);
      $created[] = $new_name;
    }

    return $created;
  }

  /**
   * Get fixture directory.
   *
   * @param string|null $name
   *   Fixture directory name.
   *
   * @return string
   *   Fixture directory path.
   */
  protected function getFixtureDir($name = NULL) {
    $parent = dirname(__FILE__);
    $path = $parent . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
    $path .= $name ? DIRECTORY_SEPARATOR . $name : '';
    if (!file_exists($path)) {
      throw new \RuntimeException(sprintf('Unable to find fixture directory at path "%s".', $path));
    }

    return $path;
  }

  protected function flattenFileTree($tree, string $parent = '.') {
    $flatten = [];
    foreach ($tree as $dir => $file) {
      if (is_array($file)) {
        $flatten = array_merge($flatten, $this->flattenFileTree($file, $parent . DIRECTORY_SEPARATOR . $dir));
      }
      else {
        $flatten[] = $parent . DIRECTORY_SEPARATOR . $file;
      }
    }

    return $flatten;
  }

}
