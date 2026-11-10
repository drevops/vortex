<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProvider::class)]
class HostingProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_acquia' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::ACQUIA;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Cannot assert for the full absence of 'lagoon' since we use Lagoon
          // images for local and CI even with Acquia.
          $test->assertSutNotContains('lagoon_');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_FILES_SOURCE=acquia');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_FILES_URL');
      }),
    ];
    yield 'hosting_lagoon' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::LAGOON;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('acquia');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_FILES_SOURCE=lagoon');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_FILES_URL');
      }),
    ];
  }

}
