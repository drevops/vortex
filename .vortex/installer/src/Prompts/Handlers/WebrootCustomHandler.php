<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class WebrootCustomHandler extends AbstractHandler {

  const DEFAULT_WEBROOT = 'web';

  public function discover(): ?string {
    $value = Env::getFromDstDotenv('WEBROOT');

    if (empty($value) && $this->isInstalled()) {
      // Try from composer.json.
      $extra = Composer::getJsonValue('extra', $this->config->getDst() . DIRECTORY_SEPARATOR . 'composer.json');
      if (!empty($extra)) {
        $value = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $value;
  }

  public function process(): void {
    $value = $this->response ?? self::DEFAULT_WEBROOT;

    if ($value === self::DEFAULT_WEBROOT) {
      return;
    }

    File::dirReplaceContent(sprintf('%s/', self::DEFAULT_WEBROOT), $value . '/', $this->dir);
    File::dirReplaceContent(sprintf('%s\/', self::DEFAULT_WEBROOT), $value . '\/', $this->dir);
    File::dirReplaceContent(sprintf(': %s', self::DEFAULT_WEBROOT), ': ' . $value, $this->dir);
    File::dirReplaceContent(sprintf('=%s', self::DEFAULT_WEBROOT), '=' . $value, $this->dir);
    File::dirReplaceContent(sprintf('!%s', self::DEFAULT_WEBROOT), '!' . $value, $this->dir);
    File::dirReplaceContent(sprintf('/\/%s\//', self::DEFAULT_WEBROOT), '/' . $value . '/', $this->dir);
    File::dirReplaceContent(sprintf('/\'\/%s\'/', self::DEFAULT_WEBROOT), "'/" . $value . "'", $this->dir);
    rename($this->dir . DIRECTORY_SEPARATOR . self::DEFAULT_WEBROOT, $this->dir . DIRECTORY_SEPARATOR . $value);
  }
}
