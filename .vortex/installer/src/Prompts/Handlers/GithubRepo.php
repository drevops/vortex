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
  public function label(): string {
    return '🏷️ What is your GitHub project name?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(): ?string {
    return 'We will use this name to create new or find an existing repository.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(): ?string {
    return 'E.g. myorg/myproject';
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
  public function validate(): ?callable {
    return fn(string $v): ?string => !empty($v) && !Validator::githubProject($v) ? 'Please enter a valid project name in the format "myorg/myproject"' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isConditional(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(): ?callable {
    return fn(array $responses): bool => !empty($responses[GithubToken::id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAlter(mixed &$default, array $responses): void {
    if (
      isset($responses[OrgMachineName::id()]) &&
      isset($responses[MachineName::id()]) &&
      !empty($responses[OrgMachineName::id()]) &&
      !empty($responses[MachineName::id()])
    ) {
      $default = $responses[OrgMachineName::id()] . '/' . $responses[MachineName::id()];
    }
  }

}
