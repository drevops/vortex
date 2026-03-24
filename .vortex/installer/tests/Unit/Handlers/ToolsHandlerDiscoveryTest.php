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

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'tools - prompt - defaults' => [
      [Tools::id() => Key::ENTER],
      [Tools::id() => [Tools::BEHAT, Tools::ESLINT, Tools::JEST, Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::PHPUNIT, Tools::RECTOR, Tools::STYLELINT]] + $expected_defaults,
    ];
    yield 'tools - discovery - all tools' => [
      [],
      [Tools::id() => [Tools::BEHAT, Tools::ESLINT, Tools::JEST, Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::PHPUNIT, Tools::RECTOR, Tools::STYLELINT]] + $expected_installed,
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
        file_put_contents(static::$sut . '/package.json', json_encode(['devDependencies' => ['eslint' => '*', 'jest' => '*', 'stylelint' => '*']], JSON_PRETTY_PRINT));
      },
    ];
    yield 'tools - discovery - none' => [
      [],
      [Tools::id() => []] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        // No tool dependencies in composer.json.
      },
    ];
    yield 'tools - discovery - non-Vortex project' => [
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
    ];
    yield 'tools - discovery - phpcs' => [
      [],
      [Tools::id() => [Tools::PHPCS]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['drupal/coder' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpcs, alt' => [
      [],
      [Tools::id() => [Tools::PHPCS]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['squizlabs/php_codesniffer' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpcs, alt2' => [
      [],
      [Tools::id() => [Tools::PHPCS]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/phpcs.xml');
      },
    ];
    yield 'tools - discovery - phpstan' => [
      [],
      [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['phpstan/phpstan' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpstan, alt' => [
      [],
      [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['mglaman/phpstan-drupal' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpstan, alt2' => [
      [],
      [Tools::id() => [Tools::PHPSTAN]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/phpstan.neon');
      },
    ];
    yield 'tools - discovery - rector' => [
      [],
      [Tools::id() => [Tools::RECTOR]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['rector/rector' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - rector, alt' => [
      [],
      [Tools::id() => [Tools::RECTOR]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['palantirnet/drupal-rector' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - rector, alt2' => [
      [],
      [Tools::id() => [Tools::RECTOR]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/rector.php');
      },
    ];
    yield 'tools - discovery - phpmd' => [
      [],
      [Tools::id() => [Tools::PHPMD]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['phpmd/phpmd' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpunit' => [
      [],
      [Tools::id() => [Tools::PHPUNIT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['phpunit/phpunit' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - phpunit, alt' => [
      [],
      [Tools::id() => [Tools::PHPUNIT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/phpunit.xml');
      },
    ];
    yield 'tools - discovery - behat' => [
      [],
      [Tools::id() => [Tools::BEHAT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['behat/behat' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - behat, alt' => [
      [],
      [Tools::id() => [Tools::BEHAT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubComposerJsonDependencies(['drupal/drupal-extension' => '*'], TRUE);
      },
    ];
    yield 'tools - discovery - behat, alt2' => [
      [],
      [Tools::id() => [Tools::BEHAT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/behat.yml');
      },
    ];
    yield 'tools - discovery - jest' => [
      [],
      [Tools::id() => [Tools::JEST]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        file_put_contents(static::$sut . '/package.json', json_encode(['devDependencies' => ['jest' => '*']], JSON_PRETTY_PRINT));
      },
    ];
    yield 'tools - discovery - jest, alt' => [
      [],
      [Tools::id() => [Tools::JEST]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/jest.config.js');
      },
    ];
    yield 'tools - discovery - eslint' => [
      [],
      [Tools::id() => [Tools::ESLINT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        file_put_contents(static::$sut . '/package.json', json_encode(['devDependencies' => ['eslint' => '*']], JSON_PRETTY_PRINT));
      },
    ];
    yield 'tools - discovery - eslint, alt' => [
      [],
      [Tools::id() => [Tools::ESLINT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.eslintrc.json');
      },
    ];
    yield 'tools - discovery - stylelint' => [
      [],
      [Tools::id() => [Tools::STYLELINT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        file_put_contents(static::$sut . '/package.json', json_encode(['devDependencies' => ['stylelint' => '*']], JSON_PRETTY_PRINT));
      },
    ];
    yield 'tools - discovery - stylelint, alt' => [
      [],
      [Tools::id() => [Tools::STYLELINT]] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.stylelintrc.js');
      },
    ];
  }

}
