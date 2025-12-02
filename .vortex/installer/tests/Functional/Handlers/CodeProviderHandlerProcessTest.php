<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeProvider::class)]
class CodeProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'code provider, github' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::GITHUB)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.dist.md');
          $test->assertFileContainsString(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.md', 'Checklist before requesting a review');
        }),
      ],

      'code provider, other' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::OTHER)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertDirectoryDoesNotExist(static::$sut . '/.github');
        }),
      ],
    ];
  }

}
