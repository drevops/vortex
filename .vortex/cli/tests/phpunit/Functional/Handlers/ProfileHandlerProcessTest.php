<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ProfileHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'profile_minimal' => [
      self::cw(fn($test): string => $test->prompts['profile'] = 'minimal'),
    ];
    yield 'profile_the_empire' => [
      self::cw(function ($test): void {
          $test->prompts['profile'] = 'custom';
          $test->prompts['profile_custom'] = 'the_empire';
      }),
    ];
  }

}
