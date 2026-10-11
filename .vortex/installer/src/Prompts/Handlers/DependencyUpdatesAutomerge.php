<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

/**
 * Select the Renovate update groups that auto-merge.
 *
 * Each group in the template's Renovate configuration carries an explicit
 * 'automerge' flag, set to TRUE for selected groups and FALSE for the rest.
 */
class DependencyUpdatesAutomerge extends AbstractHandler {

  const PHP = 'php';

  const DRUPAL_CORE = 'drupal_core';

  const JS = 'js';

  const CONTAINER_IMAGES = 'container_images';

  const CI_TOOLS = 'ci_tools';

  const GITHUB_ACTIONS = 'github_actions';

  /**
   * Dependency updates providers that run Renovate.
   */
  const PROVIDERS = [DependencyUpdatesProvider::RENOVATEBOT_APP, DependencyUpdatesProvider::RENOVATEBOT_CI];

  /**
   * The 'groupSlug' of each Renovate package rule, keyed by option.
   */
  const GROUPS = [
    self::PHP => 'php-all-except-core-minor-patch',
    self::DRUPAL_CORE => 'php-drupal-core-minor-patch',
    self::JS => 'js-all-minor-patch',
    self::CONTAINER_IMAGES => 'container-images-all-major-minor-patch',
    self::CI_TOOLS => 'ci-tools-all-major-minor-patch',
    self::GITHUB_ACTIONS => 'github-actions-all-major-minor-patch',
  ];

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 75;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Auto-merge dependency updates';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    return <<<DOC
Pull requests in the selected groups are merged as soon as they meet the branch protection rules. Pull requests in other groups need a manual merge.

Requires "Allow auto-merge" in the repository settings and at least one required status check on the base branch.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more groups.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::PHP => 'PHP packages, except Drupal core (minor and patch)',
      self::DRUPAL_CORE => 'Drupal core (minor and patch)',
      self::JS => 'JavaScript packages (minor and patch)',
      self::CONTAINER_IMAGES => 'Container images (major, minor and patch)',
      self::CI_TOOLS => 'CI tools (major, minor and patch)',
      self::GITHUB_ACTIONS => 'GitHub Actions (major, minor and patch)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return array_keys(static::GROUPS);
  }

  /**
   * {@inheritdoc}
   */
  public function dependsOn(): ?array {
    return [DependencyUpdatesProvider::id() => static::PROVIDERS];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return in_array($responses[DependencyUpdatesProvider::id()] ?? NULL, static::PROVIDERS, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    $config = $this->readConfig($this->destinationDir . '/renovate.json');

    if ($config === NULL) {
      return NULL;
    }

    $slugs = [];
    foreach ($config['packageRules'] as $rule) {
      if (is_array($rule) && ($rule['automerge'] ?? FALSE) === TRUE) {
        $slugs[] = $rule['groupSlug'] ?? NULL;
      }
    }

    return array_keys(array_intersect(static::GROUPS, $slugs));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // The prompt is skipped when no Renovate provider is selected.
    if (!is_array($this->response)) {
      return;
    }

    $file = $this->tmpDir . '/renovate.json';
    $config = $this->readConfig($file);

    if ($config === NULL) {
      return;
    }

    foreach ($config['packageRules'] as $index => $rule) {
      $group = is_array($rule) ? array_search($rule['groupSlug'] ?? NULL, static::GROUPS, TRUE) : FALSE;

      if ($group !== FALSE) {
        $config['packageRules'][$index]['automerge'] = in_array($group, $this->response, TRUE);
      }
    }

    File::dump($file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
  }

  /**
   * Read a Renovate configuration file.
   *
   * @param string $file
   *   Path to the configuration file.
   *
   * @return array<string, mixed>|null
   *   The decoded configuration, or NULL if the file is missing, is not valid
   *   JSON or has no package rules.
   */
  protected function readConfig(string $file): ?array {
    if (!File::exists($file)) {
      return NULL;
    }

    $config = json_decode(File::read($file), TRUE);

    if (!is_array($config) || !is_array($config['packageRules'] ?? NULL)) {
      return NULL;
    }

    return $config;
  }

}
