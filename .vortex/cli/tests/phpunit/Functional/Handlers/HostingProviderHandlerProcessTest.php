<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class HostingProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_acquia' => [
      self::cw(function ($test): void {
          $test->prompts['hosting_provider'] = 'acquia';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      // Cannot assert for the full absence of 'lagoon' since we use Lagoon
      // images for local and CI even with Acquia.
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['lagoon_', 'lagoon_logs', 'LAGOON_PROJECT', 'VORTEX_DEPLOY_WEBHOOK_URL']);
          $test->assertSutContains(['/VORTEX_DEPLOY_ARTIFACT_/', '/VORTEX_ACQUIA_/']);
      }),
    ];
    yield 'hosting_lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['hosting_provider'] = 'lagoon';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['acquia', '/VORTEX_DEPLOY_ARTIFACT_/', 'VORTEX_DEPLOY_WEBHOOK_URL']);
          $test->assertSutContains('LAGOON_PROJECT');
      }),
    ];
  }

}
