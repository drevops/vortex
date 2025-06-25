<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;

class GithubToken extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('GITHUB_TOKEN', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement this.
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    if (!empty($this->discover())) {
      return 'GitHub access token is already set in the environment.';
    }
    return '🔑 GitHub access token (optional)';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return Env::get('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. ghp_1234567890';
  }

  /**
   * {@inheritdoc}
   */
  public function getTransform(): ?callable {
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : null;
  }

  /**
   * {@inheritdoc}
   */
  public function isConditional(): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(): ?callable {
    return fn(array $responses): bool => $responses[CodeProvider::id()] === CodeProvider::GITHUB;
  }

  /**
   * Helper method to check if token should be shown as note instead of input.
   */
  public function shouldShowAsNote(): bool {
    return !empty($this->discover());
  }

  /**
   * Get the informational note for GitHub token requirement.
   */
  public static function explanation(): string {
    return "<info>We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.</info>";
  }

  /**
   * Get the discovered value for display.
   */
  public function getDiscoveredValue(): string {
    return $this->discover() ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function resolved(array $responses): null|string|bool|array {
    $discovered = $this->discover();
    if (!empty($discovered)) {
      return $discovered;
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses): ?string {
    if (!empty($this->discover())) {
      return 'GitHub access token is already set in the environment.';
    }
    return null;
  }

}
