<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;

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

}
