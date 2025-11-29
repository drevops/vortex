<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Domain::class)]
class DomainHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'domain - prompt' => [
        [Domain::id() => 'myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],

      'domain - prompt - www prefix' => [
        [Domain::id() => 'www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],

      'domain - prompt - secure protocol' => [
        [Domain::id() => 'https://www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],

      'domain - prompt - unsecure protocol' => [
        [Domain::id() => 'http://www.myproject.com'],
        [Domain::id() => 'myproject.com'] + $expected_defaults,
      ],

      'domain - invalid - missing TLD' => [
        [Domain::id() => 'myproject'],
        'Please enter a valid domain name.',
      ],

      'domain - invalid - incorrect protocol' => [
        [Domain::id() => 'htt://myproject.com'],
        'Please enter a valid domain name.',
      ],

      'domain - discovery' => [
        [],
        [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'https://discovered-project-dotenv.com');
        },
      ],

      'domain - discovery - www' => [
        [],
        [Domain::id() => 'discovered-project-dotenv.com'] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', 'https://www.discovered-project-dotenv.com');
        },
      ],

      'domain - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('DRUPAL_STAGE_FILE_PROXY_ORIGIN', '');
        },
      ],
    ];
  }

}
