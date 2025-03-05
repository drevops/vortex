<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;

class UseCustomProfile extends AbstractHandler {

  public function discover(): null|string|bool|iterable {
    return NULL;
  }

  public function process(): void {
    // Noop.
  }

}
