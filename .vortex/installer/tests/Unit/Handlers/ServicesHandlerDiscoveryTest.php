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
class ServicesHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'services - prompt' => [
      [Services::id() => Key::ENTER],
      [Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR]] + $expected_defaults,
    ];
    yield 'services - discovery - solr' => [
      [],
      [Services::id() => [Services::SOLR]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::SOLR => []]]));
      },
    ];
    yield 'services - discovery - redis' => [
      [],
      [Services::id() => [Services::REDIS]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::REDIS => []]]));
      },
    ];
    yield 'services - discovery - clamav' => [
      [],
      [Services::id() => [Services::CLAMAV]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::CLAMAV => []]]));
      },
    ];
    yield 'services - discovery - all' => [
      [],
      [
        Services::id() => [Services::CLAMAV, Services::REDIS, Services::SOLR],
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::CLAMAV => [], Services::REDIS => [], Services::SOLR => []]]));
      },
    ];
    yield 'services - discovery - none' => [
      [],
      [Services::id() => []] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['other_service' => []]]));
      },
    ];
    yield 'services - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // Invalid YAML causes discovery to fail and fall back to defaults.
        File::dump(static::$sut . '/docker-compose.yml', <<<'YAML'
- !text |
  first line
YAML
        );
      },
    ];
    yield 'services - discovery - non-Vortex project' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => [Services::REDIS => [], Services::CLAMAV => [], Services::SOLR => []]]));
      },
    ];
  }

}
