<?php

namespace Drevops\Tests;

/**
 * Class InstallerTokenTest.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class InstallerTokenTest extends DrevopsTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->prepareFixtureDir();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->cleanupFixtureDir();
  }

  /**
   * @dataProvider dataProviderFileContains
   * @covers       \file_contains()
   */
  public function testFileContains($string, $file, $expected) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree([$file], $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);
    $created_file = reset($created_files);

    $actual = file_contains($string, $created_file);

    $this->assertEquals($expected, $actual);
  }

  public function dataProviderFileContains() {
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
   * @covers       \dir_contains()
   */
  public function testDirContains($string, $files, $expected) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $this->createFixtureFiles($files, $tokens_dir);

    $actual = dir_contains($string, $this->fixtureDir);

    $this->assertEquals($expected, $actual);
  }

  public function dataProviderDirContains() {
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
   * @covers       \remove_token_with_content()
   */
  public function testRemoveTokenFromFile($file, $begin, $end, $with_content, $expect_exception, $expected_file) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree([$file], $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);
    $created_file = reset($created_files);
    $expected_files = $this->flattenFileTree([$expected_file], $tokens_dir);
    $expected_file = reset($expected_files);

    if ($expect_exception) {
      $this->expectException(\RuntimeException::class);
    }

    remove_token_from_file($created_file, $begin, $end, $with_content);

    $this->assertFileEquals($expected_file, $created_file);
  }

  public function dataProviderRemoveTokenFromFile() {
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
   * @covers       \dir_replace_content()
   */
  public function testDirReplaceContent($files, $expected_files) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $tokens_dir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir);

    if (count($created_files) != count($expected_files)) {
      throw new \RuntimeException(sprintf('Provided files number is not equal to expected files number.'));
    }

    dir_replace_content('BAR', 'FOO', $this->fixtureDir);

    foreach (array_keys($created_files) as $k) {
      $this->assertFileEquals($expected_files[$k], $created_files[$k]);
    }
  }

  public function dataProviderDirReplaceContent() {
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
   * @covers       \replace_string_filename()
   */
  public function testReplaceStringFilename($files, $expected_files) {
    $tokens_dir = $this->getFixtureDir('tokens');
    $files = $this->flattenFileTree($files, $tokens_dir);
    $expected_files = $this->flattenFileTree($expected_files, $this->fixtureDir);
    $created_files = $this->createFixtureFiles($files, $tokens_dir, FALSE);

    if (count($created_files) != count($expected_files)) {
      throw new \RuntimeException(sprintf('Provided files number is not equal to expected files number.'));
    }

    replace_string_filename('foo', 'bar', $this->fixtureDir);

    foreach (array_keys($expected_files) as $k) {
      $this->assertFileExists($expected_files[$k]);
    }
  }

  public function dataProviderReplaceStringFilename() {
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
