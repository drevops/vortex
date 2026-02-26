<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Services::class)]
class ServicesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'services_no_clamav' => [
        static::cw(function (): void {
          Env::put(Services::envName(), Converter::toList([Services::SOLR, Services::REDIS]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('clamav')),
      ],

      'services_no_redis' => [
        static::cw(function (): void {
          Env::put(Services::envName(), Converter::toList([Services::CLAMAV, Services::SOLR]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('redis')),
      ],

      'services_no_solr' => [
        static::cw(function (): void {
          Env::put(Services::envName(), Converter::toList([Services::CLAMAV, Services::REDIS]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains(['solr', '_search'])),
      ],

      'services_none' => [
        static::cw(fn() => Env::put(Services::envName(), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
        }),
      ],

    ];
  }

}
