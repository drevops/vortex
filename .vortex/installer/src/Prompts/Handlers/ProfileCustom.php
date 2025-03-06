<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class ProfileCustom extends Profile {

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    File::dirReplaceContent('your_site_profile', $this->response, $this->tmpDir);
  }

}
