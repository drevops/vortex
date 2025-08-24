<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tools::class)]
class ToolsPromptManagerTest extends AbstractPromptManagerTestCase {

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
        function (AbstractPromptManagerTestCase $test, Config $config): void {
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
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // No tool dependencies in composer.json.
        },
      ],

      'tools - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
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
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['squizlabs/php_codesniffer' => '*'], TRUE);
        },
      ],

      'tools - discovery - phpmd' => [
        [],
        [Tools::id() => [Tools::PHPMD]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpmd/phpmd' => '*'], TRUE);
        },
      ],

      'tools - discovery - phpstan' => [
        [],
        [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpstan/phpstan' => '*'], TRUE);
        },
      ],
      'tools - discovery - rector' => [
        [],
        [Tools::id() => [Tools::RECTOR]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['rector/rector' => '*'], TRUE);
        },
      ],

      'tools - discovery - phpunit' => [
        [],
        [Tools::id() => [Tools::PHPUNIT]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['phpunit/phpunit' => '*'], TRUE);
        },
      ],

      'tools - discovery - behat' => [
        [],
        [Tools::id() => [Tools::BEHAT]] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubComposerJsonDependencies(['behat/behat' => '*'], TRUE);
        },
      ],
    ];
  }

}
