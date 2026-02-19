<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
class DatabaseDownloadSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'db download source, url' => [
        static::cw(fn() => Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::URL)),
      ],

      'db download source, ftp' => [
        static::cw(fn() => Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::FTP)),
      ],

      'db download source, acquia' => [
        static::cw(fn() => Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::ACQUIA)),
      ],

      'db download source, lagoon' => [
        static::cw(fn() => Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::LAGOON)),
      ],

      'db download source, container_registry' => [
        static::cw(function (): void {
          Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::CONTAINER_REGISTRY);
          Env::put(DatabaseImage::envName(), 'the_empire/star_wars:latest');
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
      ],

      'db download source, s3' => [
        static::cw(fn() => Env::put(DatabaseDownloadSource::envName(), DatabaseDownloadSource::S3)),
      ],
    ];
  }

}
