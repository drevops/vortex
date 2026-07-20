<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Services::class)]
class ServicesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'services_no_clamav' => [
      static::cw(function ($test): void {
          $test->prompts[Services::id()] = [Services::SOLR, Services::REDIS];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertFileExists(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
    yield 'services_no_redis' => [
      static::cw(function ($test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::SOLR];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('redis')),
    ];
    yield 'services_no_solr' => [
      static::cw(function ($test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::REDIS];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('solr');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
    yield 'services_none' => [
      static::cw(fn($test): array => $test->prompts[Services::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
  }

}
