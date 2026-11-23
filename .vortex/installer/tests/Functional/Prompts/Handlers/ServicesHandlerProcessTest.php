<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Services::class)]
class ServicesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'services_no_clamav' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Services::id()] = [Services::SOLR, Services::REDIS];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertFileExists(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
    yield 'services_no_redis' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::SOLR];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('redis')),
    ];
    yield 'services_no_solr' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::REDIS];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('solr');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
    yield 'services_dbui' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::DBUI, Services::REDIS, Services::SOLR];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/docker-compose.yml', 'image: phpmyadmin:');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'VORTEX_HOST_DBUI_PORT');
          $test->assertFileContainsString(static::$sut . '/docker-compose.yml', "PMA_HOSTS: database\n");
          $test->assertSutNotContains('SERVICE_DBUI');
      }),
    ];
    yield 'services_dbui_migration' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::DBUI, Services::REDIS, Services::SOLR];
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/docker-compose.yml', 'PMA_HOSTS: database,database2');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'aliases: [db2ui, dbui2]');
          $test->assertSutNotContains('SERVICE_DBUI');
      }),
    ];
    yield 'services_none' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): array => $test->prompts[Services::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
          $test->assertSutNotContains('phpmyadmin');
          $test->assertFileNotContainsString(static::$sut . '/docker-compose.yml', 'dbui');
          $test->assertSutNotContains('VORTEX_HOST_DBUI_PORT');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/provision-30-search-index.sh');
      }),
    ];
  }

}
