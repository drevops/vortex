<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
class ThemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'theme_olivero' => [
        static::cw(fn() => Env::put(Theme::envName(), Theme::OLIVERO)),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
          'AGENTS.md',
          'CLAUDE.md',
        ])),
      ],

      'theme_claro' => [
        static::cw(fn() => Env::put(Theme::envName(), Theme::CLARO)),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
          'AGENTS.md',
          'CLAUDE.md',
        ])),
      ],

      'theme_stark' => [
        static::cw(fn() => Env::put(Theme::envName(), Theme::STARK)),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
          'AGENTS.md',
          'CLAUDE.md',
        ])),
      ],

      'theme_custom' => [
        static::cw(fn() => Env::put(Theme::envName(), 'light_saber')),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'your_site_theme')),
      ],
    ];
  }

}
