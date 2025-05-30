<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\File;

class MachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    File::replaceContentAsync([
      'your_site' => $v,
      'your-site' => Converter::kebab($v),
      'YourSite' => Converter::pascal($v),
    ]);

    File::renameInDir($t, 'your_site', $v);
  }

}
