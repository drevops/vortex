<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ToolsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'tools_none' => [
      self::cw(function ($test): void {
          $test->prompts['tools'] = [];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
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
            '/\btest-unit:/',
            '/\btest-kernel:/',
            '/\btest-functional:/',
            '/\btest-bdd:/',
          ]);

          $test->assertFileDoesNotExist(self::$sut . '/package.json');
          $test->assertFileDoesNotExist(self::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');

          $test->assertSutContains([
            '/\blint-fe:/',
            '/\blint-fe-fix:/',
          ]);
      }),
    ];
    yield 'tools_no_phpcs' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpcs']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
      ])),
    ];
    yield 'tools_no_phpcs_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpcs']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpcs',
        'phpcbf',
        'dealerdirect/phpcodesniffer-composer-installer',
        'drupal/coder',
        'squizlabs/php_codesniffer',
      ])),
    ];
    yield 'tools_no_phpstan' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpstan']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpstan',
        'phpstan/phpstan',
        'mglaman/phpstan-drupal',
      ])),
    ];
    yield 'tools_no_phpstan_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpstan']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpstan',
        'phpstan/phpstan',
        'mglaman/phpstan-drupal',
      ])),
    ];
    yield 'tools_no_rector' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['rector']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'rector',
        'rector/rector',
      ])),
    ];
    yield 'tools_no_rector_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['rector']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'rector',
        'rector/rector',
      ])),
    ];
    yield 'tools_no_eslint' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
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
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(self::$sut . '/.stylelintrc.js');
      }),
    ];
    yield 'tools_no_eslint_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
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
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(self::$sut . '/.stylelintrc.js');
      }),
    ];
    yield 'tools_no_stylelint' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['stylelint']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(self::$sut . '/.eslintrc.json');
      }),
    ];
    yield 'tools_no_stylelint_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['stylelint']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(self::$sut . '/.eslintrc.json');
      }),
    ];
    yield 'tools_no_phpunit' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpunit']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpunit',
        'ahoy test-unit',
        'ahoy test-kernel',
        'ahoy test-functional',
      ])),
    ];
    yield 'tools_no_phpunit_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpunit']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'phpunit',
        'ahoy test-unit',
        'ahoy test-kernel',
        'ahoy test-functional',
      ])),
    ];
    yield 'tools_no_behat' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['behat']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['behat']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpcs', 'phpstan', 'rector']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpcs', 'phpstan', 'rector']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['jest']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"jest":');
          $test->assertFileNotContainsString($pj, '"jest-environment-jsdom":');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileContainsString($pj, '"stylelint":');
      }),
    ];
    yield 'tools_no_jest_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['jest']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"jest":');
          $test->assertFileNotContainsString($pj, '"jest-environment-jsdom":');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileContainsString($pj, '"stylelint":');
      }),
    ];
    yield 'tools_groups_no_fe_lint' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint', 'stylelint', 'jest']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/package.json');
          $test->assertFileDoesNotExist(self::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
      }),
    ];
    yield 'tools_groups_no_fe_lint_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint', 'stylelint', 'jest']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/package.json');
          $test->assertFileDoesNotExist(self::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
      }),
    ];
    yield 'tools_groups_no_be_tests' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpunit', 'behat']));
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['phpunit', 'behat']));
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
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
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint', 'stylelint', 'jest']));
          $test->prompts['ci_provider'] = 'gha';
          $test->prompts['theme'] = 'olivero';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/package.json');
          $test->assertFileDoesNotExist(self::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
      }),
    ];
    yield 'tools_groups_no_fe_lint_no_theme_circleci' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint', 'stylelint', 'jest']));
          $test->prompts['ci_provider'] = 'circleci';
          $test->prompts['theme'] = 'olivero';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/package.json');
          $test->assertFileDoesNotExist(self::$sut . '/yarn.lock');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileDoesNotExist(self::$sut . '/jest.config.js');
          $test->assertSutNotContains([
            'yarn install',
            'yarn run lint',
            'ahoy fei',
            '/\bfei:/',
          ]);
      }),
    ];
    yield 'tools_no_stylelint_no_theme' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['stylelint']));
          $test->prompts['ci_provider'] = 'gha';
          $test->prompts['theme'] = 'olivero';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"stylelint":');
          $test->assertFileNotContainsString($pj, '"stylelint-config-standard":');
          $test->assertFileNotContainsString($pj, '"stylelint-order":');
          $test->assertFileDoesNotExist(self::$sut . '/.stylelintrc.js');
          $test->assertFileContainsString($pj, '"eslint":');
          $test->assertFileExists(self::$sut . '/.eslintrc.json');
          $test->assertSutContains(['yarn install', 'yarn run lint']);
      }),
    ];
    yield 'tools_no_eslint_no_theme' => [
      self::cw(function ($test): void {
          $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'jest', 'phpunit', 'behat'];
          $test->prompts['tools'] = array_values(array_diff($tools, ['eslint']));
          $test->prompts['ci_provider'] = 'gha';
          $test->prompts['theme'] = 'olivero';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $pj = self::$sut . '/package.json';
          $test->assertFileNotContainsString($pj, '"eslint":');
          $test->assertFileNotContainsString($pj, '"eslint-config-airbnb-base":');
          $test->assertFileNotContainsString($pj, '"prettier":');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.eslintignore');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierrc.json');
          $test->assertFileDoesNotExist(self::$sut . '/.prettierignore');
          $test->assertFileContainsString($pj, '"stylelint":');
          $test->assertFileExists(self::$sut . '/.stylelintrc.js');
          $test->assertSutContains(['yarn install', 'yarn run lint']);
      }),
    ];
  }

}
