<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class WebrootCustom extends AbstractHandler {

  const WEB = 'web';

  const DOCROOT = 'docroot';

  const DEFAULT = 'web';

  /**
   * {@inheritdoc}
   */
  public function discover(): ?string {
    $value = Env::getFromDotenv('WEBROOT', $this->dstDir);

    if (empty($value)) {
      // Try from composer.json.
      $extra = Composer::getJsonValue('extra', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');
      if (!empty($extra)) {
        $value = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $value = $this->response ?? self::DEFAULT;

    if ($value === self::DEFAULT) {
      return;
    }

    File::dirReplaceContent(sprintf('%s/', self::DEFAULT), $value . '/', $this->tmpDir);
    File::dirReplaceContent(sprintf('%s\/', self::DEFAULT), $value . '\/', $this->tmpDir);
    File::dirReplaceContent(sprintf(': %s', self::DEFAULT), ': ' . $value, $this->tmpDir);
    File::dirReplaceContent(sprintf('=%s', self::DEFAULT), '=' . $value, $this->tmpDir);
    File::dirReplaceContent(sprintf('!%s', self::DEFAULT), '!' . $value, $this->tmpDir);
    File::dirReplaceContent(sprintf('/\/%s\//', self::DEFAULT), '/' . $value . '/', $this->tmpDir);
    File::dirReplaceContent(sprintf('/\'\/%s\'/', self::DEFAULT), "'/" . $value . "'", $this->tmpDir);
    rename($this->tmpDir . DIRECTORY_SEPARATOR . self::DEFAULT, $this->tmpDir . DIRECTORY_SEPARATOR . $value);
  }

}
