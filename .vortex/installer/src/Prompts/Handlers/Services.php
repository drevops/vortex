<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Yaml;

class Services extends AbstractHandler {

  const CLAMAV = 'clamav';

  const REDIS = 'redis';

  const SOLR = 'solr';

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 220;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Services';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more services.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::CLAMAV => 'ClamAV',
      self::SOLR => 'Solr',
      self::REDIS => 'Redis',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return [self::CLAMAV, self::REDIS, self::SOLR];
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    try {
      $dc = Yaml::parseFile($this->destinationDir . '/docker-compose.yml');
    }
    catch (\Exception) {
      return NULL;
    }

    $services = [];

    if (isset($dc['services']['clamav'])) {
      $services[] = self::CLAMAV;
    }

    if (isset($dc['services']['solr'])) {
      $services[] = self::SOLR;
    }

    if (isset($dc['services']['redis'])) {
      $services[] = self::REDIS;
    }

    sort($services);

    return $services;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsArray();
    $t = $this->tmpDir;
    $w = $this->webroot;

    if (in_array(self::CLAMAV, $v)) {
      File::removeTokenAsync('!SERVICE_CLAMAV');
    }
    else {
      File::removeTokenAsync('SERVICE_CLAMAV');
    }

    if (in_array(self::SOLR, $v)) {
      File::removeTokenAsync('!SERVICE_SOLR');
    }
    else {
      File::removeTokenAsync('SERVICE_SOLR');
    }

    if (in_array(self::REDIS, $v)) {
      File::removeTokenAsync('!SERVICE_REDIS');
    }
    else {
      File::removeTokenAsync('SERVICE_REDIS');
    }

    if (!in_array(self::CLAMAV, $v)) {
      File::remove($t . '/.docker/config/clamav');
      File::remove($t . '/.docker/clamav.dockerfile');
      File::remove($t . '/' . $w . '/sites/default/includes/modules/settings.clamav.php');
      File::remove($t . '/tests/behat/features/clamav.feature');
      File::replaceContentInFile($t . '/docker-compose.yml', 'clamav:3310', '');
      JsonManipulator::updateFile($t . '/composer.json', function (JsonManipulator $cj): void {
        $cj->removeSubNode('require', 'drupal/clamav');
      });
    }

    if (!in_array(self::SOLR, $v)) {
      File::remove($t . '/.docker/config/solr');
      File::remove($t . '/.docker/solr.dockerfile');
      File::remove($t . '/tests/behat/features/search.feature');
      File::remove($t . '/scripts/provision-30-search-index.sh');
      JsonManipulator::updateFile($t . '/composer.json', function (JsonManipulator $cj): void {
        $cj->removeSubNode('require', 'drupal/solr');
        $cj->removeSubNode('require', 'drupal/search_api_solr');
      });
    }

    if (!in_array(self::REDIS, $v)) {
      File::remove($t . '/.docker/config/redis');
      File::remove($t . '/.docker/redis.dockerfile');
      File::remove($t . '/' . $w . '/sites/default/includes/modules/settings.redis.php');
      File::replaceContentInFile($t . '/docker-compose.yml', 'redis:6379', '');
      JsonManipulator::updateFile($t . '/composer.json', function (JsonManipulator $cj): void {
        $cj->removeSubNode('require', 'drupal/redis');
      });
      File::remove($t . '/tests/behat/features/redis.feature');
    }
  }

}
