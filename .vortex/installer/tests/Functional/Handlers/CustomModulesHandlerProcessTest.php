<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CustomModules;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomModules::class)]
class CustomModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'custom_modules_no_base' => [
        static::cw(function (): void {
          Env::put(CustomModules::envName(), Converter::toList([CustomModules::SEARCH, CustomModules::DEMO]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_base')),
      ],

      'custom_modules_no_demo' => [
        static::cw(function (): void {
          Env::put(CustomModules::envName(), Converter::toList([CustomModules::BASE, CustomModules::SEARCH]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('counter_block');
        }),
      ],

      'custom_modules_no_search' => [
        static::cw(function (): void {
          Env::put(CustomModules::envName(), Converter::toList([CustomModules::BASE, CustomModules::DEMO]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_search')),
      ],

      'custom_modules_none' => [
        static::cw(fn() => Env::put(CustomModules::envName(), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('_base');
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('_search');
        }),
      ],

      'custom_modules_search_without_solr' => [
        static::cw(function (): void {
          // Search module selected but Solr service deselected - safety net
          // should force-remove search module.
          Env::put(CustomModules::envName(), Converter::toList([CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO]));
          Env::put(Services::envName(), Converter::toList([Services::CLAMAV, Services::REDIS]));
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_search')),
      ],
    ];
  }

}
