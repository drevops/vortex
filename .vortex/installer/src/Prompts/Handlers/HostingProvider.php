<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class HostingProvider extends AbstractHandler {

  const NONE = 'none';

  const LAGOON = 'lagoon';

  const ACQUIA = 'acquia';

  const OTHER = 'other';

  public function discover(): null|string|bool|array {
    if ($this->discoverIsAcquia()) {
      return static::ACQUIA;
    }

    if ($this->discoverIsLagoon()) {
      return static::LAGOON;
    }

    return NULL;
  }

  protected function discoverIsAcquia() {
    if (is_readable($this->dstDir . '/hooks')) {
      return TRUE;
    }

    return Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir) == static::ACQUIA;
  }

  protected function discoverIsLagoon() {
    if (is_readable($this->dstDir . '/.lagoon.yml')) {
      return TRUE;
    }

    $value = Env::getFromDotenv('LAGOON_PROJECT', $this->dstDir);

    // Special case - only work with non-empty value as 'LAGOON_PROJECT'
    // may not exist in installed site's .env file.
    if (empty($value)) {
      return FALSE;
    }

    return TRUE;
  }

  public function process(): void {
    if ($this->response === static::ACQUIA) {
      $this->preserveAcquia();
      $this->removeLagoon();
    }
    elseif ($this->response === static::LAGOON) {
      $this->preserveLagoon();
      $this->removeAcquia();
    }
    else {
      $this->removeAcquia();
      $this->removeLagoon();
    }
  }

  protected function preserveAcquia(): void {
    File::removeTokenWithContent('!ACQUIA', $this->tmpDir);
  }

  protected function removeAcquia(): void {
    File::rmdirRecursive($this->tmpDir . '/hooks');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $this->tmpDir, $this->webroot));
    File::removeTokenWithContent('ACQUIA', $this->tmpDir);
  }

  protected function preserveLagoon(): void {
    File::removeTokenWithContent('!ACQUIA', $this->tmpDir);
  }

  protected function removeLagoon(): void {
    @unlink($this->tmpDir . '/drush/sites/lagoon.site.yml');
    @unlink($this->tmpDir . '/.lagoon.yml');
    @unlink($this->tmpDir . '/.github/workflows/close-pull-request.yml');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $this->tmpDir, $this->webroot));
    File::removeTokenWithContent('LAGOON', $this->tmpDir);
  }

}
