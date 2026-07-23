<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class CustomModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'custom_modules_no_base' => [
      self::cw(function ($test): void {
          $test->prompts['custom_modules'] = ['search', 'demo'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('_base')),
    ];
    yield 'custom_modules_no_demo' => [
      self::cw(function ($test): void {
          $test->prompts['custom_modules'] = ['base', 'search'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('counter_block');
      }),
    ];
    yield 'custom_modules_no_search' => [
      self::cw(function ($test): void {
          $test->prompts['custom_modules'] = ['base', 'demo'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('_search')),
    ];
    yield 'custom_modules_none' => [
      self::cw(fn($test): array => $test->prompts['custom_modules'] = []),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('_base');
          $test->assertSutNotContains('_demo');
          $test->assertSutNotContains('_search');
      }),
    ];
    yield 'custom_modules_search_without_solr' => [
      self::cw(function ($test): void {
          // Search module selected but Solr service deselected - safety net
          // should force-remove search module.
          $test->prompts['custom_modules'] = ['base', 'search', 'demo'];
          $test->prompts['services'] = ['clamav', 'redis'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('_search')),
    ];
  }

}
