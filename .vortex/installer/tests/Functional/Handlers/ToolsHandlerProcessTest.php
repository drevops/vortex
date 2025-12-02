<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tools::class)]
class ToolsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'tools, none' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList([]));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains([
            'phpcs',
            'phpcbf',
            'phpstan',
            'rector',
            'phpunit',
            'behat',
            'gherkinlint',
            'bdd',
            'lint-be:',
            'lint-be-fix:',
            'lint-tests:',
            'test:',
            'test-unit:',
            'test-kernel:',
            'test-functional:',
            'test-bdd:',
          ]);

          $test->assertSutContains([
            '/\blint:/',
            '/\blint-fe:/',
            '/\blint-fix:/',
            '/\blint-fe-fix:/',
          ]);
        }),
      ],

      'tools, no phpcs' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPCS])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpcs',
          'phpcbf',
          'dealerdirect/phpcodesniffer-composer-installer',
          'drupal/coder',
          'squizlabs/php_codesniffer',
        ])),
      ],

      'tools, no phpcs, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPCS])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpcs',
          'phpcbf',
          'dealerdirect/phpcodesniffer-composer-installer',
          'drupal/coder',
          'squizlabs/php_codesniffer',
        ])),
      ],

      'tools, no phpstan' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPSTAN])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpstan',
          'phpstan/phpstan',
          'mglaman/phpstan-drupal',
        ])),
      ],

      'tools, no phpstan, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPSTAN])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpstan',
          'phpstan/phpstan',
          'mglaman/phpstan-drupal',
        ])),
      ],

      'tools, no rector' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::RECTOR])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, no rector, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::RECTOR])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, no phpmd' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPMD])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpmd',
          'phpmd/phpmd',
        ])),
      ],

      'tools, no phpmd, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPMD])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpmd',
          'phpmd/phpmd',
        ])),
      ],

      'tools, no phpunit' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPUNIT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpunit',
          'ahoy test-unit',
          'ahoy test-kernel',
          'ahoy test-functional',
        ])),
      ],

      'tools, no phpunit, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPUNIT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpunit',
          'ahoy test-unit',
          'ahoy test-kernel',
          'ahoy test-functional',
        ])),
      ],

      'tools, no behat' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::BEHAT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'behat',
          'behat/behat',
          'drupal/drupal-extension',
          'ahoy test-bdd',
          'gherkinlint',
          'gherkin-lint',
          'gherkin',
          'bdd',
        ])),
      ],

      'tools, no behat, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::BEHAT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'behat',
          'behat/behat',
          'drupal/drupal-extension',
          'ahoy test-bdd',
          'gherkinlint',
          'gherkin-lint',
          'gherkin',
        ])),
      ],

      'tools, groups, no be lint' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpcs',
          'phpcbf',
          'dealerdirect/phpcodesniffer-composer-installer',
          'drupal/coder',
          'squizlabs/php_codesniffer',
          'phpmd',
          'phpmd/phpmd',
          'phpstan',
          'phpstan/phpstan',
          'mglaman/phpstan-drupal',
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, groups, no be lint, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpcs',
          'phpcbf',
          'dealerdirect/phpcodesniffer-composer-installer',
          'drupal/coder',
          'squizlabs/php_codesniffer',
          'phpmd',
          'phpmd/phpmd',
          'phpstan',
          'phpstan/phpstan',
          'mglaman/phpstan-drupal',
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, groups, no be tests' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpunit',
          'ahoy test-unit',
          'ahoy test-kernel',
          'ahoy test-functional',
          'behat',
          'behat/behat',
          'drupal/drupal-extension',
          'ahoy test-bdd',
          'gherkinlint',
          'gherkin-lint',
          'gherkin',
        ])),
      ],

      'tools, groups, no be tests, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(PromptManager::makeEnvName(Tools::id()), Converter::toList(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT])));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpunit',
          'ahoy test-unit',
          'ahoy test-kernel',
          'ahoy test-functional',
          'behat',
          'behat/behat',
          'drupal/drupal-extension',
          'ahoy test-bdd',
          'gherkinlint',
          'gherkin-lint',
          'gherkin',
        ])),
      ],
    ];
  }

}
