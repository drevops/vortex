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
    File::renameInDir($t . sprintf('/%s/sites/default/includes', $w), 'ys_base', $v . '_base');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🧩 Module prefix';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'We will use this name for custom modules.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. ms (for My Site)';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequired(): bool {
    return true;
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
    return fn($v): ?string => Converter::machine($v) !== $v ? 'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.' : null;
  }

}
