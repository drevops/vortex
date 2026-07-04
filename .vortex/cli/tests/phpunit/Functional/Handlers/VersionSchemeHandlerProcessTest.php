<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class VersionSchemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'version_scheme_calver' => [
      self::cw(function ($test): void {
          $test->prompts['version_scheme'] = 'calver';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('Calendar Versioning');
          $test->assertSutContains('calver.org');
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
      }),
    ];
    yield 'version_scheme_semver' => [
      self::cw(function ($test): void {
          $test->prompts['version_scheme'] = 'semver';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('Semantic Versioning');
          $test->assertSutContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
      }),
    ];
    yield 'version_scheme_other' => [
      self::cw(function ($test): void {
          $test->prompts['version_scheme'] = 'other';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
      }),
    ];
  }

}
