<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(File::class)]
class FileTest extends UnitTestBase {

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

}
