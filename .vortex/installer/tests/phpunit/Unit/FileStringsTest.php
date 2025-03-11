<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\File;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass \DrevOps\Installer\Utils\File
 * @group file
 */
class FileStringsTest extends UnitTestBase {

  /**
   * @covers ::copy
   */
  public function testCopy(): void {
    static::$fixtures = $this->locationsFixtureDir();
    File::copy(static::$fixtures, static::$sut);

    $dir = static::$sut . DIRECTORY_SEPARATOR;

    $this->assertTrue(is_file($dir . 'file.txt'));
    $this->assertTrue((fileperms($dir . 'file.txt') & 0777) === 0755);
    $this->assertTrue(is_dir($dir . 'dir'));
    $this->assertTrue(is_file($dir . 'dir/file_in_dir.txt'));
    $this->assertTrue(is_dir($dir . 'dir/subdir'));
    $this->assertTrue(is_file($dir . 'dir/subdir/file_in_subdir.txt'));

    $this->assertTrue(is_link($dir . 'file_link.txt'));

    $this->assertTrue(is_link($dir . 'dir_link'));
    $this->assertTrue(is_dir($dir . 'dir_link/subdir'));
    $this->assertTrue(is_file($dir . 'dir_link/subdir/file_in_subdir.txt'));
    $this->assertTrue(is_link($dir . 'dir_link/subdir/file_link_from_subdir.txt'));

    $this->assertTrue(is_link($dir . 'subdir_link_root'));
    $this->assertTrue(is_link($dir . 'subdir_link_root/file_link_from_subdir.txt'));
    $this->assertTrue((fileperms($dir . 'subdir_link_root/file_link_from_subdir.txt') & 0777) === 0755);
    $this->assertTrue(is_file($dir . 'subdir_link_root/file_in_subdir.txt'));

    $this->assertTrue(is_link($dir . 'dir/subdir_link'));
    $this->assertTrue(is_dir($dir . 'dir/subdir_link'));

    $this->assertDirectoryDoesNotExist($dir . 'emptydir');
  }

