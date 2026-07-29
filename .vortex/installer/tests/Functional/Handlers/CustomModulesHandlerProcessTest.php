<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CustomModules;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomModules::class)]
class CustomModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'custom_modules_no_base' => [
      static::cw(function ($test): void {
          $test->prompts[CustomModules::id()] = [CustomModules::SEARCH, CustomModules::DEMO];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_base')),
    ];
    yield 'custom_modules_no_demo' => [
      static::cw(function ($test): void {
          $test->prompts[CustomModules::id()] = [CustomModules::BASE, CustomModules::SEARCH];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('counter_block');
          // The search module indexes and moderates the 'page' bundle, so the
          // content model stays when only the demo module is dropped.
          $test->assertDirectoryExists(static::$sut . '/recipes/page');
          $test->assertFileExists(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/CreateContentModelDeployStep.php');
      }),
    ];
    yield 'custom_modules_base_only' => [
      static::cw(function ($test): void {
          $test->prompts[CustomModules::id()] = [CustomModules::BASE];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('_search');
          $test->assertDirectoryExists(static::$sut . '/web/modules/custom/sw_base');
          $test->assertDirectoryDoesNotExist(static::$sut . '/recipes/page');
          $test->assertFileDoesNotExist(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/CreateContentModelDeployStep.php');
          $test->assertFileNotContainsString(static::$sut . '/.gitignore', '!recipes/page');
      }),
    ];
    yield 'custom_modules_no_search' => [
      static::cw(function ($test): void {
          $test->prompts[CustomModules::id()] = [CustomModules::BASE, CustomModules::DEMO];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_search')),
    ];
    yield 'custom_modules_none' => [
      static::cw(fn($test): array => $test->prompts[CustomModules::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('_base');
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('_search');
          $test->assertDirectoryDoesNotExist(static::$sut . '/recipes/page');
          $test->assertFileNotContainsString(static::$sut . '/.gitignore', '!recipes/page');
      }),
    ];
    yield 'custom_modules_search_without_solr' => [
      static::cw(function ($test): void {
          // Search module selected but Solr service deselected - safety net
          // should force-remove search module.
          $test->prompts[CustomModules::id()] = [CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO];
          $test->prompts[Services::id()] = [Services::CLAMAV, Services::REDIS];
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('_search')),
    ];
  }

}
