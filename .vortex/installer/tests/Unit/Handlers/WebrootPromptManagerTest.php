<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Webroot::class)]
class WebrootPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'webroot - prompt' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my_webroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'my_webroot',
        ] + $expected_defaults,
      ],

      'webroot - prompt - capitalization' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'MyWebroot',
        ],
        [
          HostingProvider::id() => HostingProvider::OTHER,
          Webroot::id() => 'MyWebroot',
        ] + $expected_defaults,
      ],

      'webroot - prompt - invalid' => [
        [
          HostingProvider::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          Webroot::id() => 'my webroot',
        ],
        'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'webroot - discovery' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('WEBROOT', 'discovered_webroot');
        },
      ],

      'webroot - discovery - composer' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubComposerJsonValue('extra', ['drupal-scaffold' => ['locations' => ['web-root' => 'discovered_webroot']]]);
        },
      ],

      'webroot - discovery - composer, relative' => [
        [],
        [Webroot::id() => 'discovered_webroot'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubComposerJsonValue('extra', ['drupal-scaffold' => ['locations' => ['web-root' => './discovered_webroot']]]);
        },
      ],

      'webroot - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No WEBROOT in .env and no composer.json scaffold - fall back.
        },
      ],
    ];
  }

}
