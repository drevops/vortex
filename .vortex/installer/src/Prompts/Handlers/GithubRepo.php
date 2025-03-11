<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Git;

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

}
