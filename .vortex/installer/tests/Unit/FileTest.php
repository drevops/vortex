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

  public static function dataProviderIsInternal(): array {
    return [
      'exact match - LICENSE' => ['/LICENSE', TRUE],
      'exact match - CODE_OF_CONDUCT.md' => ['/CODE_OF_CONDUCT.md', TRUE],
      'exact match - CONTRIBUTING.md' => ['/CONTRIBUTING.md', TRUE],
      'exact match - SECURITY.md' => ['/SECURITY.md', TRUE],
      'directory match - docs' => ['/.vortex/docs', TRUE],
      'directory match - tests' => ['/.vortex/tests', TRUE],
      'relative path stripped' => ['./LICENSE', TRUE],
      'relative path in subdir' => ['docs/LICENSE', FALSE],
      'non-internal path' => ['/some/other/path', FALSE],
      'hidden file not matched' => ['/.gitignore', FALSE],
    ];
  }

  #[DataProvider('dataProviderToRelative')]
  public function testToRelative(string $path, ?string $base, string $expected): void {
    $result = File::toRelative($path, $base);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderToRelative(): array {
    // Get the current working directory for test cases.
    $cwd = getcwd();

    return [
      // Test cases with explicit base path.
      'absolute path with base' => [
        '/var/www/project/file.txt',
        '/var/www',
        'project/file.txt',
      ],
      'absolute path same as base' => [
        '/var/www',
        '/var/www',
        '/var/www',
      ],
      'nested absolute path' => [
        '/var/www/project/src/utils/file.php',
        '/var/www/project',
        'src/utils/file.php',
      ],
      'relative path with base' => [
        'src/file.php',
        '/var/www/project',
        'src/file.php',
      ],
      'dot relative path with base' => [
        './src/file.php',
        '/var/www/project',
        'src/file.php',
      ],
      'parent directory path with base' => [
        '../other/file.php',
        '/var/www/project',
        '/var/www/other/file.php',
      ],

      // Test cases with NULL base (should use current working directory).
      'absolute path with null base' => [
        $cwd . '/test/file.txt',
        NULL,
        'test/file.txt',
      ],
      'current directory with null base' => [
        $cwd,
        NULL,
        $cwd,
      ],
      'relative path with null base' => [
        'test/file.txt',
        NULL,
        'test/file.txt',
      ],
      'dot path with null base' => [
        './test/file.txt',
        NULL,
        'test/file.txt',
      ],

      // Edge cases.
      'empty path with base - resolves to base' => [
        '',
        '/var/www',
        '/var/www',
      ],
      'root path - no base match' => [
        '/',
        '/var',
        '/',
      ],
      'base is child of path - no match' => [
        '/var/www',
        '/var/www/project',
        '/var/www',
      ],
      'windows-style path on unix' => [
        '/c/Users/project/file.txt',
        '/c/Users',
        'project/file.txt',
      ],
      'path with special characters' => [
        '/var/www/project with spaces/file-name_test.php',
        '/var/www',
        'project with spaces/file-name_test.php',
      ],
      'path with dots in filename' => [
        '/var/www/project/.env.local',
        '/var/www',
        'project/.env.local',
      ],
      'complex nested structure' => [
        '/var/www/html/project/src/Utils/File.php',
        '/var/www/html',
        'project/src/Utils/File.php',
      ],
      'same directory level' => [
        '/var/www/project1/file.txt',
        '/var/www/project2',
        '/var/www/project1/file.txt',
      ],
      'deep nesting from base' => [
        '/var/www/a/b/c/d/e/file.txt',
        '/var/www',
        'a/b/c/d/e/file.txt',
      ],
    ];
  }

}
