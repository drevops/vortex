<?php

declare(strict_types=1);

namespace Drevops\Installer\Tests\Unit;

use DrevOps\Installer\File;
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
    // Using createTempdir() from the install file itself.
    $this->fixtureDir = File::createTempdir();
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
   *
   * @param array $files
   *   Files to create.
   * @param string|null $basedir
   *   Base directory.
   * @param bool $append_rand
   *   Append random number to the file name.
   *
   * @return string[]
   *   Created file names.
   */
  protected function createFixtureFiles(array $files, ?string $basedir = NULL, bool $append_rand = TRUE): array {
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
  protected function getFixtureDir($name = NULL): string {
    $parent = dirname(__FILE__);
    $path = $parent . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
    $path .= $name ? DIRECTORY_SEPARATOR . $name : '';
    if (!file_exists($path)) {
      throw new \RuntimeException(sprintf('Unable to find fixture directory at path "%s".', $path));
    }

    return $path;
  }

}
