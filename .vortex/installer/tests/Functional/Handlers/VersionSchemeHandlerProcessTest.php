<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VersionScheme::class)]
class VersionSchemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'version_scheme_calver' => [
        static::cw(function (): void {
          Env::put(VersionScheme::envName(), VersionScheme::CALVER);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('Calendar Versioning');
          $test->assertSutContains('calver.org');
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
        }),
      ],
      'version_scheme_semver' => [
        static::cw(function (): void {
          Env::put(VersionScheme::envName(), VersionScheme::SEMVER);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('Semantic Versioning');
          $test->assertSutContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
        }),
      ],
      'version_scheme_other' => [
        static::cw(function (): void {
          Env::put(VersionScheme::envName(), VersionScheme::OTHER);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
        }),
      ],
    ];
  }

}
