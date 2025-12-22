<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use AlexSkrypnyk\File\Replacer\Replacement;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;

class Modules extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Modules';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more modules.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return static::getAvailableModules();
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    // Default to all modules selected (meaning none will be removed).
    return array_keys(static::getAvailableModules());
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    $composer_file = $this->dstDir . '/composer.json';
    $discovered_modules = $this->getModulesFromComposerFile($composer_file);

    if ($discovered_modules === NULL) {
      return NULL;
    }

    // Filter discovered modules to only include those in our available list.
    $available_modules = array_keys(static::getAvailableModules());
    $modules = array_intersect($discovered_modules, $available_modules);

    sort($modules);

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $selected_modules = $this->getResponseAsArray();
    $all_modules = static::getAvailableModules();

    $t = $this->tmpDir;
    $w = $this->webroot;

    // Process each module that was NOT selected (remove them).
    foreach (array_keys($all_modules) as $module_name) {
      if (!in_array($module_name, $selected_modules)) {
        // Remove from composer.json.
        $pattern = '/\s*"drupal\/' . preg_quote($module_name, '/') . '":\s*"[^\"]+",?\n/';
        File::replaceContentInFile($t . '/composer.json', $pattern, "\n");

        // Remove module from settings file.
        File::remove($t . '/' . $w . '/sites/default/includes/modules/settings.' . $module_name . '.php');

        // Remove module from the provision example file.
        File::replaceContentInFile($t . '/scripts/custom/provision-10-example.sh', Replacement::create('module', function (string $content) use ($module_name): string {
          $pattern = '/^(\s*)(drush\s+pm:install.*\b' . preg_quote($module_name, '/') . '\b.*)$/m';
          $content = preg_replace_callback($pattern, function (array $matches) use ($module_name): string {
            $indent = $matches[1];
            $line = $matches[2];
            $line = preg_replace('/\s+\b' . preg_quote($module_name, '/') . '\b/', '', $line);
            $line = preg_replace('/\s{2,}/', ' ', $line) ?? $line;
            return $indent . $line;
          }, $content);
          return $content ?? '';
        }));

        // Remove module from the Behat tests.
        File::remove($t . '/tests/behat/features/' . $module_name . '.feature');

        // Remove module from the config tests.
        $pattern = '/\s*\$config\[\'' . preg_quote($module_name, '/') . '\..*;(\r?\n)?/';
        File::removeLineInFile($t . '/tests/phpunit/Drupal/EnvironmentSettingsTest.php', $pattern);

        // Remove module tokens.
        File::removeTokenAsync('MODULE_' . strtoupper($module_name));
      }
    }

    if (count($selected_modules) === 0) {
      File::removeTokenAsync('MODULE');
    }
  }

  /**
   * Get the full list of available Drupal contributed modules.
   *
   * This list excludes Drupal core modules and service modules.
   *
   * @return array<string, string>
   *   Array of module machine names as keys and labels as values.
   */
  public static function getAvailableModules(): array {
    return [
      'admin_toolbar' => 'Admin toolbar',
      'coffee' => 'Coffee',
      'config_split' => 'Config split',
      'config_update' => 'Config update',
      'environment_indicator' => 'Environment indicator',
      'pathauto' => 'Pathauto',
      'redirect' => 'Redirect',
      'robotstxt' => 'Robots.txt',
      'seckit' => 'Seckit',
      'shield' => 'Shield',
      'stage_file_proxy' => 'Stage file proxy',
    ];
  }

  /**
   * Extract Drupal contributed module names from a composer.json file.
   *
   * @param string $composer_file
   *   Path to the composer.json file.
   *
   * @return array|null
   *   Array of module machine names (without drupal/ prefix), or NULL on error.
   */
  protected function getModulesFromComposerFile(string $composer_file): ?array {
    if (!file_exists($composer_file)) {
      return NULL;
    }

    $cj = JsonManipulator::fromFile($composer_file);

    if (!$cj instanceof JsonManipulator) {
      return NULL;
    }

    $require = $cj->getProperty('require');

    if (!is_array($require)) {
      return NULL;
    }

    $modules = [];
    foreach (array_keys($require) as $package) {
      // Only include drupal/* packages, excluding core packages.
      if (str_starts_with((string) $package, 'drupal/') && !str_starts_with((string) $package, 'drupal/core-')) {
        // Extract module name (remove drupal/ prefix).
        $module_name = substr((string) $package, 7);
        $modules[] = $module_name;
      }
    }

    return $modules;
  }

}
