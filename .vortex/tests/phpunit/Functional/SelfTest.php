<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\AssertionFailedError;
use AlexSkrypnyk\File\File;

/**
 * Test the testing system itself.
 */
#[Group('smoke')]
class SelfTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    // Create test directory structure for wildcard testing.
    $test_dir = static::$workspace . DIRECTORY_SEPARATOR . 'wildcard_test';
    File::mkdir($test_dir);

    // Create test files.
    File::dump($test_dir . DIRECTORY_SEPARATOR . 'test1.html', 'test content');
    File::dump($test_dir . DIRECTORY_SEPARATOR . 'test2.html', 'test content');
    File::dump($test_dir . DIRECTORY_SEPARATOR . 'test1.log', 'test content');
    File::dump($test_dir . DIRECTORY_SEPARATOR . 'report.txt', 'test content');

    // Create subdirectory with files.
    $sub_dir = $test_dir . DIRECTORY_SEPARATOR . 'subdir';
    File::mkdir($sub_dir);
    File::dump($sub_dir . DIRECTORY_SEPARATOR . 'nested.html', 'test content');
  }

  /**
   * Test assertFilesWildcardExists method.
   */
  #[DataProvider('dataProviderAssertFilesWildcardExists')]
  public function testAssertFilesWildcardExists(string|array $patterns, bool $should_pass, ?string $expected_exception = NULL, ?string $expected_message = NULL): void {
    // Convert relative patterns to absolute paths.
    $workspace = static::$workspace;
    if (is_array($patterns)) {
      $patterns = array_map(function (string $pattern) use ($workspace): string {
        return $workspace . DIRECTORY_SEPARATOR . $pattern;
      }, $patterns);
    }
    elseif (!empty($patterns)) {
      $patterns = $workspace . DIRECTORY_SEPARATOR . $patterns;
    }

    if ($should_pass) {
      $this->assertFilesWildcardExists($patterns);
    }
    else {
      if (!is_string($expected_exception) || !class_exists($expected_exception)) {
        throw new \RuntimeException('Expected a string, got a ' . gettype($expected_exception));
      }
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      if ($expected_message !== NULL) {
        $this->expectExceptionMessage($expected_message);
      }
      $this->assertFilesWildcardExists($patterns);
    }
  }

  /**
   * Data provider for assertFilesWildcardExists tests.
   */
  public static function dataProviderAssertFilesWildcardExists(): array {
    return [
      'single pattern exists' => [
        'wildcard_test/*.html',
        TRUE,
      ],
      'single pattern not exists' => [
        'wildcard_test/*.xml',
        FALSE,
        AssertionFailedError::class,
        'No files found matching wildcard pattern',
      ],
      'array patterns all exist' => [
        ['wildcard_test/*.html', 'wildcard_test/*.log'],
        TRUE,
      ],
      'array patterns mixed' => [
        ['wildcard_test/*.html', 'wildcard_test/*.xml'],
        FALSE,
        AssertionFailedError::class,
        'No files found matching wildcard pattern',
      ],
      'subdirectory pattern' => [
        'wildcard_test/*/*.html',
        TRUE,
      ],
      'empty array' => [
        [],
        FALSE,
        \InvalidArgumentException::class,
        'Empty patterns - no files to check',
      ],
    ];
  }

  /**
   * Test assertFilesWildcardDoNotExist method.
   */
  #[DataProvider('dataProviderAssertFilesWildcardDoNotExist')]
  public function testAssertFilesWildcardDoNotExist(string|array $patterns, bool $should_pass, ?string $expected_exception = NULL, ?string $expected_message = NULL): void {
    // Convert relative patterns to absolute paths.
    $workspace = static::$workspace;
    if (is_array($patterns)) {
      $patterns = array_map(function (string $pattern) use ($workspace): string {
        return $workspace . DIRECTORY_SEPARATOR . $pattern;
      }, $patterns);
    }
    elseif (!empty($patterns)) {
      $patterns = $workspace . DIRECTORY_SEPARATOR . $patterns;
    }

    if ($should_pass) {
      $this->assertFilesWildcardDoNotExist($patterns);
    }
    else {
      if (!is_string($expected_exception) || !class_exists($expected_exception)) {
        throw new \RuntimeException('Expected a string, got a ' . gettype($expected_exception));
      }
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      if ($expected_message !== NULL) {
        $this->expectExceptionMessage($expected_message);
      }
      $this->assertFilesWildcardDoNotExist($patterns);
    }
  }

  /**
   * Data provider for assertFilesWildcardDoNotExist tests.
   */
  public static function dataProviderAssertFilesWildcardDoNotExist(): array {
    return [
      'single pattern not exists' => [
        'wildcard_test/*.xml',
        TRUE,
      ],
      'single pattern exists' => [
        'wildcard_test/*.html',
        FALSE,
        AssertionFailedError::class,
        'Found 2 file(s) matching wildcard pattern that should not exist',
      ],
      'array patterns none exist' => [
        ['wildcard_test/*.xml', 'wildcard_test/*.php'],
        TRUE,
      ],
      'array patterns some exist' => [
        ['wildcard_test/*.xml', 'wildcard_test/*.html'],
        FALSE,
        AssertionFailedError::class,
        'Found 2 file(s) matching wildcard pattern that should not exist',
      ],
      'empty array' => [
        [],
        FALSE,
        \InvalidArgumentException::class,
        'Empty patterns - no files to check',
      ],
    ];
  }

}
