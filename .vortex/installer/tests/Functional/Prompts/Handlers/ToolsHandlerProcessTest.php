<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

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
            'twig-cs-fixer',
            'vincentlanglet/twig-cs-fixer',
            'phpunit',
            'behat',
            'gherkinlint',
            'dclint',
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
          $test->assertFileDoesNotExist(static::$sut . '/package-lock.json');
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertFileDoesNotExist(static::$sut . '/.dclintrc');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'hadolint');

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
    yield 'tools_no_twig' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::TWIG_CS_FIXER]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'twig-cs-fixer',
        'vincentlanglet/twig-cs-fixer',
      ])),
    ];
    yield 'tools_no_twig_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::TWIG_CS_FIXER]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'twig-cs-fixer',
        'vincentlanglet/twig-cs-fixer',
      ])),
    ];
    yield 'tools_no_dclint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::DCLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['dclint']);
          $test->assertFileDoesNotExist(static::$sut . '/.dclintrc');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_CI_DCLINT_IGNORE_FAILURE');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'hadolint');
      }),
    ];
    yield 'tools_no_dclint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::DCLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['dclint']);
          $test->assertFileDoesNotExist(static::$sut . '/.dclintrc');
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', 'VORTEX_CI_DCLINT_IGNORE_FAILURE');
          $test->assertFileContainsString(static::$sut . '/.circleci/config.yml', 'hadolint');
      }),
    ];
    yield 'tools_no_hadolint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::HADOLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $ci = static::$sut . '/.github/workflows/build-test-deploy.yml';
          $test->assertFileNotContainsString($ci, 'hadolint');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_HADOLINT_IGNORE_FAILURE');
          $test->assertFileContainsString($ci, 'dclint');
          $test->assertFileExists(static::$sut . '/.dclintrc');
          // Dockerfile directives are inert comments that remain useful when
          // the tool is run by hand, so they survive deselection.
          $test->assertFileContainsString(static::$sut . '/.docker/cli.dockerfile', '# hadolint ignore=');
      }),
    ];
    yield 'tools_no_hadolint_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::HADOLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $ci = static::$sut . '/.circleci/config.yml';
          $test->assertFileNotContainsString($ci, 'hadolint');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_HADOLINT_IGNORE_FAILURE');
          $test->assertFileContainsString($ci, 'dclint');
          $test->assertFileExists(static::$sut . '/.dclintrc');
          $test->assertFileContainsString(static::$sut . '/.docker/cli.dockerfile', '# hadolint ignore=');
      }),
    ];
    yield 'tools_no_docker_linters' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::DCLINT, Tools::HADOLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $ci = static::$sut . '/.github/workflows/build-test-deploy.yml';
          $test->assertSutNotContains(['dclint']);
          $test->assertFileDoesNotExist(static::$sut . '/.dclintrc');
          $test->assertFileNotContainsString($ci, 'hadolint');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_HADOLINT_IGNORE_FAILURE');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_DCLINT_IGNORE_FAILURE');
      }),
    ];
    yield 'tools_no_docker_linters_circleci' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::DCLINT, Tools::HADOLINT]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $ci = static::$sut . '/.circleci/config.yml';
          $test->assertSutNotContains(['dclint']);
          $test->assertFileDoesNotExist(static::$sut . '/.dclintrc');
          $test->assertFileNotContainsString($ci, 'hadolint');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_HADOLINT_IGNORE_FAILURE');
          $test->assertFileNotContainsString($ci, 'VORTEX_CI_DCLINT_IGNORE_FAILURE');
      }),
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
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(static::$sut . '/.stylelintrc.js');

          $tpj = static::themeManifest();
          $test->assertFileNotContainsString($tpj, '"eslint":');
          $test->assertFileNotContainsString($tpj, '"prettier":');
          $test->assertFileContainsString($tpj, '"stylelint":');

          static::assertNpmPairIsInSync($pj);
          static::assertNpmPairIsInSync($tpj);
          static::assertNpmLockLacksPackages($pj, ['eslint', 'prettier']);
          static::assertNpmLockLacksPackages($tpj, ['eslint', 'prettier']);
          static::assertNpmLockHasPackages($pj, ['stylelint', 'jest']);
          static::assertNpmLockHasPackages($tpj, ['stylelint', 'sass']);
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
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
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
          $test->assertFileExists(static::$sut . '/eslint.config.mjs');

          $tpj = static::themeManifest();
          $test->assertFileNotContainsString($tpj, '"stylelint":');
          $test->assertFileNotContainsString($tpj, '"stylelint-scss":');
          $test->assertFileContainsString($tpj, '"eslint":');

          static::assertNpmPairIsInSync($pj);
          static::assertNpmPairIsInSync($tpj);
          static::assertNpmLockLacksPackages($pj, ['stylelint']);
          static::assertNpmLockLacksPackages($tpj, ['stylelint', 'stylelint-scss']);
          static::assertNpmLockHasPackages($pj, ['eslint', 'jest']);
          static::assertNpmLockHasPackages($tpj, ['eslint', 'sass']);
      }),
    ];
    yield 'tools_no_eslint_no_stylelint' => [
      static::cw(function ($test): void {
          $tools = array_keys(Tools::getToolDefinitions('tools'));
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::ESLINT, Tools::STYLELINT]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = static::$sut . '/package.json';
          $tpj = static::themeManifest();

          $test->assertFileNotContainsString($pj, '"eslint":');
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"lint":');
          $test->assertFileNotContainsString($pj, '"lint-fix":');
          $test->assertFileContainsString($pj, '"jest":');

          $test->assertFileNotContainsString($tpj, '"eslint":');
          $test->assertFileNotContainsString($tpj, '"stylelint":');
          $test->assertFileNotContainsString($tpj, '"lint":');
          $test->assertFileNotContainsString($tpj, '"lint-fix":');
          $test->assertFileContainsString($tpj, '"sass":');

          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileExists(static::$sut . '/jest.config.js');

          static::assertNpmPairIsInSync($pj);
          static::assertNpmPairIsInSync($tpj);
          static::assertNpmLockLacksPackages($pj, ['eslint', 'stylelint']);
          static::assertNpmLockLacksPackages($tpj, ['eslint', 'stylelint']);
          static::assertNpmLockHasPackages($pj, ['jest']);
          static::assertNpmLockHasPackages($tpj, ['sass']);

          $test->assertSutContains(['npm ci']);
          $test->assertSutNotContains(['npm run lint']);
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
          $test->assertFileExists(static::$sut . '/eslint.config.mjs');
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
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS, Tools::PHPSTAN, Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
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
          $test->prompts[Tools::id()] = array_values(array_diff($tools, [Tools::PHPCS, Tools::PHPSTAN, Tools::RECTOR]));
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
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

          static::assertNpmPairIsInSync($pj);
          static::assertNpmLockLacksPackages($pj, ['jest', 'jest-environment-jsdom']);
          static::assertNpmLockHasPackages($pj, ['eslint', 'stylelint']);
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
          $test->assertFileDoesNotExist(static::$sut . '/package-lock.json');
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
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
          $test->assertFileDoesNotExist(static::$sut . '/package-lock.json');
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
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
        'publish-unit-test-result-action',
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
        'publish-unit-test-result-action',
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
          $test->assertFileDoesNotExist(static::$sut . '/package-lock.json');
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'npm ci',
            'npm run lint',
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
          $test->assertFileDoesNotExist(static::$sut . '/package-lock.json');
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(static::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(static::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'npm ci',
            'npm run lint',
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
          $test->assertFileExists(static::$sut . '/eslint.config.mjs');
          $test->assertSutContains(['npm ci', 'npm run lint']);
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
          $test->assertFileDoesNotExist(static::$sut . '/eslint.config.mjs');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(static::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(static::$sut . '/.stylelintrc.js');
          $test->assertSutContains(['npm ci', 'npm run lint']);
      }),
    ];
  }

  protected static function themeManifest(): string {
    return static::$sut . '/web/themes/custom/star_wars/package.json';
  }

  /**
   * Assert that a manifest and its lock file declare the same dependencies.
   *
   * This is the condition 'npm ci' refuses to install without.
   */
  protected static function assertNpmPairIsInSync(string $manifest_file): void {
    $manifest = static::readJson($manifest_file);
    $root = static::readJson(dirname($manifest_file) . '/package-lock.json')['packages'][''];

    foreach (['dependencies', 'devDependencies', 'optionalDependencies', 'peerDependencies'] as $block) {
      self::assertSame(
        $manifest[$block] ?? NULL,
        $root[$block] ?? NULL,
        sprintf('The "%s" block of "%s" does not match its lock file.', $block, $manifest_file)
      );
    }
  }

  protected static function assertNpmLockHasPackages(string $manifest_file, array $names): void {
    $packages = static::readJson(dirname($manifest_file) . '/package-lock.json')['packages'];

    foreach ($names as $name) {
      self::assertArrayHasKey('node_modules/' . $name, $packages, sprintf('Package "%s" is missing from the lock file next to "%s".', $name, $manifest_file));
    }
  }

  protected static function assertNpmLockLacksPackages(string $manifest_file, array $names): void {
    $packages = static::readJson(dirname($manifest_file) . '/package-lock.json')['packages'];

    foreach ($names as $name) {
      self::assertArrayNotHasKey('node_modules/' . $name, $packages, sprintf('Package "%s" is still in the lock file next to "%s".', $name, $manifest_file));
    }
  }

  protected static function readJson(string $file): array {
    self::assertFileExists($file);

    return (array) json_decode((string) file_get_contents($file), TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
