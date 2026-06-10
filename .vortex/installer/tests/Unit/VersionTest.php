<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\File;
use DrevOps\VortexInstaller\Utils\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Version::class)]
class VersionTest extends UnitTestCase {

  #[DataProvider('dataProviderMajor')]
  public function testMajor(?string $version, ?int $expected): void {
    $this->assertSame($expected, Version::major($version));
  }

  /**
   * Data provider for testMajor().
   *
   * @return \Iterator<string, array{(string | null), (int | null)}>
   *   Test data.
   */
  public static function dataProviderMajor(): \Iterator {
    yield 'null' => [NULL, NULL];
    yield 'empty' => ['', NULL];
    yield 'develop' => ['develop', NULL];
    yield 'unstamped token' => ['@vortex-installer-version@', NULL];
    yield 'semver 1.x' => ['1.40.0', 1];
    yield 'semver with v prefix' => ['v1.2.3', 1];
    yield 'semver 2.x' => ['2.0.0', 2];
    yield 'dev branch' => ['2.x-dev', 2];
    yield 'semver+calver' => ['1.0.0+2025.11.0', 1];
    yield 'legacy calver' => ['25.10.0', 25];
    yield 'leading whitespace' => ['  1.0.0', 1];
  }

  #[DataProvider('dataProviderReleasePrefix')]
  public function testReleasePrefix(?string $version, ?string $expected): void {
    $this->assertSame($expected, Version::releasePrefix($version));
  }

  /**
   * Data provider for testReleasePrefix().
   *
   * @return \Iterator<string, array{(string | null), (string | null)}>
   *   Test data.
   */
  public static function dataProviderReleasePrefix(): \Iterator {
    yield 'null' => [NULL, NULL];
    yield 'develop' => ['develop', NULL];
    yield 'major 1' => ['1.40.0', '1.'];
    yield 'major 2 dev' => ['2.x-dev', '2.'];
    yield 'legacy calver' => ['25.10.0', '25.'];
  }

  #[DataProvider('dataProviderMajorFromConstraint')]
  public function testMajorFromConstraint(?string $constraint, ?int $expected): void {
    $this->assertSame($expected, Version::majorFromConstraint($constraint));
  }

  /**
   * Data provider for testMajorFromConstraint().
   *
   * @return \Iterator<string, array{(string | null), (int | null)}>
   *   Test data.
   */
  public static function dataProviderMajorFromConstraint(): \Iterator {
    yield 'null' => [NULL, NULL];
    yield 'empty' => ['', NULL];
    yield 'no digits' => ['dev-main', NULL];
    yield 'caret 1' => ['^1.1.0', 1];
    yield 'caret 2' => ['^2.0.0', 2];
    yield 'tilde 2' => ['~2.0', 2];
    yield 'dev branch' => ['2.x-dev', 2];
    yield 'range' => ['>=1.2 <3.0', 1];
  }

  #[DataProvider('dataProviderDetectProjectMajor')]
  public function testDetectProjectMajor(?string $composer_json, ?int $expected): void {
    $dir = self::$tmp . '/project_' . uniqid();
    File::mkdir($dir);

    if ($composer_json !== NULL) {
      File::dump($dir . '/composer.json', $composer_json);
    }

    $this->assertSame($expected, Version::detectProjectMajor($dir));
  }

  /**
   * Data provider for testDetectProjectMajor().
   *
   * @return \Iterator<string, array{(string | null), (int | null)}>
   *   Test data.
   */
  public static function dataProviderDetectProjectMajor(): \Iterator {
    yield 'no composer.json' => [NULL, NULL];
    yield 'invalid json' => ['not json', NULL];
    yield 'empty object' => ['{}', NULL];
    yield 'no require' => ['{"name": "test/test"}', NULL];
    yield 'no tooling' => ['{"require": {"php": ">=8.3"}}', NULL];
    yield 'tooling v1' => ['{"require": {"drevops/vortex-tooling": "^1.1.0"}}', 1];
    yield 'tooling v2' => ['{"require": {"drevops/vortex-tooling": "^2.0.0"}}', 2];
    yield 'tooling non-string' => ['{"require": {"drevops/vortex-tooling": 1}}', NULL];
  }

}
