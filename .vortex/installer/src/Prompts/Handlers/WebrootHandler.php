<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class WebrootHandler extends AbstractHandler {

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

  public function process(array $responses, string $dir): void {
    $new_name = $this->getAnswer('webroot', 'web');

    if ($new_name !== 'web') {
      File::dirReplaceContent('web/', $new_name . '/', $dir);
      File::dirReplaceContent('web\/', $new_name . '\/', $dir);
      File::dirReplaceContent(': web', ': ' . $new_name, $dir);
      File::dirReplaceContent('=web', '=' . $new_name, $dir);
      File::dirReplaceContent('!web', '!' . $new_name, $dir);
      File::dirReplaceContent('/\/web\//', '/' . $new_name . '/', $dir);
      File::dirReplaceContent('/\'\/web\'/', "'/" . $new_name . "'", $dir);
      rename($dir . DIRECTORY_SEPARATOR . 'web', $dir . DIRECTORY_SEPARATOR . $new_name);
    }
  }
}
