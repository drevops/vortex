<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tools::class)]
class ToolsHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'tools - prompt - defaults' => [
        [Tools::id() => Key::ENTER],
        [Tools::id() => [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR, Tools::PHPUNIT, Tools::BEHAT]] + $expected_defaults,
      ],

      'tools - discovery - all tools' => [
        [],
        [Tools::id() => [Tools::BEHAT, Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::PHPUNIT, Tools::RECTOR]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $dependencies = [
            'squizlabs/php_codesniffer' => '*',
            'phpmd/phpmd' => '*',
            'phpstan/phpstan' => '*',
            'rector/rector' => '*',
            'phpunit/phpunit' => '*',
            'behat/behat' => '*',
          ];
          $test->stubComposerJsonDependencies($dependencies, TRUE);
        },
      ],

      'tools - discovery - none' => [
        [],
        [Tools::id() => []] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // No tool dependencies in composer.json.
        },
      ],

      'tools - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $dependencies = [
            'squizlabs/php_codesniffer' => '*',
            'phpmd/phpmd' => '*',
            'phpstan/phpstan' => '*',
            'rector/rector' => '*',
            'phpunit/phpunit' => '*',
            'behat/behat' => '*',
          ];
          $test->stubComposerJsonDependencies($dependencies, TRUE);
        },
      ],

      'tools - discovery - phpcs' => [
        [],
        [Tools::id() => [Tools::PHPCS]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['drupal/coder' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpcs, alt' => [
        [],
        [Tools::id() => [Tools::PHPCS]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['squizlabs/php_codesniffer' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpcs, alt2' => [
        [],
        [Tools::id() => [Tools::PHPCS]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/phpcs.xml');
        },
      ],

      'tools - discovery - phpstan' => [
        [],
        [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpstan/phpstan' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpstan, alt' => [
        [],
        [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['mglaman/phpstan-drupal' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpstan, alt2' => [
        [],
        [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/phpstan.neon');
        },
      ],

      'tools - discovery - rector' => [
        [],
        [Tools::id() => [Tools::RECTOR]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['rector/rector' => '*'], TRUE);
        },
      ],
      'tools - discovery - rector, alt' => [
        [],
        [Tools::id() => [Tools::RECTOR]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['palantirnet/drupal-rector' => '*'], TRUE);
        },
      ],
      'tools - discovery - rector, alt2' => [
        [],
        [Tools::id() => [Tools::RECTOR]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/rector.php');
        },
      ],

      'tools - discovery - phpmd' => [
        [],
        [Tools::id() => [Tools::PHPMD]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpmd/phpmd' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpmd, alt' => [
        [],
        [Tools::id() => [Tools::PHPMD]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/phpmd.xml');
        },
      ],

      'tools - discovery - phpunit' => [
        [],
        [Tools::id() => [Tools::PHPUNIT]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpunit/phpunit' => '*'], TRUE);
        },
      ],
      'tools - discovery - phpunit, alt' => [
        [],
        [Tools::id() => [Tools::PHPUNIT]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/phpunit.xml');
        },
      ],

      'tools - discovery - behat' => [
        [],
        [Tools::id() => [Tools::BEHAT]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['behat/behat' => '*'], TRUE);
        },
      ],
      'tools - discovery - behat, alt' => [
        [],
        [Tools::id() => [Tools::BEHAT]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['drupal/drupal-extension' => '*'], TRUE);
        },
      ],
      'tools - discovery - behat, alt2' => [
        [],
        [Tools::id() => [Tools::BEHAT]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/behat.yml');
        },
      ],
    ];
  }

}
