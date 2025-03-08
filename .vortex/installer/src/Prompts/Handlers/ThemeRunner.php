<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ThemeRunner extends AbstractHandler {

  const GRUNT = 'grunt';
  const GULP = 'gulp';
  const WEBPACK = 'webpack';
  const NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
   // @todo Implement this.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement this.
  }

}