  /**
   * @dataProvider dataProviderContains
   * @covers ::contains
   */
  public function testContains(string $string, string $file, mixed $expected): void {
    $dir = $this->locationsFixtureDir('tokens');

    $files = $this->flattenFileTree([$file], $dir);
    $created_files = static::locationsCopyFilesToSut($files, $dir);
    $created_file = reset($created_files);

    if (empty($created_file) || !file_exists($created_file)) {
      throw new \RuntimeException('File does not exist.');
    }

    $actual = File::contains($created_file, $string);

    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderContains(): array {
    return [
      ['FOO', 'empty.txt', FALSE],
      ['BAR', 'foobar_b.txt', TRUE],
      ['FOO', 'dir1/foobar_b.txt', TRUE],
      ['BAR', 'dir1/foobar_b.txt', TRUE],
      // Regex.
      ['/BA/', 'dir1/foobar_b.txt', TRUE],
      ['/BAW/', 'dir1/foobar_b.txt', FALSE],
      ['/BA.*/', 'dir1/foobar_b.txt', TRUE],
    ];
  }

  /**
   * @dataProvider dataProviderContainsInDir
   * @covers ::containsInDir
   * @covers ::ignoredPaths
   * @covers ::internalPaths
   */
  public function testContainsInDir(string $string, array $files, array $excluded, array $expected): void {
    $dir = $this->locationsFixtureDir('tokens');

    $files = $this->flattenFileTree($files, $dir);
    static::locationsCopyFilesToSut($files, $dir, FALSE);

    $actual = File::containsInDir(static::$sut, $string, $excluded);

    $this->assertEquals(count($expected), count($actual));
    foreach ($actual as $path) {
      $path = str_replace(static::$sut . DIRECTORY_SEPARATOR, '', $path);
      $this->assertContains($path, $expected);
    }
  }

  public static function dataProviderContainsInDir(): array {
    return [
      ['FOO', ['empty.txt'], [], []],
      ['BAR', ['foobar_b.txt'], [], ['foobar_b.txt']],
      ['FOO', ['dir1/foobar_b.txt'], [], ['dir1/foobar_b.txt']],
      ['FOO', ['dir1/foobar_b.txt'], ['dir1'], []],
      ['BAR', ['dir1/foobar_b.txt'], [], ['dir1/foobar_b.txt']],

      // Regex.
      ['/BA/', ['dir1/foobar_b.txt'], [], ['dir1/foobar_b.txt']],
      ['/BA/', ['dir1/foobar_b.txt'], ['dir1'], []],
      ['/BAW/', ['dir1/foobar_b.txt'], [], []],
      ['/BA.*/', ['dir1/foobar_b.txt'], [], ['dir1/foobar_b.txt']],
    ];
  }

  /**
   * @dataProvider dataProviderRemoveTokenInDir
   * @covers ::removeTokenInDir
   * @covers ::scandirRecursive
   * @covers ::ignoredPaths
   */
  public function testRemoveTokenInDir(?string $token): void {
    $dir = $this->locationsFixtureDir('tokens_remove_dir') . DIRECTORY_SEPARATOR . 'before';
    (new Filesystem())->mirror($dir, static::$sut);
    static::$fixtures = $this->locationsFixtureDir('tokens_remove_dir') . DIRECTORY_SEPARATOR . 'after';

    File::removeTokenInDir(static::$sut, $token);

    $this->assertDirectoriesEqual(static::$fixtures, static::$sut);
  }

  public static function dataProviderRemoveTokenInDir(): array {
    return [
      'with_content_foo' => ['FOO'],
      'without_content_notoken' => [NULL],
    ];
  }

  /**
   * @dataProvider dataProviderRemoveToken
   * @covers ::removeToken
   */
  public function testRemoveToken(string $file, string $begin, string $end, bool $with_content, bool $expect_exception, string $expected_file): void {
    $dir = $this->locationsFixtureDir('tokens');

    $expected_files = $this->flattenFileTree([$expected_file], $dir);
    $expected_file = reset($expected_files);

    $files = $this->flattenFileTree([$file], $dir);

    $sut_files = static::locationsCopyFilesToSut($files, $dir);
    $sut_file = reset($sut_files);

    if (empty($sut_file) || !file_exists($sut_file)) {
      throw new \RuntimeException('File does not exist.');
    }

    if ($expect_exception) {
      $this->expectException(\RuntimeException::class);
    }

    File::removeToken($sut_file, $begin, $end, $with_content);

    $this->assertFileEquals($expected_file, $sut_file);
  }

  public static function dataProviderRemoveToken(): array {
    return [
      ['empty.txt', 'FOO', 'FOO', TRUE, FALSE, 'empty.txt'],

      // Different begin and end tokens.
      ['foobar_b.txt', '#;< FOO', '#;> BAR', TRUE, FALSE, 'lines_4.txt'],
      ['foobar_b.txt', '#;< FOO', '#;> BAR', FALSE, FALSE, 'lines_234.txt'],

      ['foobar_m.txt', '#;< FOO', '#;> BAR', TRUE, FALSE, 'lines_14.txt'],
      ['foobar_m.txt', '#;< FOO', '#;> BAR', FALSE, FALSE, 'lines_1234.txt'],

      ['foobar_e.txt', '#;< FOO', '#;> BAR', TRUE, FALSE, 'lines_1.txt'],
      ['foobar_e.txt', '#;< FOO', '#;> BAR', FALSE, FALSE, 'lines_123.txt'],

      // Same begin and end tokens.
      ['foofoo_b.txt', '#;< FOO', '#;> FOO', TRUE, FALSE, 'lines_4.txt'],
      ['foofoo_b.txt', '#;< FOO', '#;> FOO', FALSE, FALSE, 'lines_234.txt'],

      ['foofoo_m.txt', '#;< FOO', '#;> FOO', TRUE, FALSE, 'lines_14.txt'],
      ['foofoo_m.txt', '#;< FOO', '#;> FOO', FALSE, FALSE, 'lines_1234.txt'],

      ['foofoo_e.txt', '#;< FOO', '#;> FOO', TRUE, FALSE, 'lines_1.txt'],
      ['foofoo_e.txt', '#;< FOO', '#;> FOO', FALSE, FALSE, 'lines_123.txt'],

      // Tokens without ending trigger exception.
      ['foobar_b.txt', '#;< FOO', '#;> FOO', TRUE, TRUE, 'lines_4.txt'],
      ['foobar_b.txt', '#;< FOO', '#;> FOO', FALSE, TRUE, 'lines_234.txt'],

      ['foobar_m.txt', '#;< FOO', '#;> FOO', TRUE, TRUE, 'lines_14.txt'],
      ['foobar_m.txt', '#;< FOO', '#;> FOO', FALSE, TRUE, 'lines_1234.txt'],

      ['foobar_e.txt', '#;< FOO', '#;> FOO', TRUE, TRUE, 'lines_1.txt'],
      ['foobar_e.txt', '#;< FOO', '#;> FOO', FALSE, TRUE, 'lines_123.txt'],
    ];
  }

  /**
   * @dataProvider dataProviderReplaceContentInDir
   * @covers     ::replaceContentInDir
   * @covers     ::replaceContent
   * @covers     ::isExcluded
   * @covers     ::dump
   */
  public function testReplaceContentInDir(string $from, string $to, array $fixture_files, array $expected_files): void {
    $dir = $this->locationsFixtureDir('tokens');

    $expected_files = $this->flattenFileTree($expected_files, $dir);

    $fixture_files = $this->flattenFileTree($fixture_files, $dir);
    $sut_files = static::locationsCopyFilesToSut($fixture_files, $dir);
    if (count($sut_files) !== count($expected_files)) {
      throw new \RuntimeException('Provided files number is not equal to expected files number.');
    }

    File::replaceContentInDir(static::$sut, $from, $to);

    foreach (array_keys($sut_files) as $k) {
      $this->assertFileEquals($expected_files[$k], $sut_files[$k]);
    }
  }

  public static function dataProviderReplaceContentInDir(): array {
    return [
      [
        'BAR',
        'FOO',
        ['empty.txt'],
        ['empty.txt'],
      ],
      [
        'BAR',
        'FOO',
        ['foobar_b.txt', 'foobar_m.txt', 'foobar_e.txt'],
        ['foofoo_b.txt', 'foofoo_m.txt', 'foofoo_e.txt'],
      ],
      [
        'BAR',
        'FOO',
        ['dir1/foobar_b.txt'],
        ['dir1/foofoo_b.txt'],
      ],
      [
        '/BAR/',
        'FOO',
        ['dir1/foobar_b.txt'],
        ['dir1/foofoo_b.txt'],
      ],
    ];
  }

  /**
   * @dataProvider dataProviderRemoveLine
   * @covers ::removeLine
   * @covers ::isExcluded
   */
  public function testRemoveLine(string $filename, string $content, string $needle, string $expected): void {
    $file = static::$build . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($file, $content);

    File::removeLine($file, $needle);
    $result = file_get_contents($file);

    $this->assertSame($expected, $result);

    unlink($file);
  }

  public static function dataProviderRemoveLine(): array {
    return [
      'remove single line' => [
        'test.txt',
        "line1\nremove me\nline3\n",
        'remove me',
        "line1\nline3\n",
      ],
      'remove multiple occurrences' => [
        'test.txt',
        "line1\nremove me\nline2\nremove me again\nline3\n",
        'remove me',
        "line1\nline2\nline3\n",
      ],
      'no match (no removal)' => [
        'test.txt',
        "line1\nline2\nline3\n",
        'not in file',
        "line1\nline2\nline3\n",
      ],
      'handle CRLF line endings' => [
        'test.txt',
        "line1\r\nremove me\r\nline3\r\n",
        'remove me',
        "line1\r\nline3\r\n",
      ],
      'handle old Mac line endings (CR)' => [
        'test.txt',
        "line1\rremove me\rline3\r",
        'remove me',
        "line1\rline3\r",
      ],
      'empty file' => [
        'test.txt',
        "",
        'anything',
        "",
      ],
      'excluded file' => [
        'test.png',
        "excluded\nremove me\n",
        'remove me',
        "excluded\nremove me\n",
      ],
    ];
  }

  /**
   * @dataProvider dataProviderRenameInDir
   * @covers    ::renameInDir
   */
  public function testRenameInDir(array $fixture_files, array $expected_files): void {
    $dir = $this->locationsFixtureDir('tokens');

    $expected_files = $this->flattenFileTree($expected_files, static::$sut);

    $fixture_files = $this->flattenFileTree($fixture_files, $dir);
    $sut_files = static::locationsCopyFilesToSut($fixture_files, $dir, FALSE);

    if (count($sut_files) !== count($expected_files)) {
      throw new \RuntimeException('Provided files number is not equal to expected files number.');
    }

    File::renameInDir(static::$sut, 'foo', 'bar');

    foreach (array_keys($expected_files) as $k) {
      $this->assertFileExists($expected_files[$k]);
    }
  }

  public static function dataProviderRenameInDir(): array {
    return [
      [
        ['empty.txt'],
        ['empty.txt'],
      ],
      [
        ['foofoo_b.txt'],
        ['barbar_b.txt'],
      ],
      [
        ['dir1/foofoo_b.txt'],
        ['dir1/barbar_b.txt'],
      ],
      [
        ['foo/foofoo_b.txt'],
        ['bar/barbar_b.txt'],
      ],
    ];
  }

  /**
   * @dataProvider dataProviderIsRegex
   * @covers ::isRegex
   */
  public function testIsRegex(string $value, mixed $expected): void {
    $this->assertEquals($expected, File::isRegex($value));
  }

  public static function dataProviderIsRegex(): array {
    return [
      ['', FALSE],

      // Valid regular expressions.
      ["/^[a-z]$/", TRUE],
      ["#[a-z]*#i", TRUE],

      // Invalid regular expressions (wrong delimiters or syntax).
      ["{\\d+}", FALSE],
      ["(\\d+)", FALSE],
      ["<[A-Z]{3,6}>", FALSE],
      ["^[a-z]$", FALSE],
      ["/[a-z", FALSE],
      ["[a-z]+/", FALSE],
      ["{[a-z]*", FALSE],
      ["(a-z]", FALSE],

      // Edge cases.
      // Valid, but '*' as delimiter would be invalid.
      ["/a*/", TRUE],
      // Empty string.
      ["", FALSE],
      // Just delimiters, no pattern.
      ["//", FALSE],

      ['web/', FALSE],
      ['web\/', FALSE],
      [': web', FALSE],
      ['=web', FALSE],
      ['!web', FALSE],
      ['/web', FALSE],
    ];
  }

  /**
   * @dataProvider dataProviderContentignore
   * @covers ::contentignore
   */
  public function testContentignore(?string $content, array $expected): void {
    $file = static::$sut . DIRECTORY_SEPARATOR . File::IGNORECONTENT;
    if (!is_null($content)) {
      file_put_contents($file, $content);
    }

    $result = File::contentignore($file);
    $this->assertSame($expected, $result);

    unlink($file);
  }

  public static function dataProviderContentignore(): array {
    return [
      'non-existing file' => [
        NULL,
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => [],
        ],
      ],
      'empty file' => [
        '',
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => [],
        ],
      ],
      'only comments' => [
        "# This is a comment\n# Another comment",
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => [],
        ],
      ],
      'include rules' => [
        "!include-this\n!^content-only",
        [
          File::RULE_INCLUDE => ['include-this', 'content-only'],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => [],
        ],
      ],
      'ignore content rules' => [
        "^ignore-content",
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => ['ignore-content'],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => [],
        ],
      ],
      'global rules' => [
        "global-pattern\nanother-pattern",
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => ['global-pattern', 'another-pattern'],
          File::RULE_SKIP => [],
        ],
      ],
      'skip rules' => [
        "some/path/file.txt\nanother/path/",
        [
          File::RULE_INCLUDE => [],
          File::RULE_IGNORE_CONTENT => [],
          File::RULE_GLOBAL => [],
          File::RULE_SKIP => ['some/path/file.txt', 'another/path/'],
        ],
      ],
      'mixed rules' => [
        "!include-this\n!^content-only\n^ignore-content\nsome/path/file.txt\nglobal-pattern",
        [
          File::RULE_INCLUDE => ['include-this', 'content-only'],
          File::RULE_IGNORE_CONTENT => ['ignore-content'],
          File::RULE_GLOBAL => ['global-pattern'],
          File::RULE_SKIP => ['some/path/file.txt'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider dataProviderIsPathMatchesPattern
   * @covers ::isPathMatchesPattern
   */
  public function testIsPathMatchesPattern(string $path, string $pattern, bool $expected): void {
    $result = self::callProtectedMethod(File::class, 'isPathMatchesPattern', [$path, $pattern]);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderIsPathMatchesPattern(): array {
    return [
      // Exact match.
      ['dir/file.txt', 'dir/file.txt', TRUE],

      // Directory match.
      ['dir/subdir/file.txt', 'dir/', TRUE],
      ['otherdir/file.txt', 'dir/', FALSE],

      // Direct child match.
      ['dir/file.txt', 'dir/*', TRUE],
      ['dir/subdir/file.txt', 'dir/*', FALSE],
      ['dir/another.txt', 'dir/*', TRUE],

      // Wildcard match.
      ['dir/file.txt', '*.txt', TRUE],
      ['dir/file.md', '*.txt', FALSE],
    // Should not match nested paths.
      ['dir/nested/file.txt', 'dir/*.txt', FALSE],

      // Pattern with a wildcard in the middle.
      ['dir/abc_file.txt', 'dir/abc_*.txt', TRUE],
      ['dir/xyz_file.txt', 'dir/abc_*.txt', FALSE],

      // Matching subdirectories.
      ['dir/subdir/file.txt', 'dir/subdir/*', TRUE],
      ['dir/anotherdir/file.txt', 'dir/subdir/*', FALSE],

      // Complex fnmatch pattern.
      ['dir/file.txt', 'dir/f*.txt', TRUE],
      ['dir/afile.txt', 'dir/f*.txt', FALSE],
    ];
  }

  /**
   * Flatten file tree.
   *
   * @param array<string|int, string|array> $tree
   *   File tree.
   * @param string $parent
   *   Parent directory.
   *
   * @return array
   *   Flattened file tree.
   */
  protected function flattenFileTree(array $tree, string $parent = '.'): array {
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

  /**
   * @covers ::sync
   */
  public function testSync(): void {
    $src = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'files_equal' . DIRECTORY_SEPARATOR . 'directory2');
    $expected = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'files_equal' . DIRECTORY_SEPARATOR . 'directory1');

    copy($expected . DIRECTORY_SEPARATOR . File::IGNORECONTENT, static::$sut . DIRECTORY_SEPARATOR . File::IGNORECONTENT);

    File::sync($src, static::$sut);

    $this->assertDirectoriesEqual($expected, static::$sut);
  }

  /**
   * @covers ::sync
   */
  public function testSyncFile(): void {
    $this->expectException(\RuntimeException::class);
    $src = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'files_equal' . DIRECTORY_SEPARATOR . 'directory2');

    $dst = static::$sut .= DIRECTORY_SEPARATOR . 'file.txt';
    touch($dst);

    File::sync($src, $dst);
  }

  /**
   * @dataProvider dataProviderList
   * @covers ::list
   */
  public function testList(array $rules, ?callable $before_match_content, array $expected): void {
    $dir = File::dir($this->locationsFixtureDir('compare') . DIRECTORY_SEPARATOR . 'files_equal' . DIRECTORY_SEPARATOR . 'directory2');

    $files = File::list($dir, $rules, $before_match_content);

    $this->assertEquals($expected, array_keys($files));
  }

  public static function dataProviderList(): array {
    $defaults = [
      'd32f2_symlink_deep.txt',
      'dir1_flat/d1f1.txt',
      'dir1_flat/d1f1_symlink.txt',
      'dir1_flat/d1f2.txt',
      'dir2_flat/d2f1.txt',
      'dir2_flat/d2f2.txt',
      'dir3_subdirs/d3f1-ignored.txt',
      'dir3_subdirs/d3f2-ignored.txt',
      'dir3_subdirs/dir31/d31f1-ignored.txt',
      'dir3_subdirs/dir31/d31f2-ignored.txt',
      'dir3_subdirs/dir31/f3-new-file-ignore-everywhere.txt',
      'dir3_subdirs/dir32-unignored/d32f1.txt',
      'dir3_subdirs/dir32-unignored/d32f1_symlink.txt',
      'dir3_subdirs/dir32-unignored/d32f2-ignore-ext-only-dst.log',
      'dir3_subdirs/dir32-unignored/d32f2.txt',
      'dir3_subdirs/f3-new-file-ignore-everywhere.txt',
      'dir3_subdirs_symlink',
      'dir3_subdirs_symlink_ignored',
      'dir4_full_ignore/d4f1.txt',
      'dir5_content_ignore/d5f1-ignored-changed-content.txt',
      'dir5_content_ignore/d5f2-unignored-content.txt',
      'dir5_content_ignore/dir51/d51f1-changed-content.txt',
      'f1.txt',
      'f2.txt',
      'f2_symlink.txt',
      'f3-new-file-ignore-everywhere.txt',
      'f4-ignore-ext.log',
      'f5-new-file-ignore-ext.log',
    ];

    return [
      [[], NULL, $defaults],

      [[], fn(string $content, \SplFileInfo $file): null => NULL, $defaults],

      [[], fn(string $content, \SplFileInfo $file): true => TRUE, $defaults],

      [[], fn(string $content, \SplFileInfo $file): string => $content, $defaults],

      [[], fn(string $content, \SplFileInfo $file): false => FALSE, []],

      [
        [],
        fn(string $content, \SplFileInfo $file): bool => str_contains($content, 'f2l1'),
        [
          'd32f2_symlink_deep.txt',
          'dir1_flat/d1f2.txt',
          'dir2_flat/d2f2.txt',
          'dir3_subdirs/d3f2-ignored.txt',
          'dir3_subdirs/dir31/d31f2-ignored.txt',
          'dir3_subdirs/dir32-unignored/d32f2-ignore-ext-only-dst.log',
          'dir3_subdirs/dir32-unignored/d32f2.txt',
          'dir5_content_ignore/d5f2-unignored-content.txt',
          'f2.txt',
        ],
      ],

      [
        [
          File::RULE_GLOBAL => [
            '*.log',
            'f3-new-file-ignore-everywhere.txt',
            'dir3_subdirs_symlink_ignored',
          ],
          File::RULE_SKIP => [
            'dir2_flat/*',
            'dir3_subdirs/*',
            'dir4_full_ignore/',
          ],
          File::RULE_INCLUDE => [
            'dir3_subdirs/dir32-unignored/',
            'dir3_subdirs_symlink/dir32-unignored/',
            'dir5_content_ignore/d5f2-unignored-content.txt',
          ],
          File::RULE_IGNORE_CONTENT => [
            'dir5_content_ignore/',
          ],
        ],
        NULL,
        [
          'd32f2_symlink_deep.txt',
          'dir1_flat/d1f1.txt',
          'dir1_flat/d1f1_symlink.txt',
          'dir1_flat/d1f2.txt',
          'dir3_subdirs/dir31/d31f1-ignored.txt',
          'dir3_subdirs/dir31/d31f2-ignored.txt',
          'dir3_subdirs/dir32-unignored/d32f1.txt',
          'dir3_subdirs/dir32-unignored/d32f1_symlink.txt',
          'dir3_subdirs/dir32-unignored/d32f2.txt',
          'dir3_subdirs_symlink',
          'dir5_content_ignore/d5f1-ignored-changed-content.txt',
          'dir5_content_ignore/d5f2-unignored-content.txt',
          'dir5_content_ignore/dir51/d51f1-changed-content.txt',
          'f1.txt',
          'f2.txt',
          'f2_symlink.txt',
        ],
      ],
    ];
  }

}
