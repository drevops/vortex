<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseFetchSource::class)]
#[CoversClass(DatabaseImage::class)]
class DatabaseFetchSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'db_fetch_source_url' => [
      static::cw(fn($test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::URL),
    ];
    yield 'db_fetch_source_ftp' => [
      static::cw(fn($test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::FTP),
    ];
    yield 'db_fetch_source_acquia' => [
      static::cw(fn($test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::ACQUIA),
    ];
    yield 'db_fetch_source_lagoon' => [
      static::cw(fn($test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::LAGOON),
    ];
    yield 'db_fetch_source_container_registry' => [
      static::cw(function ($test): void {
          $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::CONTAINER_REGISTRY;
          $test->prompts[DatabaseImage::id()] = 'the_empire/star_wars:latest';
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
    ];
    yield 'db_fetch_source_s3' => [
      static::cw(fn($test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::S3),
    ];
  }

}
