<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class ModulePrefix extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $locations = [
      $this->dstDir . sprintf('/%s/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/custom/*_base', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    return empty($path) ? NULL : str_replace('_base', '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $w = $this->webroot;

    File::replaceContentAsync([
      'ys_base' => $v . '_base',
      'ys_search' => $v . '_search',
      'YsBase' => Converter::pascal($v) . 'Base',
      'YsSearch' => Converter::pascal($v) . 'Search',
      'YSBASE' => Converter::cobol($v),
      'YSSEARCH' => Converter::cobol($v),
    ]);

    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys_base', $v . '_base');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys_search', $v . '_search');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'YsBase', Converter::pascal($v) . 'Base');
  }

}
