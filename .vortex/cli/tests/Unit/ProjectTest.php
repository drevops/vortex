<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit;

use AlexSkrypnyk\File\File;
use DrevOps\VortexCli\Utils\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Project::class)]
class ProjectTest extends UnitTestCase {

  #[DataProvider('dataProviderIsVortex')]
  public function testIsVortex(?string $readme, bool $expected, bool $trailing_separator = FALSE): void {
    $dir = self::$tmp . '/project_' . uniqid();
    File::mkdir($dir);

    if ($readme !== NULL) {
      File::dump($dir . '/README.md', $readme);
    }

    $this->assertSame($expected, Project::isVortex($trailing_separator ? $dir . DIRECTORY_SEPARATOR : $dir));
  }

  /**
   * Data provider for testIsVortex().
   *
   * @return \Iterator<string, array{(string | null), bool, 2?: bool}>
   *   Test data.
   */
  public static function dataProviderIsVortex(): \Iterator {
    yield 'no README' => [NULL, FALSE];
    yield 'empty README' => ['', FALSE];
    yield 'unrelated README' => ['# My project', FALSE];
    yield 'badge present' => ['[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-blue.svg)](https://www.vortextemplate.com/)', TRUE];
    yield 'badge with development version' => ['![badge/Vortex-develop-blue]', TRUE];
    yield 'similarly named badge' => ['[![Coverage](https://img.shields.io/badge/Coverage-100%25-green.svg)]', FALSE];
    yield 'badge present with trailing separator' => ['![badge/Vortex-1.0.0-blue]', TRUE, TRUE];
  }

  public function testIsVortexOnMissingDirectory(): void {
    $this->assertFalse(Project::isVortex(self::$tmp . '/does_not_exist_' . uniqid()));
  }

}
