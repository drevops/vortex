<?php

namespace Drevops\Installer\Tests\Unit;

use DrevOps\Installer\Command\InstallCommand;

/**
 * Class InstallerTokenTest.
 *
 * InstallerTokenTest fixture class.
 *
 * @coversDefaultClass \DrevOps\Installer\Command\InstallCommand
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class TokenTest extends UnitTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->prepareFixtureDir();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->cleanupFixtureDir();
  }

  /**
   * Flatten file tree.
   */
  protected function flattenFileTree($tree, string $parent = '.'): array {
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

    $actual = InstallCommand::fileContains($string, $created_file);

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

    $actual = $this->callProtectedMethod(InstallCommand::class, 'dirContains', [$string, $this->fixtureDir]);

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

    if ($expect_exception) {
      $this->expectException(\RuntimeException::class);
    }

    InstallCommand::removeTokenFromFile($created_file, $begin, $end, $with_content);

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

    $this->callProtectedMethod(InstallCommand::class, 'dirReplaceContent', ['BAR', 'FOO', $this->fixtureDir]);

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

    $this->callProtectedMethod(InstallCommand::class, 'replaceStringFilename', ['foo', 'bar', $this->fixtureDir]);

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
