<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ThemeHandler extends AbstractHandler {

  public function discover() {
    $webroot = $this->getAnswer(PromptFields::WEBROOT_CUSTOM);

    $name_from_env = NULL;
    if ($this->isInstalled()) {
      $name_from_env = Env::getFromDstDotenv('DRUPAL_THEME');
    }

    $file = $this->findThemeFile($this->config->getDstDir(), $webroot);

    if (empty($file)) {
      // If theme file was not found, but the theme is set in the .env file -
      // return the theme name from the .env file.
      return $name_from_env ?: NULL;
    }

    $name_from_info = str_replace(['.info.yml', '.info'], '', basename($file));

    // Check that this is a theme coming originally from the Vortex template.
    $dir = dirname($file);

    if (!$this->isVortexTheme($dir)) {
      // If the theme is not coming from the Vortex template - return the theme
      // name from the .env file.
      return $name_from_env ?: NULL;
    }

    if ($name_from_env) {
      if ($name_from_info !== $name_from_env) {
        // If the theme name from the .env file does not match the theme name
        // from the theme file - return the theme name from the info file
        // to update the .env file.
        return $name_from_info;
      }

      return $name_from_env;
    }

    return NULL;
  }

  protected function findThemeFile(string $dir, string $webroot): ?string {
    $locations = [
      sprintf('%s/%s/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/sites/all/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/sites/all/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/profiles/*/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/profiles/*/themes/custom/*/*.info.yml', $dir, $webroot),
      sprintf('%s/%s/profiles/custom/*/themes/custom/*/*.info', $dir, $webroot),
      sprintf('%s/%s/profiles/custom/*/themes/custom/*/*.info.yml', $dir, $webroot),
    ];

    return File::findMatchingPath($locations);
  }

  protected function isVortexTheme(string $dir): bool {
    $c1 = file_exists($dir . '/scss/_variables.scss');
    $c2 = file_exists($dir . '/Gruntfile.js');
    $c3 = file_exists($dir . '/package.json');
    $c4 = File::fileContains('build-dev', $dir . '/package.json');

    return $c1 && $c2 && $c3 && $c4;
  }

  public function process(array $responses, string $dir): void {
    $webroot = $this->getAnswer('webroot');
    $name = $this->getAnswer('theme');

    File::fileReplaceContent('/DRUPAL_THEME=.*/', 'DRUPAL_THEME=' . $name, $dir . '/.env');

    $file_dst = $this->findThemeFile($this->config->getDstDir(), $webroot);
    // Do not update the theme files if it is not a theme from the Vortex
    // template.
    if (!empty($file_dst) && !$this->isVortexTheme(dirname($file_dst))) {
      $file_tmpl = $this->findThemeFile($dir, $webroot);
      if (!empty($file_tmpl)) {
        File::rmdirRecursive(dirname($file_tmpl));
      }
    }
  }
}
