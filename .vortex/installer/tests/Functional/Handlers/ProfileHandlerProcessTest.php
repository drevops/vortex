<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profile::class)]
class ProfileHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'profile, minimal' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), Profile::MINIMAL)),
      ],

      'profile, the_empire' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Profile::id()), 'the_empire')),
      ],
    ];
  }

}
