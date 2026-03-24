<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Tui;

class CustomModules extends AbstractHandler {

  const BASE = 'base';

  const DEMO = 'demo';

  const SEARCH = 'search';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Custom modules';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    $base = Tui::bold('Base');
    $demo = Tui::bold('Demo');
    $search = Tui::bold('Search');

    return <<<DOC
Select which custom modules to include in your project:

    ○ {$base}
      Starter module with common site utilities (mail handling,
      deploy hooks) and test scaffolding for Unit, Kernel,
      Functional, and FunctionalJavascript tests.

    ○ {$search}
      Custom Solr search integration module. Requires the Solr
      service to be selected.

    ○ {$demo}
      Demonstrates how Vortex tooling works: includes a counter
      block with CSS/JS, PHPUnit example tests across all test
      types, and a Behat feature. Safe to remove on real projects.
DOC;
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
    return [
      self::BASE => 'Base - starter module with utilities and test scaffolding',
      self::SEARCH => 'Search - custom Solr search integration',
      self::DEMO => 'Demo - counter block and example tests to demonstrate tooling',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return [self::BASE, self::SEARCH, self::DEMO];
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    // Discover the module prefix from the existing codebase.
    $prefix = $this->discoverModulePrefix();

    if ($prefix === NULL) {
      return NULL;
    }

    $modules = [];

    $module_dir = $this->dstDir . '/' . $this->webroot . '/modules/custom';

    if (is_dir($module_dir . '/' . $prefix . '_base')) {
      $modules[] = self::BASE;
    }

    if (is_dir($module_dir . '/' . $prefix . '_demo')) {
      $modules[] = self::DEMO;
    }

    if (is_dir($module_dir . '/' . $prefix . '_search')) {
      $modules[] = self::SEARCH;
    }

    sort($modules);

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $selected = $this->getResponseAsArray();
    $t = $this->tmpDir;
    $w = $this->webroot;

    // Safety net: if search was selected but Solr service was not, force-remove
    // search module since it cannot function without Solr.
    if (in_array(self::SEARCH, $selected) && isset($this->responses[Services::id()])) {
      $services = $this->responses[Services::id()];
      if (is_array($services) && !in_array(Services::SOLR, $services)) {
        $selected = array_values(array_diff($selected, [self::SEARCH]));
      }
    }

    if (!in_array(self::BASE, $selected)) {
      File::removeTokenAsync('CUSTOM_MODULE_BASE');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_base', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_base', $w),
        $t . sprintf('/%s/profiles/*/modules/*_base', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_base', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_base', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_base', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }
    }

    if (!in_array(self::DEMO, $selected)) {
      File::removeTokenAsync('CUSTOM_MODULE_DEMO');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_demo', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_demo', $w),
        $t . sprintf('/%s/profiles/*/modules/*_demo', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_demo', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_demo', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_demo', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }

      static::removeDemoBehatFeatures($t);
    }

    if (!in_array(self::SEARCH, $selected)) {
      File::removeTokenAsync('CUSTOM_MODULE_SEARCH');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_search', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_search', $w),
        $t . sprintf('/%s/profiles/*/modules/*_search', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_search', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_search', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_search', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }
    }
  }

  /**
   * Discover the module prefix from the existing codebase.
   *
   * @return string|null
   *   The discovered module prefix, or NULL if not found.
   */
  protected function discoverModulePrefix(): ?string {
    $locations = [
      $this->dstDir . sprintf('/%s/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_core', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    return empty($path) ? NULL : str_replace(['_base', '_core'], '', basename($path));
  }

  /**
   * Remove Behat feature files tagged with @demo.
   *
   * Scans the Behat features directory for .feature files whose first line
   * contains the @demo tag and removes them.
   *
   * @param string $dir
   *   The base directory to search in.
   */
  protected static function removeDemoBehatFeatures(string $dir): void {
    $features_dir = $dir . '/tests/behat/features';

    if (!is_dir($features_dir)) {
      return;
    }

    $files = glob($features_dir . '/*.feature');

    if ($files === FALSE) {
      return;
    }

    foreach ($files as $file) {
      $handle = fopen($file, 'r');
      if ($handle === FALSE) {
        continue;
      }
      $first_line = fgets($handle);
      fclose($handle);

      if ($first_line !== FALSE && str_contains($first_line, '@demo')) {
        File::remove($file);
      }
    }
  }

}
