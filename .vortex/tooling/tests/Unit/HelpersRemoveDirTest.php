<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for remove_dir() function.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\remove_dir')]
#[Group('helpers')]
class HelpersRemoveDirTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderRemoveDir')]
  public function testRemoveDir(array $structure): void {
    $dir = self::$tmp . '/remove_' . uniqid();

    $this->createDirectoryStructure($dir, $structure);
    $this->assertDirectoryExists($dir);

    \DrevOps\VortexTooling\remove_dir($dir);

    $this->assertDirectoryDoesNotExist($dir);
  }

  public static function dataProviderRemoveDir(): array {
    return [
      'empty directory' => [
        'structure' => [],
      ],
      'single file' => [
        'structure' => [
          'file.txt' => 'content',
        ],
      ],
      'nested directories' => [
        'structure' => [
          'root.txt' => 'root content',
          'subdir1' => [
            'file1.txt' => 'sub1 content',
          ],
          'subdir2' => [
            'nested' => [
              'file2.txt' => 'nested content',
              'deep' => [
                'file3.txt' => 'deep content',
              ],
            ],
          ],
        ],
      ],
      'empty subdirectories' => [
        'structure' => [
          'empty1' => [],
          'empty2' => [
            'empty3' => [],
          ],
        ],
      ],
      'hidden files' => [
        'structure' => [
          '.hidden' => 'hidden content',
          '.hidden_dir' => [
            '.another' => 'content',
          ],
        ],
      ],
    ];
  }

  public function testRemoveDirMissingPathIsNoop(): void {
    $dir = self::$tmp . '/missing_' . uniqid();

    \DrevOps\VortexTooling\remove_dir($dir);

    $this->assertDirectoryDoesNotExist($dir);
  }

  public function testRemoveDirPathIsFile(): void {
    $file = self::$tmp . '/file_' . uniqid();
    file_put_contents($file, 'content');

    // A plain file is not a directory: the call is a no-op and the file stays.
    \DrevOps\VortexTooling\remove_dir($file);

    $this->assertFileExists($file);
    unlink($file);
  }

  public function testRemoveDirDoesNotFollowSymlinks(): void {
    $target = self::$tmp . '/target_' . uniqid();
    $dir = self::$tmp . '/remove_' . uniqid();

    $this->createDirectoryStructure($target, ['kept.txt' => 'must survive']);
    $this->createDirectoryStructure($dir, ['file.txt' => 'content']);
    symlink($target, $dir . '/link_to_dir');
    symlink($target . '/kept.txt', $dir . '/link_to_file');

    \DrevOps\VortexTooling\remove_dir($dir);

    $this->assertDirectoryDoesNotExist($dir);
    $this->assertFileExists($target . '/kept.txt');
    $this->assertEquals('must survive', file_get_contents($target . '/kept.txt'));
  }

  public function testRemoveDirPathIsSymlink(): void {
    $target = self::$tmp . '/target_' . uniqid();
    $link = self::$tmp . '/link_' . uniqid();

    $this->createDirectoryStructure($target, ['kept.txt' => 'must survive']);
    symlink($target, $link);

    // The link itself is removed; the tree it points to survives.
    \DrevOps\VortexTooling\remove_dir($link);

    $this->assertFalse(is_link($link));
    $this->assertFileExists($target . '/kept.txt');
  }

  public function testRemoveDirUnreadableSubdirectoryDoesNotThrow(): void {
    $dir = self::$tmp . '/remove_' . uniqid();

    $this->createDirectoryStructure($dir, ['unreadable' => ['file.txt' => 'content']]);
    chmod($dir . '/unreadable', 0000);

    try {
      // Must not throw: an unreadable subdirectory is skipped, not fatal.
      \DrevOps\VortexTooling\remove_dir($dir);
    }
    finally {
      if (is_dir($dir . '/unreadable')) {
        chmod($dir . '/unreadable', 0755);
      }
      \DrevOps\VortexTooling\remove_dir($dir);
    }

    $this->assertDirectoryDoesNotExist($dir);
  }

  public function testRemoveDirSuppressesFailures(): void {
    $dir = self::$tmp . '/remove_' . uniqid();

    $this->createDirectoryStructure($dir, ['locked' => ['file.txt' => 'content']]);
    chmod($dir . '/locked', 0555);

    try {
      // Must not throw: failures are suppressed, mirroring 'rm -rf'.
      \DrevOps\VortexTooling\remove_dir($dir);

      $this->assertFileExists($dir . '/locked/file.txt');
    }
    finally {
      chmod($dir . '/locked', 0755);
      \DrevOps\VortexTooling\remove_dir($dir);
    }

    $this->assertDirectoryDoesNotExist($dir);
  }

}
