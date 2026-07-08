<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use AlexSkrypnyk\File\ContentFile\ContentFile;
use AlexSkrypnyk\File\Replacer\Replacement;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\JsonManipulator;
use DrevOps\VortexCli\Utils\Strings;
use DrevOps\VortexCli\Utils\Yaml;

/**
 * Handler for the "tools" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Tools extends AbstractHandler {

  const PHPCS = 'phpcs';

  const PHPSTAN = 'phpstan';

  const RECTOR = 'rector';

  const ESLINT = 'eslint';

  const STYLELINT = 'stylelint';

  const PHPUNIT = 'phpunit';

  const BEHAT = 'behat';

  const JEST = 'jest';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $selected_tools = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];

    $tmp_dir = $context->directory;
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    $tools = static::getToolDefinitions($tmp_dir, $webroot, 'tools');
    $groups = static::getToolDefinitions($tmp_dir, $webroot, 'groups');

    $missing_tools = array_diff_key($tools, array_flip($selected_tools));

    foreach (array_keys($missing_tools) as $name) {
      $this->processTool($name, $tmp_dir, $webroot);
    }

    foreach (array_keys($groups) as $name) {
      $this->processGroup($name, $tmp_dir, $webroot, $selected_tools);
    }

    // Remove fei: command and its call when all FE tools and custom
    // theme are absent, as there are no front-end dependencies to install.
    $fe_all_group = $groups['frontend_all'] ?? NULL;

    if (!is_array($fe_all_group) || !isset($fe_all_group['tools']) || !is_array($fe_all_group['tools'])) {
      return;
    }

    $fe_tools = array_values(array_filter($fe_all_group['tools'], is_string(...)));

    if (array_intersect($fe_tools, $selected_tools)) {
      return;
    }

    $theme = $context->answers['theme'] ?? NULL;

    if (!in_array($theme, ['olivero', 'claro', 'stark'])) {
      return;
    }

    File::replaceContentInFile($tmp_dir . '/.ahoy.yml', Replacement::create('ahoy_fei', function (string $content): string {
      $content = preg_replace('/^\h*fei:\R(?:\h{4,}.*\R)*/m', '', $content) ?? $content;
      $content = preg_replace('/^\h*ahoy fei\b.*\n?/m', '', $content) ?? $content;
      return Yaml::collapseEmptyLinesInLiteralBlock($content);
    }));
  }

  /**
   * Remove a single tool's files, dependencies, commands and tokens.
   *
   * @param string $name
   *   The tool name.
   * @param string $tmp_dir
   *   The destination project directory.
   * @param string $webroot
   *   The webroot directory name.
   */
  protected function processTool(string $name, string $tmp_dir, string $webroot): void {
    $definitions = static::getToolDefinitions($tmp_dir, $webroot, 'tools');
    $tool = $definitions[$name] ?? NULL;

    if (!is_array($tool)) {
      return;
    }

    // Remove associated files.
    $files_def = $tool['files'] ?? NULL;

    if ($files_def !== NULL) {
      if ($files_def instanceof \Closure) {
        $result = $files_def();
        $files = is_array($result) ? static::flattenFiles($result) : [];
      }
      else {
        $files = is_array($files_def) ? array_values(array_filter($files_def, is_string(...))) : [];
        $files = array_map(fn(string $file): string => $tmp_dir . '/' . $file, $files);
      }

      File::remove($files);
    }

    // Remove dependencies from composer.json.
    $composer_callback = $tool['composer.json'] ?? NULL;

    if (is_callable($composer_callback)) {
      $composer_path = $tmp_dir . '/composer.json';
      $cj = JsonManipulator::fromFile($composer_path);

      if ($cj instanceof JsonManipulator) {
        $composer_callback($cj);
        file_put_contents($composer_path, $cj->getContents());
      }
    }

    // Remove dependencies from package.json.
    $package_callback = $tool['package.json'] ?? NULL;

    if (is_callable($package_callback)) {
      $package_path = $tmp_dir . '/package.json';
      $pj = JsonManipulator::fromFile($package_path);

      if ($pj instanceof JsonManipulator) {
        $package_callback($pj);
        file_put_contents($package_path, $pj->getContents());
      }
    }

    // Remove command definitions from Ahoy.
    if (isset($tool['ahoy']) && is_array($tool['ahoy'])) {
      foreach ($tool['ahoy'] as $string) {
        if (!is_string($string)) {
          continue;
        }

        File::replaceContentInFile($tmp_dir . '/.ahoy.yml', Replacement::create('ahoy_tool', function (string $content) use ($string): string {
          $content = File::replaceContent($content, $string, '');
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }

    File::replaceContentAsync(
      function (string $content, ContentFile $file) use ($tool, $tmp_dir): string {
        if (isset($tool['strings']) && is_array($tool['strings'])) {
          foreach ($tool['strings'] as $string) {
            if (!is_string($string)) {
              continue;
            }

            if (Strings::isRegex($string)) {
              $replaced = preg_replace($string, '', $content, -1, $count);

              if ($count > 0 && $replaced !== NULL) {
                $content = $replaced;
              }
            }
            else {
              $content = str_replace($string, '', $content);
            }
          }
        }

        if (isset($tool['lines']) && is_array($tool['lines'])) {
          $relative_file_path = str_replace($tmp_dir . '/', '', $file->getPathname());

          foreach ($tool['lines'] as $relative_lines_file_name => $lines) {
            if ($relative_file_path !== $relative_lines_file_name) {
                continue;
            }
            if (!is_array($lines)) {
                continue;
            }
            foreach ($lines as $line) {
              if (is_string($line)) {
                $content = File::removeLine($content, $line);
              }
            }
          }
        }

        return $content;
      }
    );

    File::removeTokenAsync('TOOL_' . strtoupper($name));
  }

  /**
   * Remove a tool group's shared files, commands and tokens.
   *
   * @param string $name
   *   The group name.
   * @param string $tmp_dir
   *   The destination project directory.
   * @param string $webroot
   *   The webroot directory name.
   * @param array<int, string> $selected_tools
   *   The list of selected tool names.
   */
  protected function processGroup(string $name, string $tmp_dir, string $webroot, array $selected_tools): void {
    $definitions = static::getToolDefinitions($tmp_dir, $webroot, 'goups');
    $config = $definitions[$name] ?? NULL;

    if (!is_array($config)) {
      return;
    }

    $group_tools = $config['tools'] ?? NULL;

    if (!is_array($group_tools) || array_intersect(array_values(array_filter($group_tools, is_string(...))), $selected_tools)) {
      return;
    }

    if (isset($config['files']) && is_array($config['files'])) {
      $files = array_values(array_filter($config['files'], is_string(...)));
      $files = array_map(fn(string $file): string => $tmp_dir . '/' . $file, $files);
      File::remove($files);
    }

    if (isset($config['ahoy']) && is_array($config['ahoy'])) {
      foreach ($config['ahoy'] as $string) {
        if (!is_string($string)) {
          continue;
        }

        File::replaceContentInFile($tmp_dir . '/.ahoy.yml', Replacement::create('ahoy_tool', function (string $content) use ($string): string {
          $content = File::replaceContent($content, $string, '');
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }

    if (isset($config['token']) && is_string($config['token'])) {
      File::removeTokenAsync($config['token']);
    }
  }

  /**
   * Get the tool and tool-group definitions.
   *
   * @param string $tmp_dir
   *   The destination project directory.
   * @param string $webroot
   *   The webroot directory name.
   * @param string $filter
   *   Which subset to return: 'all', 'tools' or 'groups'.
   *
   * @return array<string, array<string, mixed>>
   *   The tool definitions keyed by tool or group name.
   */
  protected static function getToolDefinitions(string $tmp_dir, string $webroot, string $filter = 'all'): array {
    $filter = in_array($filter, ['all', 'tools', 'groups'], TRUE) ? $filter : 'all';

    $map = [
      'phpcs' => [
        'title' => 'PHP CodeSniffer',
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'dealerdirect/phpcodesniffer-composer-installer');
          $cj->removeConfigSetting('allow-plugins.dealerdirect/phpcodesniffer-composer-installer');
          $cj->removeSubNode('require-dev', 'drupal/coder');
          $cj->removeSubNode('require-dev', 'squizlabs/php_codesniffer');
          $cj->removeSubNode('require-dev', 'phpcompatibility/php-compatibility');
          $cj->removeSubNode('require-dev', 'drevops/phpcs-standard');
        },
        'files' => ['phpcs.xml'],
        'strings' => [
          '/^.*\bphpcs\b.*\n?/m',
          '/^.*\bphpcbf\b.*\n?/m',
        ],
        'ahoy' => ['ahoy cli vendor/bin/phpcs', 'ahoy cli vendor/bin/phpcbf'],
      ],

      'phpstan' => [
        'title' => 'PHPStan',
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'phpstan/phpstan');
          $cj->removeSubNode('require-dev', 'mglaman/phpstan-drupal');
          $cj->removeSubNode('require-dev', 'phpstan/extension-installer');
          $cj->removeConfigSetting('allow-plugins.phpstan/extension-installer');
        },
        'files' => ['phpstan.neon'],
        'strings' => [
          '/^.*\bphpstan\b.*\n?/m',
          '/^.*@phpstan.*\n?/m',
        ],
        'ahoy' => ['ahoy cli vendor/bin/phpstan'],
      ],

      'rector' => [
        'title' => 'Rector',
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'rector/rector');
          $cj->removeSubNode('require-dev', 'palantirnet/drupal-rector');
        },
        'files' => ['rector.php'],
        'strings' => ['/^.*\brector\b.*\n?/m'],
        'ahoy' => [
          'ahoy cli vendor/bin/rector --clear-cache --dry-run',
          'ahoy cli vendor/bin/rector --dry-run',
          'ahoy cli vendor/bin/rector',
        ],
      ],

      'eslint' => [
        'title' => 'ESLint',
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'eslint');
          $pj->removeSubNode('devDependencies', 'eslint-config-airbnb-base');
          $pj->removeSubNode('devDependencies', 'eslint-config-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-import');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-jsdoc');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-no-jquery');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-yml');
          $pj->removeSubNode('devDependencies', 'prettier');
          $pj->removeSubNode('devDependencies', '@homer0/prettier-plugin-jsdoc');
          $pj->removeSubNode('scripts', 'lint-js');
          $pj->removeSubNode('scripts', 'lint-fix-js');
          $pj->addSubNode('scripts', 'lint', 'yarn run lint-css');
          $pj->addSubNode('scripts', 'lint-fix', 'yarn run lint-fix-css');
        },
        'files' => [
          '.eslintrc.json',
          '.eslintignore',
          '.prettierrc.json',
          '.prettierignore',
        ],
      ],

      'stylelint' => [
        'title' => 'Stylelint',
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'stylelint');
          $pj->removeSubNode('devDependencies', 'stylelint-config-standard');
          $pj->removeSubNode('devDependencies', 'stylelint-order');
          $pj->removeSubNode('scripts', 'lint-css');
          $pj->removeSubNode('scripts', 'lint-fix-css');
          $pj->addSubNode('scripts', 'lint', 'yarn run lint-js');
          $pj->addSubNode('scripts', 'lint-fix', 'yarn run lint-fix-js');
        },
        'files' => ['.stylelintrc.js'],
      ],

      'jest' => [
        'title' => 'Jest',
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'jest');
          $pj->removeSubNode('devDependencies', 'jest-environment-jsdom');
          $pj->removeSubNode('scripts', 'test');
        },
        'files' => fn(): array => [
          $tmp_dir . '/jest.config.js',
          glob($tmp_dir . '/' . $webroot . '/modules/custom/*/js/*.test.js'),
        ],
        'lines' => [
          'AGENTS.md' => [
            '# Jest testing',
            'ahoy test-js',
          ],
        ],
      ],

      'phpunit' => [
        'title' => 'PHPUnit',
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'phpunit/phpunit');
          $cj->removeSubNode('require-dev', 'phpspec/prophecy-phpunit');
          $cj->removeProperty('autoload-dev.classmap');
          $cj->removeMainKeyIfEmpty('autoload-dev');
        },
        'files' => fn(): array => [
          $tmp_dir . '/phpunit.xml',
          $tmp_dir . '/tests/phpunit',
          glob($tmp_dir . '/' . $webroot . '/profiles/custom/*/tests', GLOB_ONLYDIR),
          glob($tmp_dir . '/' . $webroot . '/modules/custom/*/tests', GLOB_ONLYDIR),
          glob($tmp_dir . '/' . $webroot . '/themes/custom/*/tests', GLOB_ONLYDIR),
        ],
        'strings' => ['/^.*phpunit.*\n?/m'],
        'lines' => [
          'AGENTS.md' => [
            '# PHPUnit testing',
            'ahoy test            # Run PHPUnit tests',
            'ahoy test-unit',
            'ahoy test-kernel',
            'ahoy test-functional',
            'ahoy test -- --filter=TestClassName',
          ],
          'rector.php' => [
            'YieldDataProviderRector',
          ],
        ],
        'ahoy' => [
          '/^.*phpunit.*\n?/m',
          'ahoy test',
          '/^\h*test:\R\h*usage:\h*usage: Run all PHPUnit tests\.$/um',
          'ahoy test-unit',
          '/^\h*test-unit:\R\h*usage:\h*Run PHPUnit unit tests\.$/um',
          'ahoy test-kernel',
          '/^\h*test-kernel:\R\h*usage:\h*Run PHPUnit kernel tests\.$/um',
          'ahoy test-functional',
          '/^\h*test-functional:\R\h*usage:\h*Run PHPUnit functional tests\.$/um',
        ],
      ],

      'behat' => [
        'title' => 'Behat',
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'behat/behat');
          $cj->removeSubNode('require-dev', 'drupal/drupal-extension');
          $cj->removeSubNode('require-dev', 'dantleech/gherkin-lint');
          $cj->removeSubNode('require-dev', 'drevops/behat-format-progress-fail');
          $cj->removeSubNode('require-dev', 'drevops/behat-screenshot');
          $cj->removeSubNode('require-dev', 'drevops/behat-steps');
        },
        'files' => [
          'behat.yml',
          'tests/behat',
          'gherkinlint.json',
        ],
        'strings' => [
          '/^.*\bbehat\b.*\n?/m',
          '/^.*\bgherkinlint\b.*\n?/m',
        ],
        'lines' => [
          'AGENTS.md' => [
            '# Behat testing',
            'ahoy test-bdd',
          ],
        ],
        'ahoy' => [
          '/^.*behat.*\n?/m',
          'ahoy test-bdd',
          'ahoy lint-tests',
          '/^\h*test-bdd:\R\h*usage:\h*Run BDD tests\.$/um',
          'ahoy cli vendor/bin/gherkinlint lint tests/behat/features',
        ],
      ],

      // Tool groups with shared resources.
      'backend_linting' => [
        'tools' => ['phpcs', 'phpstan', 'rector'],
        'ahoy' => [
          'ahoy lint-be-fix',
          'ahoy lint-be',
          '/^\h*lint-be:\R\h*usage:\h*Lint back-end code\.\R\h*cmd:\h*\|\h*\R\h*$\R\h*$/um',
          '/^\h*lint-be-fix:\R\h*usage:\h*Fix lint issues of back-end code\.\R\h*cmd:\h*\|\h*\R^\h*$/um',
          '/^\h*lint:\R\h*usage:\h*Lint back-end and front-end code\.\R\h*cmd:\h*\|\h*\R\h*$\R\h*$/um',
        ],
      ],
      'test' => [
        'tools' => ['phpunit', 'behat'],
        'ahoy' => [
          '/^\h*test:\R\h*usage:\h*Run all tests\.\R\h*cmd:\h*\|$/um',
          '/^\h*lint-tests:\R\h*usage:\h*Lint tests code\.\R\h*cmd:\h*\|\h*\R^\h*$/um',
        ],
        'token' => 'TOOL_PHPUNIT_BEHAT',
      ],
      'frontend_linting' => [
        'tools' => ['eslint', 'stylelint'],
        'ahoy' => [
          '/^\h*ahoy cli "yarn run lint"\h*\n?/m',
          '/^\h*ahoy cli "yarn run lint-fix"\h*\n?/m',
        ],
        'token' => 'TOOL_ESLINT_STYLELINT',
      ],
      'frontend_testing' => [
        'tools' => ['jest'],
        'token' => 'TOOL_JEST',
      ],
      'frontend_all' => [
        'tools' => ['eslint', 'stylelint', 'jest'],
        'files' => ['package.json', 'yarn.lock'],
      ],
    ];

    if ($filter === 'tools') {
      $map = array_filter($map, fn(array $tool): bool => !isset($tool['tools']));
    }
    elseif ($filter === 'groups') {
      $map = array_filter($map, fn(array $tool): bool => isset($tool['tools']));
    }

    return $map;
  }

  /**
   * Flatten a nested list of paths into a flat list of string paths.
   *
   * @param array<mixed> $files
   *   The nested list of paths, potentially containing arrays and non-strings.
   *
   * @return array<int, string>
   *   The flattened list of string paths.
   */
  protected static function flattenFiles(array $files): array {
    $flat = [];

    array_walk_recursive($files, function (mixed $item) use (&$flat): void {
      if (is_string($item)) {
        $flat[] = $item;
      }
    });

    return $flat;
  }

}
