<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class CodeProvider extends AbstractHandler {

  const GITHUB = 'github';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Repository provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): string {
    return 'Vortex offers full automation with GitHub, while support for other providers is limited.';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select your code repository provider.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::GITHUB => 'GitHub',
      self::OTHER => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::GITHUB;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (file_exists($this->dstDir . '/.github')) {
      return self::GITHUB;
    }

    return $this->isInstalled() && file_exists($this->dstDir . '/.git') ? self::OTHER : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v === self::GITHUB) {
      if (file_exists($t . '/.github/PULL_REQUEST_TEMPLATE.md')) {
        File::remove($t . '/.github/PULL_REQUEST_TEMPLATE.md');
      }

      if (file_exists($t . '/.github/PULL_REQUEST_TEMPLATE.dist.md')) {
        rename($t . '/.github/PULL_REQUEST_TEMPLATE.dist.md', $t . '/.github/PULL_REQUEST_TEMPLATE.md');
      }
    }
    else {
      File::remove($t . '/.github');

      $this->removeRenovateGithubActionsManager($t . '/renovate.json');
    }
  }

  /**
   * Remove the GitHub Actions manager from the Renovate configuration.
   *
   * The manager only operates on files under '.github/workflows', which do
   * not exist for non-GitHub code providers.
   */
  protected function removeRenovateGithubActionsManager(string $path): void {
    if (!file_exists($path)) {
      return;
    }

    $content = file_get_contents($path);

    if ($content === FALSE) {
      return;
    }

    $data = json_decode($content, TRUE);

    if (!is_array($data)) {
      return;
    }

    $changed = FALSE;

    if (isset($data['enabledManagers']) && is_array($data['enabledManagers']) && in_array('github-actions', $data['enabledManagers'], TRUE)) {
      $data['enabledManagers'] = array_values(array_diff($data['enabledManagers'], ['github-actions']));
      $changed = TRUE;
    }

    if (isset($data['packageRules']) && is_array($data['packageRules'])) {
      $filtered = array_values(array_filter($data['packageRules'], fn(array $rule): bool => ($rule['matchManagers'] ?? NULL) !== ['github-actions']));

      if (count($filtered) !== count($data['packageRules'])) {
        $data['packageRules'] = $filtered;
        $changed = TRUE;
      }
    }

    if ($changed) {
      file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }
  }

}
