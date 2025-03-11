<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\FileDiff;

/**
 * Class InstallerCopyRecursiveTest.
 *
 * InstallerCopyRecursiveTest fixture class.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\FileDiff
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class FileDiffTest extends UnitTestBase {

  /**
   * @dataProvider dataProviderCompare
   * @covers ::compare
   */
  public function testCompare(array $expected_diffs = []): void {
    $dir1 = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'directory1');
    $dir2 = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'directory2');

    $diff = FileDiff::compare($dir1, $dir2);

    $this->assertEquals($expected_diffs['absent_dir1'] ?? [], array_keys($diff['absent_dir1']));
    $this->assertEquals($expected_diffs['absent_dir2'] ?? [], array_keys($diff['absent_dir2']));
    $this->assertEquals($expected_diffs['content'] ?? [], $diff['content']);
  }

  public static function dataProviderCompare(): array {
    return [
      'files_equal' => [],
      'files_not_equal' => [
        [
          'absent_dir1' => [
            'dir2_flat-present-dst/d2f1.txt',
            'dir2_flat-present-dst/d2f2.txt',
            'dir3_subdirs/dir31/f4-new-file-notignore-everywhere.txt',
            'dir5_content_ignore/dir51/d51f2-new-file.txt',
            'f4-new-file-notignore-everywhere.txt',
          ],
          'absent_dir2' => [
            'd32f2_symlink_deep.txt',
            'dir1_flat/d1f1_symlink.txt',
            'dir1_flat/d1f3-only-src.txt',
            'dir3_subdirs/dir32-unignored/d32f1_symlink.txt',
            'dir3_subdirs_symlink',
            'f2_symlink.txt',
          ],
          'content' => [
            'dir3_subdirs/dir32-unignored/d32f2.txt' => [
              'dir1' => "d32f2l1\n",
              'dir2' => "d32f2l1-changed\n",
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider dataProviderCompareRendered
   * @covers ::compare
   */
  public function testCompareRendered(array $expected): void {
    $dir1 = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'directory1');
    $dir2 = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'directory2');

    $text = FileDiff::compareRendered($dir1, $dir2);

    if ($expected === []) {
      $this->assertNull($text);
      return;
    }

    foreach ($expected as $expected_line) {
      $this->assertStringContainsString($expected_line, $text);
    }
  }

  public static function dataProviderCompareRendered(): array {
    return [
      'files_equal' => [
        [],
      ],
      'files_not_equal' => [
        [
          'Differences between directories',
          "Files absent in dir1:\n",
          "dir2_flat-present-dst/d2f1.txt\n",
          "dir2_flat-present-dst/d2f2.txt\n",
          "dir3_subdirs/dir31/f4-new-file-notignore-everywhere.txt\n",
          "dir5_content_ignore/dir51/d51f2-new-file.txt\n",
          "f4-new-file-notignore-everywhere.txt\n",
          "Files that differ in content:\n",
          "dir3_subdirs/dir32-unignored/d32f2.txt\n",
          "--- DIFF START ---\n",
          "@@ -1 +1 @@\n",
          "-d32f2l1\n",
          "+d32f2l1-changed\n",
          "--- DIFF END ---\n",
        ],
      ],
    ];
  }

}
