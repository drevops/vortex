<?php

declare(strict_types=1);

namespace Drevops\Installer\Tests\Unit;

use DrevOps\Installer\File;

/**
 * Class InstallerCopyRecursiveTest.
 *
 * InstallerCopyRecursiveTest fixture class.
 *
 * @coversDefaultClass \DrevOps\Installer\File
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class FileTest extends UnitTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->prepareFixtureDir();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->cleanupFixtureDir();
  }

  /**
   * @covers ::copyRecursive
   */
  public function testCopyRecursive(): void {
    $files_dir = $this->getFixtureDir('copyfiles');

    File::copyRecursive($files_dir, $this->fixtureDir);

    $dir = $this->fixtureDir . DIRECTORY_SEPARATOR;

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
      ["{\\d+}", TRUE],
      ["(\\d+)", TRUE],
      ["<[A-Z]{3,6}>", TRUE],

      // Invalid regular expressions (wrong delimiters or syntax).
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
   * @dataProvider dataProviderFileContains
   * @covers ::fileContains
   */
  public function testFileContains(string $string, string $file, mixed $expected): void {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree([$file], $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);
    $created_file = reset($created_files);

    if (empty($created_file) || !file_exists($created_file)) {
      throw new \RuntimeException('File does not exist.');
    }

    $actual = File::fileContains($string, $created_file);

    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderFileContains(): array {
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
   * @dataProvider dataProviderDirContains
   * @covers ::dirContains
   */
  public function testDirContains(string $string, array $files, mixed $expected): void {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $this->createFixtureFiles($files, $tokens_dir);

    $actual = File::dirContains($string, $this->fixtureDir);

    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderDirContains(): array {
    return [
      ['FOO', ['empty.txt'], FALSE],
      ['BAR', ['foobar_b.txt'], TRUE],
      ['FOO', ['dir1/foobar_b.txt'], TRUE],
      ['BAR', ['dir1/foobar_b.txt'], TRUE],

      // Regex.
      ['/BA/', ['dir1/foobar_b.txt'], TRUE],
      ['/BAW/', ['dir1/foobar_b.txt'], FALSE],
      ['/BA.*/', ['dir1/foobar_b.txt'], TRUE],
    ];
  }

  /**
   * @dataProvider dataProviderRemoveTokenFromFile
   * @covers ::removeTokenFromFile
   */
  public function testRemoveTokenFromFile(string $file, string $begin, string $end, bool $with_content, bool $expect_exception, string $expected_file): void {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree([$file], $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);
    $created_file = reset($created_files);
    $expected_files = $this->flattenFileTree([$expected_file], $tokens_dir);
    $expected_file = reset($expected_files);

    if (empty($created_file) || !file_exists($created_file)) {
      throw new \RuntimeException('File does not exist.');
    }

    if ($expect_exception) {
      $this->expectException(\RuntimeException::class);
    }

    File::removeTokenFromFile($created_file, $begin, $end, $with_content);

    $this->assertFileEquals($expected_file, $created_file);
  }

  public static function dataProviderRemoveTokenFromFile(): array {
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
   * @dataProvider dataProviderDirReplaceContent
   * @covers     ::dirReplaceContent
   */
  public function testDirReplaceContent(array $files, array $expected_files): void {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);

    if (count($created_files) !== count($expected_files)) {
      throw new \RuntimeException('Provided files number is not equal to expected files number.');
    }

    File::dirReplaceContent('BAR', 'FOO', $this->fixtureDir);

    foreach (array_keys($created_files) as $k) {
      $this->assertFileEquals($expected_files[$k], $created_files[$k]);
    }
  }

  public static function dataProviderDirReplaceContent(): array {
    return [
      [
        ['empty.txt'],
        ['empty.txt'],
      ],
      [
        ['foobar_b.txt', 'foobar_m.txt', 'foobar_e.txt'],
        ['foofoo_b.txt', 'foofoo_m.txt', 'foofoo_e.txt'],
      ],
      [
        ['dir1/foobar_b.txt'],
        ['dir1/foofoo_b.txt'],
      ],
    ];
  }

  /**
   * @dataProvider dataProviderReplaceStringFilename
   * @covers    ::replaceStringFilename
   */
  public function testReplaceStringFilename(array $files, array $expected_files): void {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $this->fixtureDir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir, FALSE);

    if (count($created_files) !== count($expected_files)) {
      throw new \RuntimeException('Provided files number is not equal to expected files number.');
    }

    File::replaceStringFilename('foo', 'bar', $this->fixtureDir);

    foreach (array_keys($expected_files) as $k) {
      $this->assertFileExists($expected_files[$k]);
    }
  }

  public static function dataProviderReplaceStringFilename(): array {
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

}
