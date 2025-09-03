<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Services::class)]
class ServicesInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'services, no clamav' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::SOLR, Services::VALKEY]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('clamav')),
      ],

      'services, no valkey' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::SOLR]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains(['valkey', 'redis'])),
      ],

      'services, no solr' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Services::id()), Converter::toList([Services::CLAMAV, Services::VALKEY]));
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('solr')),
      ],

      'services, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Services::id()), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains(['valkey', 'redis']);
        }),
      ],

    ];
  }

}
