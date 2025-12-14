<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for copy_dir() function.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\copy_dir')]
#[Group('helpers')]
class HelpersCopyDirTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderCopyDir')]
  public function testCopyDir(array $structure, array $expected_files): void {
    $src = self::$tmp . '/src_' . uniqid();
    $dst = self::$tmp . '/dst_' . uniqid();

    // Create source directory structure.
    $this->createDirectoryStructure($src, $structure);

    // Perform copy.
    \DrevOps\VortexTooling\copy_dir($src, $dst);

    // Assert destination exists.
    $this->assertDirectoryExists($dst);

    // Assert all expected files exist with correct content.
    foreach ($expected_files as $relative_path => $expected_content) {
      $full_path = $dst . '/' . $relative_path;
      if ($expected_content === NULL) {
        // NULL means it's a directory.
        $this->assertDirectoryExists($full_path, sprintf('Directory %s should exist', $relative_path));
      }
      else {
        $this->assertFileExists($full_path, sprintf('File %s should exist', $relative_path));
        $this->assertEquals($expected_content, file_get_contents($full_path), sprintf('File %s should have correct content', $relative_path));
      }
    }
  }

  public static function dataProviderCopyDir(): array {
    return [
      'single file' => [
        'structure' => [
          'file.txt' => 'content',
        ],
        'expected_files' => [
          'file.txt' => 'content',
        ],
      ],
      'multiple files' => [
        'structure' => [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'file3.txt' => 'content3',
        ],
        'expected_files' => [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'file3.txt' => 'content3',
        ],
      ],
      'nested directories' => [
        'structure' => [
          'subdir' => [
            'nested.txt' => 'nested content',
          ],
        ],
        'expected_files' => [
          'subdir' => NULL,
          'subdir/nested.txt' => 'nested content',
        ],
      ],
      'deeply nested directories' => [
        'structure' => [
          'level1' => [
            'level2' => [
              'level3' => [
                'deep.txt' => 'deep content',
              ],
            ],
          ],
        ],
        'expected_files' => [
          'level1' => NULL,
          'level1/level2' => NULL,
          'level1/level2/level3' => NULL,
          'level1/level2/level3/deep.txt' => 'deep content',
        ],
      ],
      'mixed files and directories' => [
        'structure' => [
          'root.txt' => 'root content',
          'subdir1' => [
            'file1.txt' => 'sub1 content',
          ],
          'subdir2' => [
            'file2.txt' => 'sub2 content',
            'nested' => [
              'file3.txt' => 'nested content',
            ],
          ],
        ],
        'expected_files' => [
          'root.txt' => 'root content',
          'subdir1' => NULL,
          'subdir1/file1.txt' => 'sub1 content',
          'subdir2' => NULL,
          'subdir2/file2.txt' => 'sub2 content',
          'subdir2/nested' => NULL,
          'subdir2/nested/file3.txt' => 'nested content',
        ],
      ],
      'empty directory' => [
        'structure' => [],
        'expected_files' => [],
      ],
      'directory with empty subdirectory' => [
        'structure' => [
          'file.txt' => 'content',
          'empty_dir' => [],
        ],
        'expected_files' => [
          'file.txt' => 'content',
          'empty_dir' => NULL,
        ],
      ],
    ];
  }

  public function testCopyDirDestinationAlreadyExists(): void {
    $src = self::$tmp . '/src_' . uniqid();
    $dst = self::$tmp . '/dst_' . uniqid();

    // Create source with files.
    $this->createDirectoryStructure($src, [
      'new_file.txt' => 'new content',
    ]);

    // Create destination with existing file.
    mkdir($dst, 0755, TRUE);
    file_put_contents($dst . '/existing.txt', 'existing content');

    // Perform copy.
    \DrevOps\VortexTooling\copy_dir($src, $dst);

    // Assert both files exist.
    $this->assertFileExists($dst . '/new_file.txt');
    $this->assertEquals('new content', file_get_contents($dst . '/new_file.txt'));
    $this->assertFileExists($dst . '/existing.txt');
    $this->assertEquals('existing content', file_get_contents($dst . '/existing.txt'));
  }

  public function testCopyDirOverwritesExistingFile(): void {
    $src = self::$tmp . '/src_' . uniqid();
    $dst = self::$tmp . '/dst_' . uniqid();

    // Create source with file.
    $this->createDirectoryStructure($src, [
      'file.txt' => 'new content',
    ]);

    // Create destination with same file but different content.
    mkdir($dst, 0755, TRUE);
    file_put_contents($dst . '/file.txt', 'old content');

    // Perform copy.
    \DrevOps\VortexTooling\copy_dir($src, $dst);

    // Assert file was overwritten.
    $this->assertEquals('new content', file_get_contents($dst . '/file.txt'));
  }

  protected function createDirectoryStructure(string $base_path, array $structure): void {
    if (!is_dir($base_path)) {
      mkdir($base_path, 0755, TRUE);
    }

    foreach ($structure as $name => $content) {
      $path = $base_path . '/' . $name;
      if (is_array($content)) {
        // It's a directory.
        mkdir($path, 0755, TRUE);
        $this->createDirectoryStructure($path, $content);
      }
      else {
        // It's a file.
        file_put_contents($path, $content);
      }
    }
  }

}
