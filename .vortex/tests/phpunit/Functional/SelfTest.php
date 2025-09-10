<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;

/**
 * Test the testing system itself.
 *
 * @group smoke
 */
#[\PHPUnit\Framework\Attributes\Group('smoke')]
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
  #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderAssertFilesWildcardExists')]
  public function testAssertFilesWildcardExists($patterns, bool $should_pass): void {
    // Convert relative patterns to absolute paths.
    $workspace = static::$workspace;
    if (is_array($patterns)) {
      $patterns = array_map(function ($pattern) use ($workspace) {
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
      $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
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
        true,
      ],
      'single pattern not exists' => [
        'wildcard_test/*.xml',
        false,
      ],
      'array patterns all exist' => [
        ['wildcard_test/*.html', 'wildcard_test/*.log'],
        true,
      ],
      'array patterns mixed' => [
        ['wildcard_test/*.html', 'wildcard_test/*.xml'],
        false,
      ],
      'subdirectory pattern' => [
        'wildcard_test/*/*.html',
        true,
      ],
      'empty array' => [
        [],
        true,
      ],
    ];
  }

  /**
   * Test assertFilesWildcardDoNotExist method.
   */
  #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderAssertFilesWildcardDoNotExist')]
  public function testAssertFilesWildcardDoNotExist($patterns, bool $should_pass): void {
    // Convert relative patterns to absolute paths.
    $workspace = static::$workspace;
    if (is_array($patterns)) {
      $patterns = array_map(function ($pattern) use ($workspace) {
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
      $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
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
        true,
      ],
      'single pattern exists' => [
        'wildcard_test/*.html',
        false,
      ],
      'array patterns none exist' => [
        ['wildcard_test/*.xml', 'wildcard_test/*.php'],
        true,
      ],
      'array patterns some exist' => [
        ['wildcard_test/*.xml', 'wildcard_test/*.html'],
        false,
      ],
      'empty array' => [
        [],
        true,
      ],
    ];
  }

}
