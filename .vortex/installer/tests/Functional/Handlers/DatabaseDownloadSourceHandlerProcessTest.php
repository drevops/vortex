<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
class DatabaseDownloadSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderInstall(): array {
    return [
      'db download source, url' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::URL)),
      ],

      'db download source, ftp' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::FTP)),
      ],

      'db download source, acquia' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::ACQUIA)),
      ],

      'db download source, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::LAGOON)),
      ],

      'db download source, container_registry' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DatabaseDownloadSource::id()), DatabaseDownloadSource::CONTAINER_REGISTRY);
          Env::put(PromptManager::makeEnvName(DatabaseImage::id()), 'the_empire/star_wars:latest');
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
    ];
  }

}
