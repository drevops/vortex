<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseDownloadSource::class)]
#[CoversClass(DatabaseImage::class)]
class DatabaseDownloadSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'db_download_source_url' => [
      static::cw(fn($test): string => $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::URL),
    ];
    yield 'db_download_source_ftp' => [
      static::cw(fn($test): string => $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::FTP),
    ];
    yield 'db_download_source_acquia' => [
      static::cw(fn($test): string => $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::ACQUIA),
    ];
    yield 'db_download_source_lagoon' => [
      static::cw(fn($test): string => $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::LAGOON),
    ];
    yield 'db_download_source_container_registry' => [
      static::cw(function ($test): void {
          $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::CONTAINER_REGISTRY;
          $test->prompts[DatabaseImage::id()] = 'the_empire/star_wars:latest';
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
    ];
    yield 'db_download_source_s3' => [
      static::cw(fn($test): string => $test->prompts[DatabaseDownloadSource::id()] = DatabaseDownloadSource::S3),
    ];
  }

}
