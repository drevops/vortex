<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class HostingProvider extends AbstractHandler {

  const NONE = 'none';

  const LAGOON = 'lagoon';

  const ACQUIA = 'acquia';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (is_readable($this->dstDir . '/hooks') || Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir) === DatabaseDownloadSource::ACQUIA) {
      return self::ACQUIA;
    }

    if (is_readable($this->dstDir . '/.lagoon.yml')) {
      return self::LAGOON;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if ($v === static::ACQUIA) {
      File::removeTokenAsync('!HOSTING_ACQUIA');
      $this->removeLagoon();
    }
    elseif ($v === static::LAGOON) {
      File::removeTokenAsync('!HOSTING_LAGOON');
      $this->removeAcquia();
    }
    else {
      $this->removeAcquia();
      $this->removeLagoon();
      File::removeTokenAsync('HOSTING');
    }
  }

  protected function removeAcquia(): void {
    File::rmdir($this->tmpDir . '/hooks');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $this->tmpDir, $this->webroot));

    File::removeTokenAsync('HOSTING_ACQUIA');
  }

  protected function removeLagoon(): void {
    @unlink($this->tmpDir . '/drush/sites/lagoon.site.yml');
    @unlink($this->tmpDir . '/.lagoon.yml');
    @unlink($this->tmpDir . '/.github/workflows/close-pull-request.yml');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $this->tmpDir, $this->webroot));

    File::removeTokenAsync('HOSTING_LAGOON');
  }

}
