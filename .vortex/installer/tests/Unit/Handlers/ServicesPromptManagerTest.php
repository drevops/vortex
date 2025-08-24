<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Yaml;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(Services::class)]
class ServicesPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'services - prompt' => [
        [Services::id() => Key::ENTER],
        [Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY]] + $expected_defaults,
      ],

      'services - discovery - solr' => [
        [],
        [Services::id() => [Services::SOLR]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::SOLR => []]]));
        },
      ],

      'services - discovery - valkey' => [
        [],
        [Services::id() => [Services::VALKEY]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::VALKEY => []]]));
        },
      ],

      'services - discovery - clamav' => [
        [],
        [Services::id() => [Services::CLAMAV]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::CLAMAV => []]]));
        },
      ],

      'services - discovery - all' => [
        [],
        [
          Services::id() => [Services::CLAMAV, Services::SOLR, Services::VALKEY],
        ] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::CLAMAV => [], Services::VALKEY => [], Services::SOLR => []]]));
        },
      ],

      'services - discovery - none' => [
        [],
        [Services::id() => []] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['other_service' => []]]));
        },
      ],

      'services - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // Invalid YAML causes discovery to fail and fall back to defaults.
          File::dump(static::$sut . '/docker-compose.yml', <<<'YAML'
- !text |
  first line
YAML
          );
        },
      ],

      'services - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::VALKEY => [], Services::CLAMAV => [], Services::SOLR => []]]));
        },
      ],
    ];
  }

}
