<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\ProfileCustom;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profile::class)]
class ProfileHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'profile_minimal' => [
      static::cw(fn($test): string => $test->prompts[Profile::id()] = Profile::MINIMAL),
    ];
    yield 'profile_the_empire' => [
      static::cw(function ($test): void {
          $test->prompts[Profile::id()] = Profile::CUSTOM;
          $test->prompts[ProfileCustom::id()] = 'the_empire';
      }),
    ];
  }

}
