<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\Env;

class Domain extends AbstractHandler {

  public function discover(): null|string|bool|iterable {
    return Env::getFromDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN', $this->dstDir);
  }

  public function process():void  {
    // @todo Implement this.
  }


}
