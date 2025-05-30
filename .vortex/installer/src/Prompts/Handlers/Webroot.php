<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Webroot extends AbstractHandler {

  const WEB = 'web';

  const DOCROOT = 'docroot';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
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
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $webroot = self::WEB;

    if ($v === $webroot) {
      return;
    }

    File::replaceContentAsync([
      sprintf('%s/', $webroot) => $v . '/',
      sprintf('%s\/', $webroot) => $v . '\/',
      sprintf(': %s', $webroot) => ': ' . $v,
      sprintf('=%s', $webroot) => '=' . $v,
      sprintf('!%s', $webroot) => '!' . $v,
      sprintf('/\/%s\//', $webroot) => '/' . $v . '/',
      sprintf('/\'\/%s\'/', $webroot) => "'/" . $v . "'",
    ]);

    rename($t . DIRECTORY_SEPARATOR . $webroot, $t . DIRECTORY_SEPARATOR . $v);
  }

}
