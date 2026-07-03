<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProvider::class)]
class HostingProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_acquia' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::ACQUIA;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      // Cannot assert for the full absence of 'lagoon' since we use Lagoon
      // images for local and CI even with Acquia.
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains(['lagoon_', 'lagoon_logs', 'LAGOON_PROJECT', 'VORTEX_DEPLOY_WEBHOOK_URL']);
          $test->assertSutContains(['/VORTEX_DEPLOY_ARTIFACT_/', '/VORTEX_ACQUIA_/']);
      }),
    ];
    yield 'hosting_lagoon' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::LAGOON;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains(['acquia', '/VORTEX_DEPLOY_ARTIFACT_/', 'VORTEX_DEPLOY_WEBHOOK_URL']);
          $test->assertSutContains('LAGOON_PROJECT');
      }),
    ];
  }

}
