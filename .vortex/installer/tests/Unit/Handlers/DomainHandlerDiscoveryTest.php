<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Domain::class)]
class DomainHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'domain - prompt' => [
      [Domain::id() => 'myproject.com'],
      [Domain::id() => 'myproject.com'] + $expected_defaults,
    ];
    yield 'domain - prompt - www prefix' => [
      [Domain::id() => 'www.myproject.com'],
      [Domain::id() => 'myproject.com'] + $expected_defaults,
    ];
    yield 'domain - prompt - secure protocol' => [
      [Domain::id() => 'https://www.myproject.com'],
      [Domain::id() => 'myproject.com'] + $expected_defaults,
    ];
    yield 'domain - prompt - unsecure protocol' => [
      [Domain::id() => 'http://www.myproject.com'],
      [Domain::id() => 'myproject.com'] + $expected_defaults,
    ];
    yield 'domain - invalid - missing TLD' => [
      [Domain::id() => 'myproject'],
      'Please enter a valid domain name.',
    ];
    yield 'domain - invalid - incorrect protocol' => [
      [Domain::id() => 'htt://myproject.com'],
      'Please enter a valid domain name.',
    ];
    yield 'domain - discovery' => [
      [],
      [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'https://discovered-project-dotenv.com');
      },
    ];
    yield 'domain - discovery - www' => [
      [],
      [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'https://www.discovered-project-dotenv.com');
      },
    ];
    yield 'domain - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', '');
      },
    ];
  }

}
