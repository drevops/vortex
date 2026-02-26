<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Yaml;

class Migration extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Use a second database for migrations?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Adds a second database service for Drupal migrations.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    try {
      $dc = Yaml::parseFile($this->dstDir . '/docker-compose.yml');
    }
    catch (\Exception) {
      return NULL;
    }

    return isset($dc['services']['database2']);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;
    $w = $this->webroot;

    if ($v) {
      File::removeTokenAsync('!MIGRATION');
    }
    else {
      File::removeTokenAsync('MIGRATION');
      File::remove($t . '/' . $w . '/sites/default/settings.migration.php');
      File::remove($t . '/scripts/custom/provision-20-migration.sh');
      File::remove($t . '/' . $w . '/modules/custom/ys_migrate');

      $cj = JsonManipulator::fromFile($t . '/composer.json');
      if ($cj instanceof JsonManipulator) {
        $cj->removeSubNode('require', 'drupal/migrate_plus');
        $cj->removeSubNode('require', 'drupal/migrate_tools');
        file_put_contents($t . '/composer.json', $cj->getContents());
      }
    }
  }

}
