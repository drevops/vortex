<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ServicesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'services_no_clamav' => [
      self::cw(function ($test): void {
          $test->prompts['services'] = ['solr', 'redis'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('clamav')),
    ];
    yield 'services_no_redis' => [
      self::cw(function ($test): void {
          $test->prompts['services'] = ['clamav', 'solr'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('redis')),
    ];
    yield 'services_no_solr' => [
      self::cw(function ($test): void {
          $test->prompts['services'] = ['clamav', 'redis'];
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('solr')),
    ];
    yield 'services_none' => [
      self::cw(fn($test): array => $test->prompts['services'] = []),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('clamav');
          $test->assertSutNotContains('solr');
          $test->assertSutNotContains('redis');
      }),
    ];
  }

}
