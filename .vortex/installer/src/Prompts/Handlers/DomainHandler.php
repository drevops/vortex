<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\Env;

class DomainHandler extends AbstractHandler {

  public function discover() {
    return Env::getFromDstDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN');
  }

  public function process(array $responses, string $dir):void  {
    // @todo Implement this.
  }


}
