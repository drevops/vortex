<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profile::class)]
class ProfileHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'profile_minimal' => [
        static::cw(fn() => Env::put(Profile::envName(), Profile::MINIMAL)),
      ],

      'profile_the_empire' => [
        static::cw(fn() => Env::put(Profile::envName(), 'the_empire')),
      ],
    ];
  }

}
