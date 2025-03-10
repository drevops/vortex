<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Tests\Functional\FunctionalTestBase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Unit tests for the functional tests.
 *
 * These are "tests for tests" to make sure that the custom assertions
 * provided by the CustomizerTestCase class work as expected.
 *
 * We inherit from FunctionalTestBase to test the custom assertions.
 *
 * @coversDefaultClass \DrevOps\Installer\Tests\Functional\FunctionalTestBase
 */
class SelfTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fs = new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Override the parent method as this is a unit test for a method of
    // the parent test class.
  }

  /**
   * @dataProvider dataProviderAssertDirectoriesEqual
   * @covers ::assertDirectoriesEqual
   */
  public function testAssertDirectoriesEqual(array $diffs = []): void {
    $base = getcwd() . DIRECTORY_SEPARATOR . static::FIXTURES_DIR . DIRECTORY_SEPARATOR . 'assert_fixture_files' . DIRECTORY_SEPARATOR . $this->dataName() . DIRECTORY_SEPARATOR . 'dir1';
    $expected = getcwd() . DIRECTORY_SEPARATOR . static::FIXTURES_DIR . DIRECTORY_SEPARATOR . 'assert_fixture_files' . DIRECTORY_SEPARATOR . $this->dataName() . DIRECTORY_SEPARATOR . 'dir2';

    try {
      $this->assertDirectoriesEqual($base, $expected);
    }
    catch (AssertionFailedError $assertionFailedError) {
      $this->assertExceptionMessage($assertionFailedError->getMessage(), $diffs);
    }
  }

  /**
   * Data provider for testAssertDirectoriesEqual().
   *
   * @return array
   *   The data provider.
   */
  public static function dataProviderAssertDirectoriesEqual(): array {
    return [
      'files_equal' => [],
      'files_not_equal' => [
        [
          'dir1' => [
            'd32f2_symlink_deep.txt',
            'dir1_flat/d1f1_symlink.txt',
            'dir1_flat/d1f3-only-src.txt',
            'dir3_subdirs/dir32-unignored/d32f1_symlink.txt',
            'dir3_subdirs_symlink/d3f1-ignored.txt',
            'dir3_subdirs_symlink/d3f2-ignored.txt',
            'dir3_subdirs_symlink/dir31/d31f1-ignored.txt',
            'dir3_subdirs_symlink/dir31/d31f2-ignored.txt',
            'dir3_subdirs_symlink/dir32-unignored/d32f1.txt',
            'dir3_subdirs_symlink/dir32-unignored/d32f1_symlink.txt',
            'dir3_subdirs_symlink/dir32-unignored/d32f2.txt',
            'f2_symlink.txt',
          ],
          'dir2' => [
            'dir2_flat-present-dst/d2f1.txt',
            'dir2_flat-present-dst/d2f2.txt',
            'dir3_subdirs/dir31/f4-new-file-notignore-everywhere.txt',
            'dir5_content_ignore/dir51/d51f2-new-file.txt',
            'f4-new-file-notignore-everywhere.txt',
          ],
          'content' => [
            'dir3_subdirs/dir32-unignored/d32f2.txt',
          ],
        ],
      ],
    ];
  }

  /**
   * Assert that the exception message contains the expected differences.
   *
   * @param string $message
   *   The exception message.
   * @param array $expected
   *   The expected differences.
   */
  private function assertExceptionMessage(string $message, array $expected): void {
    $actual = ['dir1' => [], 'dir2' => [], 'content' => []];

    // Parse the exception message into sections.
    $lines = explode("\n", trim($message));
    $section = NULL;
    foreach ($lines as $line) {
      $line = trim($line);

      if ($line === 'Files only in dir1:') {
        $section = 'dir1';
      }
      elseif ($line === 'Files only in dir2:') {
        $section = 'dir2';
      }
      elseif ($line === 'Files that differ in content:') {
        $section = 'content';
      }
      elseif ($section) {
        $actual[$section][] = $line;
      }
    }

    // Compare the actual and expected sections.
    foreach (['dir1', 'dir2', 'content'] as $section) {
      $this->assertEquals($expected[$section] ?: [], $actual[$section] ?: [], sprintf("Files in section '%s' do not match expected.", $section));
    }
  }

}
