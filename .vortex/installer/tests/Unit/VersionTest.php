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
   * @return array<string, array{?string, ?int}>
   *   Test data.
   */
  public static function dataProviderMajor(): array {
    return [
      'null' => [NULL, NULL],
      'empty' => ['', NULL],
      'develop' => ['develop', NULL],
      'unstamped token' => ['@vortex-installer-version@', NULL],
      'semver 1.x' => ['1.40.0', 1],
      'semver with v prefix' => ['v1.2.3', 1],
      'semver 2.x' => ['2.0.0', 2],
      'dev branch' => ['2.x-dev', 2],
      'semver+calver' => ['1.0.0+2025.11.0', 1],
      'legacy calver' => ['25.10.0', 25],
      'leading whitespace' => ['  1.0.0', 1],
    ];
  }

  #[DataProvider('dataProviderReleasePrefix')]
  public function testReleasePrefix(?string $version, ?string $expected): void {
    $this->assertSame($expected, Version::releasePrefix($version));
  }

  /**
   * Data provider for testReleasePrefix().
   *
   * @return array<string, array{?string, ?string}>
   *   Test data.
   */
  public static function dataProviderReleasePrefix(): array {
    return [
      'null' => [NULL, NULL],
      'develop' => ['develop', NULL],
      'major 1' => ['1.40.0', '1.'],
      'major 2 dev' => ['2.x-dev', '2.'],
      'legacy calver' => ['25.10.0', '25.'],
    ];
  }

  #[DataProvider('dataProviderMajorFromConstraint')]
  public function testMajorFromConstraint(?string $constraint, ?int $expected): void {
    $this->assertSame($expected, Version::majorFromConstraint($constraint));
  }

  /**
   * Data provider for testMajorFromConstraint().
   *
   * @return array<string, array{?string, ?int}>
   *   Test data.
   */
  public static function dataProviderMajorFromConstraint(): array {
    return [
      'null' => [NULL, NULL],
      'empty' => ['', NULL],
      'no digits' => ['dev-main', NULL],
      'caret 1' => ['^1.1.0', 1],
      'caret 2' => ['^2.0.0', 2],
      'tilde 2' => ['~2.0', 2],
      'dev branch' => ['2.x-dev', 2],
      'range' => ['>=1.2 <3.0', 1],
    ];
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
   * @return array<string, array{?string, ?int}>
   *   Test data.
   */
  public static function dataProviderDetectProjectMajor(): array {
    return [
      'no composer.json' => [NULL, NULL],
      'invalid json' => ['not json', NULL],
      'empty object' => ['{}', NULL],
      'no require' => ['{"name": "test/test"}', NULL],
      'no tooling' => ['{"require": {"php": ">=8.3"}}', NULL],
      'tooling v1' => ['{"require": {"drevops/vortex-tooling": "^1.1.0"}}', 1],
      'tooling v2' => ['{"require": {"drevops/vortex-tooling": "^2.0.0"}}', 2],
      'tooling non-string' => ['{"require": {"drevops/vortex-tooling": 1}}', NULL],
    ];
  }

}
