<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\File;

class OrgMachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    File::replaceContentInDir($this->tmpDir, 'your_org', (string) $this->response);
    File::renameInDir($this->tmpDir, 'your_org', (string) $this->response);
  }

}
