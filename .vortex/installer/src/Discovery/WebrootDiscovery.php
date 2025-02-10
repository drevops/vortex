<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class WebrootDiscovery extends AbstractDiscovery {

  public function discover() {
    $webroot = $this->getValueFromDstDotenv('WEBROOT');

    if (empty($webroot) && $this->isInstalled()) {
      // Try from composer.json.
      $extra = Util::getComposerJsonValue('extra', $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json');
      if (!empty($extra)) {
        $webroot = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $webroot;
  }

}
