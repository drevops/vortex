<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(File::class)]
class FileTest extends UnitTestCase {

  #[DataProvider('dataProviderIsInternal')]
  public function testIsInternal(string $path, bool $expected): void {
    $result = File::isInternal($path);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderIsInternal(): \Iterator {
    yield 'exact match - LICENSE' => ['/LICENSE', TRUE];
    yield 'exact match - CODE_OF_CONDUCT.md' => ['/CODE_OF_CONDUCT.md', TRUE];
    yield 'exact match - CONTRIBUTING.md' => ['/CONTRIBUTING.md', TRUE];
    yield 'exact match - SECURITY.md' => ['/SECURITY.md', TRUE];
    yield 'directory match - docs' => ['/.vortex/docs', TRUE];
    yield 'directory match - tests' => ['/.vortex/tests', TRUE];
    yield 'relative path stripped' => ['./LICENSE', TRUE];
    yield 'relative path in subdir' => ['docs/LICENSE', FALSE];
    yield 'non-internal path' => ['/some/other/path', FALSE];
    yield 'hidden file not matched' => ['/.gitignore', FALSE];
  }

  #[DataProvider('dataProviderToRelative')]
  public function testToRelative(string $path, ?string $base, string $expected): void {
    $result = File::toRelative($path, $base);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderToRelative(): \Iterator {
    // Get the current working directory for test cases.
    $cwd = getcwd();
    // Test cases with explicit base path.
    yield 'absolute path with base' => [
      '/var/www/project/file.txt',
      '/var/www',
      'project/file.txt',
    ];
    yield 'absolute path same as base' => [
      '/var/www',
      '/var/www',
      '/var/www',
    ];
    yield 'nested absolute path' => [
      '/var/www/project/src/utils/file.php',
      '/var/www/project',
      'src/utils/file.php',
    ];
    yield 'relative path with base' => [
      'src/file.php',
      '/var/www/project',
      'src/file.php',
    ];
    yield 'dot relative path with base' => [
      './src/file.php',
      '/var/www/project',
      'src/file.php',
    ];
    yield 'parent directory path with base' => [
      '../other/file.php',
      '/var/www/project',
      '/var/www/other/file.php',
    ];
    // Test cases with NULL base (should use current working directory).
    yield 'absolute path with null base' => [
      $cwd . '/test/file.txt',
      NULL,
      'test/file.txt',
    ];
    yield 'current directory with null base' => [
      $cwd,
      NULL,
      $cwd,
    ];
    yield 'relative path with null base' => [
      'test/file.txt',
      NULL,
      'test/file.txt',
    ];
    yield 'dot path with null base' => [
      './test/file.txt',
      NULL,
      'test/file.txt',
    ];
    // Edge cases.
    yield 'empty path with base - resolves to base' => [
      '',
      '/var/www',
      '/var/www',
    ];
    yield 'root path - no base match' => [
      '/',
      '/var',
      '/',
    ];
    yield 'base is child of path - no match' => [
      '/var/www',
      '/var/www/project',
      '/var/www',
    ];
    yield 'windows-style path on unix' => [
      '/c/Users/project/file.txt',
      '/c/Users',
      'project/file.txt',
    ];
    yield 'path with special characters' => [
      '/var/www/project with spaces/file-name_test.php',
      '/var/www',
      'project with spaces/file-name_test.php',
    ];
    yield 'path with dots in filename' => [
      '/var/www/project/.env.local',
      '/var/www',
      'project/.env.local',
    ];
    yield 'complex nested structure' => [
      '/var/www/html/project/src/Utils/File.php',
      '/var/www/html',
      'project/src/Utils/File.php',
    ];
    yield 'same directory level' => [
      '/var/www/project1/file.txt',
      '/var/www/project2',
      '/var/www/project1/file.txt',
    ];
    yield 'deep nesting from base' => [
      '/var/www/a/b/c/d/e/file.txt',
      '/var/www',
      'a/b/c/d/e/file.txt',
    ];
  }

}
