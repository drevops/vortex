<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "code_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CodeProvider extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value === 'github') {
      if (file_exists($context->directory . '/.github/PULL_REQUEST_TEMPLATE.md')) {
        File::remove($context->directory . '/.github/PULL_REQUEST_TEMPLATE.md');
      }

      if (file_exists($context->directory . '/.github/PULL_REQUEST_TEMPLATE.dist.md')) {
        rename($context->directory . '/.github/PULL_REQUEST_TEMPLATE.dist.md', $context->directory . '/.github/PULL_REQUEST_TEMPLATE.md');
      }
    }
    else {
      File::remove($context->directory . '/.github');

      $this->removeRenovateGithubActionsManager($context->directory . '/renovate.json');
    }
  }

  /**
   * Remove the GitHub Actions manager from the Renovate configuration.
   *
   * The manager only operates on files under '.github/workflows', which do
   * not exist for non-GitHub code providers.
   *
   * @param string $path
   *   The path to the Renovate configuration file.
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
      $data['enabledManagers'] = array_values(array_filter($data['enabledManagers'], fn($manager): bool => $manager !== 'github-actions'));
      $changed = TRUE;
    }

    if (isset($data['packageRules']) && is_array($data['packageRules'])) {
      $filtered = array_values(array_filter($data['packageRules'], fn($rule): bool => !is_array($rule) || ($rule['matchManagers'] ?? NULL) !== ['github-actions']));

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
