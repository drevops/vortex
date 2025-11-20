<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VersionScheme::class)]
class VersionSchemeInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'version scheme, calver' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(VersionScheme::id()), VersionScheme::CALVER);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('Calendar Versioning');
          $test->assertSutContains('calver.org');
          $test->assertSutNotContains('Semantic Versioning');
          $test->assertSutNotContains('semver.org');
        }),
      ],
      'version scheme, semver' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(VersionScheme::id()), VersionScheme::SEMVER);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('Semantic Versioning');
          $test->assertSutContains('semver.org');
          $test->assertSutNotContains('Calendar Versioning');
          $test->assertSutNotContains('calver.org');
        }),
      ],
      'version scheme, other' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(VersionScheme::id()), VersionScheme::OTHER);
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
