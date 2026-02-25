<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use AlexSkrypnyk\File\ContentFile\ContentFile;
use AlexSkrypnyk\File\Replacer\Replacement;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Strings;
use DrevOps\VortexInstaller\Utils\Yaml;
use function iter\flatten;

class Tools extends AbstractHandler {

  const PHPCS = 'phpcs';

  const PHPMD = 'phpmd';

  const PHPSTAN = 'phpstan';

  const RECTOR = 'rector';

  const ESLINT = 'eslint';

  const STYLELINT = 'stylelint';

  const PHPUNIT = 'phpunit';

  const BEHAT = 'behat';

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
    foreach (static::getToolDefinitions('tools') as $tool => $config) {
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
      self::ESLINT,
      self::PHPCS,
      self::PHPMD,
      self::PHPSTAN,
      self::PHPUNIT,
      self::RECTOR,
      self::STYLELINT,
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

    foreach (static::getToolDefinitions('tools') as $tool => $config) {
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

    $tools = static::getToolDefinitions('tools');
    $groups = static::getToolDefinitions('groups');

    $missing_tools = array_diff_key($tools, array_flip($selected_tools));

    foreach (array_keys($missing_tools) as $name) {
      $this->processTool($name);
    }

    foreach (array_keys($groups) as $name) {
      $this->processGroup($name);
    }

    // Remove fei: command and its call when both FE lint tools and custom
    // theme are absent, as there are no front-end dependencies to install.
    $fe_group = $groups['frontend_linting'] ?? NULL;
    if ($fe_group && isset($fe_group['tools']) && !array_intersect($fe_group['tools'], $selected_tools)) {
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
    $tool = static::getToolDefinitions('tools')[$name];

    // Remove associated files.
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

    // Remove dependencies from composer.json.
    if (isset($tool['composer.json']) && is_callable($tool['composer.json'])) {
      $composer_path = $this->tmpDir . '/composer.json';
      $cj = JsonManipulator::fromFile($composer_path);
      if ($cj instanceof JsonManipulator) {
        $tool['composer.json']($cj);
        file_put_contents($composer_path, $cj->getContents());
      }
    }

    // Remove dependencies from package.json.
    if (isset($tool['package.json']) && is_callable($tool['package.json'])) {
      $package_path = $this->tmpDir . '/package.json';
      $pj = JsonManipulator::fromFile($package_path);
      if ($pj instanceof JsonManipulator) {
        $tool['package.json']($pj);
        file_put_contents($package_path, $pj->getContents());
      }
    }

    // Remove command definitions from Ahoy.
    if (isset($tool['ahoy'])) {
      foreach ($tool['ahoy'] as $string) {
        File::replaceContentInFile($this->tmpDir . '/.ahoy.yml', Replacement::create('ahoy_tool', function (string $content) use ($string): string {
          $content = File::replaceContent($content, $string, '');
          return Yaml::collapseEmptyLinesInLiteralBlock($content);
        }));
      }
    }

    File::replaceContentAsync(
      function (string $content, ContentFile $file) use ($tool): string {
        if (isset($tool['strings'])) {
          foreach ($tool['strings'] as $string) {
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

        if (isset($tool['lines'])) {
          $relative_file_path = str_replace($this->tmpDir . '/', '', $file->getPathname());
          foreach ($tool['lines'] as $relative_lines_file_name => $lines) {
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

    File::removeTokenAsync('TOOL_' . strtoupper($name));
  }

  protected function processGroup(string $name): void {
    $config = static::getToolDefinitions('goups')[$name];
    $selected_tools = $this->getResponseAsArray();

    if (!isset($config['tools']) || array_intersect($config['tools'], $selected_tools)) {
      return;
    }

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

    if (isset($config['token'])) {
      File::removeTokenAsync($config['token']);
    }
  }

  public static function getToolDefinitions(string $filter = 'all'): array {
    $filter = in_array($filter, ['all', 'tools', 'groups']) ? $filter : 'all';

    $map = [
      self::PHPCS => [
        'title' => 'PHP CodeSniffer',
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'dealerdirect/phpcodesniffer-composer-installer') ||
        File::contains($this->dstDir . '/composer.json', 'drupal/coder') ||
        File::contains($this->dstDir . '/composer.json', 'squizlabs/php_codesniffer') ||
        File::contains($this->dstDir . '/composer.json', 'phpcompatibility/php-compatibility') ||
        File::contains($this->dstDir . '/composer.json', 'drevops/phpcs-standard') ||
        File::exists($this->dstDir . '/phpcs.xml'),
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
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'phpstan/phpstan') ||
        File::contains($this->dstDir . '/composer.json', 'mglaman/phpstan-drupal') ||
        File::contains($this->dstDir . '/composer.json', 'phpstan/extension-installer') ||
        File::exists($this->dstDir . '/phpstan.neon'),
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
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'rector/rector') ||
        File::contains($this->dstDir . '/composer.json', 'palantirnet/drupal-rector') ||
        File::exists($this->dstDir . '/rector.php'),
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

      self::PHPMD => [
        'title' => 'PHP Mess Detector',
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'phpmd/phpmd') ||
        File::exists($this->dstDir . '/phpmd.xml'),
        'composer.json' => function (JsonManipulator $cj): void {
          $cj->removeSubNode('require-dev', 'phpmd/phpmd');
        },
        'files' => ['phpmd.xml'],
        'strings' => [
          '/^.*phpmd.*\n?/m',
          '/^.*@SuppressWarnings.*\n?/m',
        ],
        'ahoy' => ['ahoy cli vendor/bin/phpmd . text phpmd.xml'],
      ],

      self::ESLINT => [
        'title' => 'ESLint',
        'present' => fn(): mixed => File::contains($this->dstDir . '/package.json', '"eslint":') ||
        File::exists($this->dstDir . '/.eslintrc.json'),
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
        'files' => ['.eslintrc.json', '.eslintignore', '.prettierrc.json', '.prettierignore'],
      ],

      self::STYLELINT => [
        'title' => 'Stylelint',
        'present' => fn(): mixed => File::contains($this->dstDir . '/package.json', '"stylelint":') ||
        File::exists($this->dstDir . '/.stylelintrc.js'),
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

      self::PHPUNIT => [
        'title' => 'PHPUnit',
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'phpunit/phpunit') ||
        File::contains($this->dstDir . '/composer.json', 'phpspec/prophecy-phpunit') ||
        File::exists($this->dstDir . '/phpunit.xml'),
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
        'present' => fn(): mixed => File::contains($this->dstDir . '/composer.json', 'behat/behat') ||
        File::contains($this->dstDir . '/composer.json', 'drupal/drupal-extension') ||
        File::contains($this->dstDir . '/composer.json', 'drevops/behat-format-progress-fail') ||
        File::contains($this->dstDir . '/composer.json', 'drevops/behat-screenshot') ||
        File::contains($this->dstDir . '/composer.json', 'drevops/behat-steps') ||
        File::exists($this->dstDir . '/behat.yml'),
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
        'tools' => [self::PHPCS, self::PHPSTAN, self::RECTOR, self::PHPMD],
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
      ],
      'frontend_linting' => [
        'tools' => [self::ESLINT, self::STYLELINT],
        'files' => ['package.json', 'yarn.lock'],
        'ahoy' => [
          '/^\h*ahoy cli "yarn install --frozen-lockfile"\h*\n?/m',
          '/^\h*ahoy cli "yarn run lint"\h*\n?/m',
          '/^\h*ahoy cli "yarn run lint-fix"\h*\n?/m',
        ],
        'token' => 'TOOL_ESLINT_STYLELINT',
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
