<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Util;

class DomainHandler extends AbstractHandler {

  public function discover() {
    return Env::getFromDstDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN');
  }

}
