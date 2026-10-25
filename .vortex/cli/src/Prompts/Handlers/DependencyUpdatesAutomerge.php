<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Prompts\Handlers;

use DrevOps\VortexCli\Utils\File;

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
   * The 'groupSlug' of Renovate package rules linked to an option's group.
   *
   * A linked rule gives some of the group's dependencies a separate schedule,
   * so the group's option also sets the linked rule's 'automerge' flag.
   */
  const LINKED_GROUPS = [
    self::CI_TOOLS => 'ci-tools-renovate-major-minor-patch',
  ];

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

    $config = $this->readConfig($this->dstDir . '/renovate.json');

    if ($config === NULL) {
      return NULL;
    }

    $slugs = [];
    foreach ($this->packageRules($config) as $rule) {
      if (($rule['automerge'] ?? FALSE) === TRUE) {
        $slugs[] = $rule['groupSlug'] ?? NULL;
      }
    }

    // The option sets a linked rule's flag, so only group flags are read.
    return array_keys(array_intersect(static::GROUPS, $slugs));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $file = $this->tmpDir . '/renovate.json';
    $config = $this->readConfig($file);

    if (!is_array($this->response) || $config === NULL) {
      return;
    }

    foreach ($this->packageRules($config) as $index => $rule) {
      $slug = $rule['groupSlug'] ?? NULL;
      $group = array_search($slug, static::GROUPS, TRUE);

      if ($group === FALSE) {
        $group = array_search($slug, static::LINKED_GROUPS, TRUE);
      }

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
   *   The decoded configuration, or NULL if the file is missing or does not
   *   hold a JSON object.
   */
  protected function readConfig(string $file): ?array {
    if (!File::exists($file)) {
      return NULL;
    }

    $config = json_decode(File::read($file), TRUE);

    return is_array($config) ? $config : NULL;
  }

  /**
   * Get the package rules of a Renovate configuration.
   *
   * @param array<string, mixed> $config
   *   The decoded configuration.
   *
   * @return array<int|string, array<string, mixed>>
   *   The package rules that are objects, keyed by their position, or an empty
   *   array if the configuration has none.
   */
  protected function packageRules(array $config): array {
    $rules = is_array($config['packageRules'] ?? NULL) ? $config['packageRules'] : [];

    return array_filter($rules, is_array(...));
  }

}
