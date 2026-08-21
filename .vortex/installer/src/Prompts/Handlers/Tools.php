<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use AlexSkrypnyk\File\ContentFile\ContentFile;
use AlexSkrypnyk\File\Replacer\Replacement;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\NpmLock;
use DrevOps\VortexInstaller\Utils\Strings;
use DrevOps\VortexInstaller\Utils\Yaml;
use function iter\flatten;

class Tools extends AbstractHandler {

  const PHPCS = 'phpcs';

  const PHPSTAN = 'phpstan';

  const RECTOR = 'rector';

  const ESLINT = 'eslint';

  const STYLELINT = 'stylelint';

  const PHPUNIT = 'phpunit';

  const BEHAT = 'behat';

  const JEST = 'jest';

  const TWIG_CS_FIXER = 'twig_cs_fixer';

  const DCLINT = 'dclint';

  const HADOLINT = 'hadolint';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Development tools';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more tools.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    $options = [];
    foreach (self::getToolDefinitions('tools') as $tool => $config) {
      $options[$tool] = $config['title'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return [
      self::BEHAT,
      self::DCLINT,
      self::ESLINT,
      self::HADOLINT,
      self::JEST,
      self::PHPCS,
      self::PHPSTAN,
      self::PHPUNIT,
      self::RECTOR,
      self::STYLELINT,
      self::TWIG_CS_FIXER,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    $tools = [];

    foreach (self::getToolDefinitions('tools') as $tool => $config) {
      if (isset($config['present']) && $config['present'] instanceof \Closure && $config['present']->bindTo($this)()) {
        $tools[] = $tool;
      }
    }

    sort($tools);

    return $tools;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $selected_tools = $this->getResponseAsArray();

    $tools = self::getToolDefinitions('tools');
    $groups = self::getToolDefinitions('groups');

    $missing_tools = array_diff_key($tools, array_flip($selected_tools));

    foreach (array_keys($missing_tools) as $name) {
      $this->processTool($name);
    }

    foreach (array_keys($groups) as $name) {
      $this->processGroup($name);
    }

    // Remove fei: command and its call when all FE tools and custom
    // theme are absent, as there are no front-end dependencies to install.
    $fe_all_group = $groups['frontend_all'] ?? NULL;
    if ($fe_all_group && isset($fe_all_group['tools']) && !array_intersect($fe_all_group['tools'], $selected_tools)) {
      $theme = $this->responses[Theme::id()] ?? NULL;
      if (in_array($theme, [Theme::OLIVERO, Theme::CLARO, Theme::STARK])) {
        File::replaceContentInFile($this->tmpDir . '/.ahoy.yml', Replacement::create('ahoy_fei', function (string $content): string {
          $content = preg_replace('/^\h*fei:\R(?:\h{4,}.*\R)*/m', '', $content) ?? $content;
          $content = preg_replace('/^\h*ahoy fei\b.*\n?/m', '', $content) ?? $content;
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }
  }

  protected function processTool(string $name): void {
    $tool = self::getToolDefinitions('tools')[$name];

    if (isset($tool['files'])) {
      if ($tool['files'] instanceof \Closure) {
        $files = $tool['files']->bindTo($this)();
        $files = flatten($files);
      }
      else {
        $files = $tool['files'];
        $files = array_map(fn($file): string => $this->tmpDir . '/' . $file, $files);
      }
      File::remove($files);
    }

    if (isset($tool['composer.json']) && is_callable($tool['composer.json'])) {
      JsonManipulator::updateFile($this->tmpDir . '/composer.json', $tool['composer.json']);
    }

    $this->processNpmManifests($tool);

    if (isset($tool['ahoy'])) {
      foreach ($tool['ahoy'] as $string) {
        File::replaceContentInFile($this->tmpDir . '/.ahoy.yml', Replacement::create('ahoy_tool', function (string $content) use ($string): string {
          $content = File::replaceContent($content, $string, '');
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }

    $this->processContent($tool);

    File::removeTokenAsync('TOOL_' . strtoupper($name));
  }

  protected function processGroup(string $name): void {
    $config = self::getToolDefinitions('groups')[$name];
    $selected_tools = $this->getResponseAsArray();

    if (!isset($config['tools']) || array_intersect($config['tools'], $selected_tools)) {
      return;
    }

    $this->processNpmManifests($config);

    if (isset($config['files'])) {
      $files = array_map(fn($file): string => $this->tmpDir . '/' . $file, $config['files']);
      File::remove($files);
    }

    if (isset($config['ahoy'])) {
      foreach ($config['ahoy'] as $string) {
        File::replaceContentInFile($this->tmpDir . '/.ahoy.yml', Replacement::create('ahoy_tool', function (string $content) use ($string): string {
          $content = File::replaceContent($content, $string, '');
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }

    $this->processContent($config);

    if (isset($config['token'])) {
      File::removeTokenAsync($config['token']);
    }
  }

  /**
   * Queue the content removals declared by a tool or a group.
   *
   * @param array $config
   *   Tool or group definition.
   */
  protected function processContent(array $config): void {
    if (!isset($config['strings']) && !isset($config['lines'])) {
      return;
    }

    File::replaceContentAsync(
      function (string $content, ContentFile $file) use ($config): string {
        if (isset($config['strings'])) {
          foreach ($config['strings'] as $string) {
            if (Strings::isRegex($string)) {
              $replaced = preg_replace($string, '', $content, -1, $count);

              if ($count > 0) {
                $content = $replaced;
              }
            }
            else {
              $content = str_replace($string, '', $content);
            }
          }
        }

        if (isset($config['lines'])) {
          $relative_file_path = str_replace($this->tmpDir . '/', '', $file->getPathname());
          foreach ($config['lines'] as $relative_lines_file_name => $lines) {
            if ($relative_file_path === $relative_lines_file_name) {
              foreach ($lines as $line) {
                $content = File::removeLine($content, $line);
              }
            }
          }
        }

        return $content;
      }
    );
  }

  /**
   * Apply the npm manifest edits declared by a tool or a group.
   *
   * @param array $config
   *   Tool or group definition.
   */
  protected function processNpmManifests(array $config): void {
    if (isset($config['package.json']) && is_callable($config['package.json'])) {
      $this->updateNpmManifest($this->tmpDir . '/package.json', $config['package.json']);
    }

    if (isset($config['theme.package.json']) && is_callable($config['theme.package.json'])) {
      foreach ($this->themeManifests() as $manifest) {
        $this->updateNpmManifest($manifest, $config['theme.package.json']);
      }
    }
  }

  protected function updateNpmManifest(string $manifest, callable $callback): void {
    JsonManipulator::updateFile($manifest, $callback);

    // A lock file that still lists the removed dependencies makes 'npm ci'
    // abort on the first build.
    NpmLock::sync($manifest);
  }

  /**
   * Find the manifests of the custom themes.
   *
   * @return array<int, string>
   *   Paths to the "package.json" files.
   */
  protected function themeManifests(): array {
    return glob($this->tmpDir . '/' . $this->webroot . '/themes/custom/*/package.json') ?: [];
  }

  public static function getToolDefinitions(string $filter = 'all'): array {
    $filter = in_array($filter, ['all', 'tools', 'groups'], TRUE) ? $filter : 'all';

    $map = [
      self::PHPCS => [
        'title' => 'PHP CodeSniffer',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'dealerdirect/phpcodesniffer-composer-installer') ||
          File::contains($this->destinationDir . '/composer.json', 'drupal/coder') ||
          File::contains($this->destinationDir . '/composer.json', 'squizlabs/php_codesniffer') ||
          File::contains($this->destinationDir . '/composer.json', 'phpcompatibility/php-compatibility') ||
          File::contains($this->destinationDir . '/composer.json', 'drevops/phpcs-standard') ||
          File::exists($this->destinationDir . '/phpcs.xml'),
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

      self::PHPSTAN => [
        'title' => 'PHPStan',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'phpstan/phpstan') ||
          File::contains($this->destinationDir . '/composer.json', 'mglaman/phpstan-drupal') ||
          File::contains($this->destinationDir . '/composer.json', 'phpstan/extension-installer') ||
          File::exists($this->destinationDir . '/phpstan.neon'),
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

      self::RECTOR => [
        'title' => 'Rector',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'rector/rector') ||
          File::contains($this->destinationDir . '/composer.json', 'palantirnet/drupal-rector') ||
          File::exists($this->destinationDir . '/rector.php'),
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

      self::ESLINT => [
        'title' => 'ESLint',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/package.json', '"eslint":') ||
          File::exists($this->destinationDir . '/eslint.config.mjs'),
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', '@eslint/compat');
          $pj->removeSubNode('devDependencies', '@eslint/js');
          $pj->removeSubNode('devDependencies', 'eslint');
          $pj->removeSubNode('devDependencies', 'eslint-config-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-import');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-jsdoc');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-no-jquery');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-yml');
          $pj->removeSubNode('devDependencies', 'globals');
          $pj->removeSubNode('devDependencies', 'prettier');
          $pj->removeSubNode('devDependencies', '@homer0/prettier-plugin-jsdoc');
          $pj->removeSubNode('scripts', 'lint-js');
          $pj->removeSubNode('scripts', 'lint-fix-js');
          $pj->addSubNode('scripts', 'lint', 'npm run lint-css');
          $pj->addSubNode('scripts', 'lint-fix', 'npm run lint-fix-css');
        },
        'theme.package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', '@eslint/compat');
          $pj->removeSubNode('devDependencies', '@eslint/js');
          $pj->removeSubNode('devDependencies', 'eslint');
          $pj->removeSubNode('devDependencies', 'eslint-config-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-import');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-jsdoc');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-no-jquery');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-prettier');
          $pj->removeSubNode('devDependencies', 'eslint-plugin-yml');
          $pj->removeSubNode('devDependencies', 'globals');
          $pj->removeSubNode('devDependencies', 'prettier');
          $pj->removeSubNode('devDependencies', '@homer0/prettier-plugin-jsdoc');
          $pj->removeSubNode('scripts', 'lint-js');
          $pj->removeSubNode('scripts', 'lint-js-fix');
          $pj->addSubNode('scripts', 'lint', 'npm run lint-css');
          $pj->addSubNode('scripts', 'lint-fix', 'npm run lint-css-fix');
        },
        // A project created before the move to flat config still carries the
        // legacy files, which linger unread once the tool is deselected.
        'files' => ['eslint.config.mjs', '.eslintrc.json', '.eslintignore', '.prettierrc.json', '.prettierignore'],
      ],

      self::STYLELINT => [
        'title' => 'Stylelint',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/package.json', '"stylelint":') ||
          File::exists($this->destinationDir . '/.stylelintrc.js'),
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'stylelint');
          $pj->removeSubNode('devDependencies', 'stylelint-config-standard');
          $pj->removeSubNode('devDependencies', 'stylelint-order');
          $pj->removeSubNode('scripts', 'lint-css');
          $pj->removeSubNode('scripts', 'lint-fix-css');
          $pj->addSubNode('scripts', 'lint', 'npm run lint-js');
          $pj->addSubNode('scripts', 'lint-fix', 'npm run lint-fix-js');
        },
        'theme.package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'stylelint');
          $pj->removeSubNode('devDependencies', 'stylelint-config-standard');
          $pj->removeSubNode('devDependencies', 'stylelint-config-standard-scss');
          $pj->removeSubNode('devDependencies', 'stylelint-order');
          $pj->removeSubNode('devDependencies', 'stylelint-scss');
          $pj->removeSubNode('scripts', 'lint-css');
          $pj->removeSubNode('scripts', 'lint-css-fix');
          $pj->addSubNode('scripts', 'lint', 'npm run lint-js');
          $pj->addSubNode('scripts', 'lint-fix', 'npm run lint-js-fix');
        },
        'files' => ['.stylelintrc.js'],
      ],

      self::JEST => [
        'title' => 'Jest',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/package.json', '"jest":') ||
          File::exists($this->destinationDir . '/jest.config.js'),
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('devDependencies', 'jest');
          $pj->removeSubNode('devDependencies', 'jest-environment-jsdom');
          $pj->removeSubNode('scripts', 'test');
        },
        'files' => fn(): array => [
          $this->tmpDir . '/jest.config.js',
          glob($this->tmpDir . '/' . $this->webroot . '/modules/custom/*/js/*.test.js'),
        ],
        'lines' => [
          'AGENTS.md' => [
            '# Jest testing',
            'ahoy test-js',
          ],
        ],
      ],

      self::PHPUNIT => [
        'title' => 'PHPUnit',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'phpunit/phpunit') ||
          File::contains($this->destinationDir . '/composer.json', 'phpspec/prophecy-phpunit') ||
          File::exists($this->destinationDir . '/phpunit.xml'),
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'phpunit/phpunit');
          $cj->removeSubNode('require-dev', 'phpspec/prophecy-phpunit');
          $cj->removeProperty('autoload-dev.classmap');
          $cj->removeMainKeyIfEmpty('autoload-dev');
        },
        'files' => fn(): array => [
          $this->tmpDir . '/phpunit.xml',
          $this->tmpDir . '/tests/phpunit',
          glob($this->tmpDir . '/' . $this->webroot . '/profiles/custom/*/tests', GLOB_ONLYDIR),
          glob($this->tmpDir . '/' . $this->webroot . '/modules/custom/*/tests', GLOB_ONLYDIR),
          glob($this->tmpDir . '/' . $this->webroot . '/themes/custom/*/tests', GLOB_ONLYDIR),
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

      self::BEHAT => [
        'title' => 'Behat',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'behat/behat') ||
          File::contains($this->destinationDir . '/composer.json', 'drupal/drupal-extension') ||
          File::contains($this->destinationDir . '/composer.json', 'drevops/behat-format-progress-fail') ||
          File::contains($this->destinationDir . '/composer.json', 'drevops/behat-screenshot') ||
          File::contains($this->destinationDir . '/composer.json', 'drevops/behat-steps') ||
          File::exists($this->destinationDir . '/behat.yml'),
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

      self::TWIG_CS_FIXER => [
        'title' => 'Twig CS Fixer',
        'present' => fn(): mixed => File::contains($this->destinationDir . '/composer.json', 'vincentlanglet/twig-cs-fixer') ||
          File::exists($this->destinationDir . '/.twig-cs-fixer.php'),
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'vincentlanglet/twig-cs-fixer');
        },
        'files' => ['.twig-cs-fixer.php'],
        'strings' => ['/^.*\btwig-cs-fixer\b.*\n?/m'],
        'ahoy' => [
          'ahoy cli vendor/bin/twig-cs-fixer lint --fix',
          'ahoy cli vendor/bin/twig-cs-fixer lint',
        ],
      ],

      self::DCLINT => [
        'title' => 'DCLint',
        'present' => fn(): mixed => File::exists($this->destinationDir . '/.dclintrc') ||
          File::contains($this->destinationDir . '/.github/workflows/build-test-deploy.yml', 'dclint') ||
          File::contains($this->destinationDir . '/.circleci/config.yml', 'dclint'),
        'files' => ['.dclintrc'],
        'strings' => ['/^.*dclint.*\n?/m'],
      ],

      // The 'hadolint ignore=' directives in Dockerfiles are deliberately not
      // a detection signal: they survive deselection, so treating them as one
      // would make the choice impossible to reverse.
      self::HADOLINT => [
        'title' => 'Hadolint',
        'present' => fn(): mixed => File::exists($this->destinationDir . '/.hadolint.yaml') ||
          File::exists($this->destinationDir . '/.hadolint.yml') ||
          File::contains($this->destinationDir . '/.github/workflows/build-test-deploy.yml', 'hadolint') ||
          File::contains($this->destinationDir . '/.circleci/config.yml', 'hadolint'),
      ],

      // Tool groups with shared resources.
      'backend_linting' => [
        'tools' => [self::PHPCS, self::PHPSTAN, self::RECTOR],
        'ahoy' => [
          'ahoy lint-be-fix',
          'ahoy lint-be',
          '/^\h*lint-be:\R\h*usage:\h*Lint back-end code\.\R\h*cmd:\h*\|\h*\R\h*$\R\h*$/um',
          '/^\h*lint-be-fix:\R\h*usage:\h*Fix lint issues of back-end code\.\R\h*cmd:\h*\|\h*\R^\h*$/um',
          '/^\h*lint:\R\h*usage:\h*Lint back-end and front-end code\.\R\h*cmd:\h*\|\h*\R\h*$\R\h*$/um',
        ],
      ],
      'test' => [
        'tools' => [self::PHPUNIT, self::BEHAT],
        'ahoy' => [
          '/^\h*test:\R\h*usage:\h*Run all tests\.\R\h*cmd:\h*\|$/um',
          '/^\h*lint-tests:\R\h*usage:\h*Lint tests code\.\R\h*cmd:\h*\|\h*\R^\h*$/um',
        ],
        'token' => 'TOOL_PHPUNIT_BEHAT',
      ],
      'frontend_linting' => [
        'tools' => [self::ESLINT, self::STYLELINT],
        // Each linter rewrites 'lint' to call the other one, so with both
        // deselected the pair points at scripts that no longer exist.
        'package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('scripts', 'lint');
          $pj->removeSubNode('scripts', 'lint-fix');
        },
        'theme.package.json' => function (JsonManipulator $pj): void {
          $pj->removeSubNode('scripts', 'lint');
          $pj->removeSubNode('scripts', 'lint-fix');
        },
        'ahoy' => [
          '/^\h*ahoy cli "npm run lint"\h*\n?/m',
          '/^\h*ahoy cli "npm run lint-fix"\h*\n?/m',
          'ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint"',
          'ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-fix"',
        ],
        'strings' => [
          '/^\|\h*`npm run lint`.*\n?/m',
          '/^\|\h*`npm run lint-fix`.*\n?/m',
        ],
        'token' => 'TOOL_ESLINT_STYLELINT',
      ],
      'frontend_testing' => [
        'tools' => [self::JEST],
        'token' => 'TOOL_JEST',
      ],
      'frontend_all' => [
        'tools' => [self::ESLINT, self::STYLELINT, self::JEST],
        'files' => ['package.json', 'package-lock.json'],
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

}
