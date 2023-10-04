<?php

namespace Drevops\Installer\Tests\Unit\Utils;

use DrevOps\Installer\Command\Installer;
use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Files;

/**
 * Class InstallerCopyRecursiveTest.
 *
 * InstallerCopyRecursiveTest fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class FilesTest extends UnitTestBase {

  public function setUp(): void {
    parent::setUp();
    $this->prepareFixtureDir();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->cleanupFixtureDir();
  }

  /**
   * @covers \Drevops\Installer\Utils\Files::copyRecursive
   */
  public function testCopyRecursive() {
    $files_dir = $this->getFixtureDir('copyfiles');

    $this->callProtectedMethod(Files::class, 'copyRecursive', [$files_dir, $this->fixtureDir]);

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
   * @dataProvider dataProviderFileContains
   * @covers       \DrevOps\Installer\Utils\Files::fileContains
   */
  public function testFileContains($string, $file, $expected) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree([$file], $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);
    $created_file = reset($created_files);

    $actual = Files::fileContains($string, $created_file);

    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderFileContains() {
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
   * @covers       \DrevOps\Installer\Utils\Files::dirContains
   */
  public function testDirContains($string, $files, $expected) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $this->createFixtureFiles($files, $tokens_dir);

    $actual = $this->callProtectedMethod(Files::class, 'dirContains', [$string, $this->fixtureDir]);

    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderDirContains() {
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
   * @covers       \DrevOps\Installer\Utils\Files::dirReplaceContent
   * @dataProvider dataProviderDirReplaceContent
   */
  public function testDirReplaceContent($files, $expected_files) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);

    if (count($created_files) != count($expected_files)) {
      throw new \RuntimeException(sprintf('Provided files number is not equal to expected files number.'));
    }

    $this->callProtectedMethod(Files::class, 'dirReplaceContent', ['BAR', 'FOO', $this->fixtureDir]);

    foreach (array_keys($created_files) as $k) {
      $this->assertFileEquals($expected_files[$k], $created_files[$k]);
    }
  }

  public static function dataProviderDirReplaceContent() {
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
   * @covers       \DrevOps\Installer\Utils\Files::replaceStringFilename
   * @dataProvider dataProviderReplaceStringFilename
   */
  public function testReplaceStringFilename($files, $expected_files) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $this->fixtureDir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir, FALSE);

    if (count($created_files) != count($expected_files)) {
      throw new \RuntimeException(sprintf('Provided files number is not equal to expected files number.'));
    }

    $this->callProtectedMethod(Files::class, 'replaceStringFilename', ['foo', 'bar', $this->fixtureDir]);

    foreach (array_keys($expected_files) as $k) {
      $this->assertFileExists($expected_files[$k]);
    }
  }

  public static function dataProviderReplaceStringFilename() {
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
