<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
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
          Env::put(Tools::envName(), Converter::toList([]));
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
            '/\blint-be:/',
            '/\blint-be-fix:/',
            '/\blint-tests:/',
            '/\btest:/',
            '/\btest-unit:/',
            '/\btest-kernel:/',
            '/\btest-functional:/',
            '/\btest-bdd:/',
          ]);

          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');

          $test->assertSutContains([
            '/\blint-fe:/',
            '/\blint-fe-fix:/',
          ]);
        }),
      ],

      'tools, no phpcs' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPCS])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPCS])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPSTAN])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPSTAN])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::RECTOR])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, no rector, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::RECTOR])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'rector',
          'rector/rector',
        ])),
      ],

      'tools, no phpmd' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPMD])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpmd',
          'phpmd/phpmd',
        ])),
      ],

      'tools, no phpmd, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPMD])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'phpmd',
          'phpmd/phpmd',
        ])),
      ],

      'tools, no eslint' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"eslint":');
          $test->assertFileNotContainsString($pj, '"eslint-config-airbnb-base":');
          $test->assertFileNotContainsString($pj, '"eslint-config-prettier":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-import":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-jsdoc":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-no-jquery":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-prettier":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-yml":');
          $test->assertFileNotContainsString($pj, '"prettier":');
          $test->assertFileNotContainsString($pj, '"@homer0/prettier-plugin-jsdoc":');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(static::$sut . '/.stylelintrc.js');
        }),
      ],

      'tools, no eslint, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"eslint":');
          $test->assertFileNotContainsString($pj, '"eslint-config-airbnb-base":');
          $test->assertFileNotContainsString($pj, '"eslint-config-prettier":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-import":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-jsdoc":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-no-jquery":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-prettier":');
          $test->assertFileNotContainsString($pj, '"eslint-plugin-yml":');
          $test->assertFileNotContainsString($pj, '"prettier":');
          $test->assertFileNotContainsString($pj, '"@homer0/prettier-plugin-jsdoc":');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(static::$sut . '/.stylelintrc.js');
        }),
      ],

      'tools, no stylelint' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
        }),
      ],

      'tools, no stylelint, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
        }),
      ],

      'tools, no phpunit' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPUNIT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPUNIT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::BEHAT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::BEHAT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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

      'tools, groups, no fe lint' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
        }),
      ],

      'tools, groups, no fe lint, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
        }),
      ],

      'tools, groups, no be tests' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
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
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
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

      'tools, groups, no fe lint, no theme' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
          Env::put(Theme::envName(), Theme::OLIVERO);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
        }),
      ],

      'tools, groups, no fe lint, no theme, circleci' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
          Env::put(Theme::envName(), Theme::OLIVERO);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
        }),
      ],

      'tools, no stylelint, no theme' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::STYLELINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
          Env::put(Theme::envName(), Theme::OLIVERO);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
          $test->assertSutContains(['yarn install', 'yarn run lint']);
        }),
      ],

      'tools, no eslint, no theme' => [
        static::cw(function (): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          Env::put(Tools::envName(), Converter::toList(array_diff($tools, [Tools::ESLINT])));
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
          Env::put(Theme::envName(), Theme::OLIVERO);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"eslint":');
          $test->assertFileNotContainsString($pj, '"eslint-config-airbnb-base":');
          $test->assertFileNotContainsString($pj, '"prettier":');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(static::$sut . '/.stylelintrc.js');
          $test->assertSutContains(['yarn install', 'yarn run lint']);
        }),
      ],
    ];
  }

}
