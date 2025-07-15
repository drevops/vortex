<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;

class GithubToken extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    if (!empty($this->discover())) {
      return 'GitHub access token is already set in the environment.';
    }
    return '🔑 GitHub access token (optional)';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return Env::get('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. ghp_1234567890';
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return $responses[CodeProvider::id()] === CodeProvider::GITHUB;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('GITHUB_TOKEN', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedValue(array $responses): null|string|bool|array {
    $discovered = $this->discover();
    if (!empty($discovered)) {
      return $discovered;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses): ?string {
    return 'GitHub access token is already set in the environment.';
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement this.
  }

  /**
   * Get the informational note for GitHub token requirement.
   */
  public static function description(array $responses): ?string {
    return "We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.";
  }

  /**
   * Get the discovered value for display.
   */
  public function getDiscoveredValue(): string {
    return '';
  }

}
