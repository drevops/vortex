<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class HostingProviderHandler extends AbstractHandler {

  public function discover() {
    if ($this->discoverIsAcquia()) {
      return 'acquia';
    }

    if ($this->discoverIsLagoon()) {
      return 'lagoon';
    }

    return NULL;
  }

  public function process(array $responses, string $dir): void {
    $provider = $responses[PromptFields::HOSTING_PROVIDER];

    if ($provider === 'acquia') {
      $this->preserveAcquia($dir);
      $this->removeLagoon($dir);
    }
    elseif ($provider === 'lagoon') {
      $this->preserveLagoon($dir);
      $this->removeAcquia($dir);
    }
    else {
      $this->removeAcquia($dir);
      $this->removeLagoon($dir);
    }
  }

  protected function discoverIsAcquia() {
    if (is_readable($this->config->getDstDir() . '/hooks')) {
      return TRUE;
    }

    $value = Env::getFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');

    if (is_null($value)) {
      return FALSE;
    }

    return $value == 'acquia';
  }

  protected function discoverIsLagoon() {
    if (is_readable($this->config->getDstDir() . '/.lagoon.yml')) {
      return TRUE;
    }

    if ($this->getAnswer('deploy_type') === 'lagoon') {
      return TRUE;
    }

    $value = Env::getFromDstDotenv('LAGOON_PROJECT');

    // Special case - only work with non-empty value as 'LAGOON_PROJECT'
    // may not exist in installed site's .env file.
    if (empty($value)) {
      return FALSE;
    }

    return TRUE;
  }

  protected function preserveAcquia(string $dir): void {
    File::removeTokenWithContent('!ACQUIA', $dir);
  }

  protected function removeAcquia(string $dir): void {
    File::rmdirRecursive($dir . '/hooks');
    $webroot = $this->getAnswer('webroot');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $dir, $webroot));
    File::removeTokenWithContent('ACQUIA', $dir);
  }

  protected function preserveLagoon(string $dir): void {
    File::removeTokenWithContent('!ACQUIA', $dir);
  }

  protected function removeLagoon(string $dir): void {
    @unlink($dir . '/drush/sites/lagoon.site.yml');
    @unlink($dir . '/.lagoon.yml');
    @unlink($dir . '/.github/workflows/close-pull-request.yml');
    $webroot = $this->getAnswer('webroot');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $dir, $webroot));
    File::removeTokenWithContent('LAGOON', $dir);
  }
}
