<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class ModulePrefix extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Module prefix';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'We will use this name in custom modules';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. ms (for My Site)';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    if (isset($responses[MachineName::id()]) && !empty($responses[MachineName::id()])) {
      return Converter::abbreviation(Converter::machine($responses[MachineName::id()]), 4, ['_']);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $locations = [
      $this->dstDir . sprintf('/%s/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/custom/*_base', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    return empty($path) ? NULL : str_replace(['_base', '_core'], '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Converter::machine($v) !== $v ? 'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.' : NULL;
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
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $w = $this->webroot;

    File::replaceContentAsync([
      'ys_base' => $v . '_base',
      'ys-base' => Converter::kebab($v) . '-base',
      'ys_search' => $v . '_search',
      'ys-search' => Converter::kebab($v) . '-search',
      'YsBase' => Converter::pascal($v) . 'Base',
      'YsSearch' => Converter::pascal($v) . 'Search',
      'YSBASE' => Converter::cobol($v),
      'YSSEARCH' => Converter::cobol($v),
    ]);

    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys_base', $v . '_base');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys-base', Converter::kebab($v) . '-base');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys_search', $v . '_search');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'ys-search', Converter::kebab($v) . '-search');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'YsBase', Converter::pascal($v) . 'Base');
    File::renameInDir($t . sprintf('/%s/modules/custom', $w), 'YsSearch', Converter::pascal($v) . 'Search');
    File::renameInDir($t . sprintf('/%s/sites/default/includes', $w), 'ys_base', $v . '_base');
  }

}
