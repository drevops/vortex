<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;

class WebrootDiscovery extends AbstractDiscovery {

  public function discover() {
    $webroot = Env::getFromDstDotenv('WEBROOT');

    if (empty($webroot) && $this->isInstalled()) {
      // Try from composer.json.
      $extra = Composer::getJsonValue('extra', $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json');
      if (!empty($extra)) {
        $webroot = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $webroot;
  }

}
