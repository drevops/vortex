<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\Validator;

class GithubRepo extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!file_exists($this->dstDir . DIRECTORY_SEPARATOR . '.git')) {
      return NULL;
    }

    $repo = new Git($this->dstDir);
    $remotes = $repo->listRemotes();

    if (empty($remotes)) {
      return NULL;
    }

    $remote = $remotes['origin'] ?? reset($remotes);

    return Git::extractOwnerRepo($remote);
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
    return '🏷️ What is your GitHub project name?';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'We will use this name to create new or find an existing repository.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. myorg/myproject';
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
    return fn(string $v): ?string => !empty($v) && !Validator::githubProject($v) ? 'Please enter a valid project name in the format "myorg/myproject"' : null;
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
  public function getCondition(): ?callable {
    return fn(array $responses): bool => !empty($responses[GithubToken::id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultForContext(array $responses): mixed {
    // Generate default from OrgMachineName and MachineName if available
    if (isset($responses[OrgMachineName::id()]) && isset($responses[MachineName::id()]) 
        && !empty($responses[OrgMachineName::id()]) && !empty($responses[MachineName::id()])) {
      return $responses[OrgMachineName::id()] . '/' . $responses[MachineName::id()];
    }
    
    return $this->getDefault();
  }

}
