<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VersionScheme::class)]
class VersionSchemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'version_scheme_calver' => [
      static::cw(function ($test): void {
          $test->prompts[VersionScheme::id()] = VersionScheme::CALVER;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('Calendar Versioning');
          $test->assertSutContains('calver.org');
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
      }),
    ];
    yield 'version_scheme_semver' => [
      static::cw(function ($test): void {
          $test->prompts[VersionScheme::id()] = VersionScheme::SEMVER;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('Semantic Versioning');
          $test->assertSutContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
      }),
    ];
    yield 'version_scheme_other' => [
      static::cw(function ($test): void {
          $test->prompts[VersionScheme::id()] = VersionScheme::OTHER;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
      }),
    ];
  }

}
