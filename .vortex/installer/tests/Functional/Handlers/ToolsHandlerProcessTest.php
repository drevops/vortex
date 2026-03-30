<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tools::class)]
class ToolsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'tools_none' => [
      static::cw(function ($test): void {
          $test->prompts[Tools::id()] = [];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
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
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');

          $test->assertSutContains([
            '/\blint-fe:/',
            '/\blint-fe-fix:/',
          ]);
      }),
    ];
    yield 'tools_no_phpcs' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
      ])),
    ];
    yield 'tools_no_phpcs_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
      ])),
    ];
    yield 'tools_no_phpstan' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPSTAN]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpstan',
        'phpstan/phpstan',
        'mglaman/phpstan-drupal',
      ])),
    ];
    yield 'tools_no_phpstan_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPSTAN]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpstan',
        'phpstan/phpstan',
        'mglaman/phpstan-drupal',
      ])),
    ];
    yield 'tools_no_rector' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'rector',
        'rector/rector',
      ])),
    ];
    yield 'tools_no_rector_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'rector',
        'rector/rector',
      ])),
    ];
    yield 'tools_no_phpmd' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPMD]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpmd',
        'phpmd/phpmd',
      ])),
    ];
    yield 'tools_no_phpmd_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPMD]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpmd',
        'phpmd/phpmd',
      ])),
    ];
    yield 'tools_no_eslint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
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
    ];
    yield 'tools_no_eslint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
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
    ];
    yield 'tools_no_stylelint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::STYLELINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
      }),
    ];
    yield 'tools_no_stylelint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::STYLELINT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
      }),
    ];
    yield 'tools_no_phpunit' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPUNIT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpunit',
        'ahoy test-unit',
        'ahoy test-kernel',
        'ahoy test-functional',
      ])),
    ];
    yield 'tools_no_phpunit_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPUNIT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpunit',
        'ahoy test-unit',
        'ahoy test-kernel',
        'ahoy test-functional',
      ])),
    ];
    yield 'tools_no_behat' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::BEHAT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
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
    ];
    yield 'tools_no_behat_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::BEHAT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
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
    ];
    yield 'tools_groups_no_be_lint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
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
    ];
    yield 'tools_groups_no_be_lint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS, Tools::PHPMD, Tools::PHPSTAN, Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
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
    ];
    yield 'tools_no_jest' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"jest":');
          $test->assertFileNotContainsString($pj, '"jest-environment-jsdom":');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileContainsString($pj, '"stylelint":');
      }),
    ];
    yield 'tools_no_jest_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"jest":');
          $test->assertFileNotContainsString($pj, '"jest-environment-jsdom":');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileContainsString($pj, '"stylelint":');
      }),
    ];
    yield 'tools_groups_no_fe_lint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT, Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
      }),
    ];
    yield 'tools_groups_no_fe_lint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT, Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
      }),
    ];
    yield 'tools_groups_no_be_tests' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
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
    ];
    yield 'tools_groups_no_be_tests_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPUNIT, Tools::BEHAT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
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
    ];
    yield 'tools_groups_no_fe_lint_no_theme' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT, Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
          $test->prompts[Theme::id()] = Theme::OLIVERO;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
      }),
    ];
    yield 'tools_groups_no_fe_lint_no_theme_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT, Tools::JEST]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
          $test->prompts[Theme::id()] = Theme::OLIVERO;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/package.json');
          $test->assertFileDoesNotExist(static::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
      }),
    ];
    yield 'tools_no_stylelint_no_theme' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::STYLELINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
          $test->prompts[Theme::id()] = Theme::OLIVERO;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(static::$sut . '/.eslintrc.json');
          $test->assertSutContains(['yarn install', 'yarn run lint']);
      }),
    ];
    yield 'tools_no_eslint_no_theme' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
          $test->prompts[Theme::id()] = Theme::OLIVERO;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
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
    ];
  }

}
