<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
class ThemeInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'theme, absent' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Theme::id()), '');
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
          '.gitignore',
          'scripts/vortex',
          'composer.json',
          'CLAUDE.md',
        ])),
      ],

      'theme, custom' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Theme::id()), 'light_saber')),
        static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'your_site_theme')),
      ],

    ];
  }

}
