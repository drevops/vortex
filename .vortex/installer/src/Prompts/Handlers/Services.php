<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;
use Symfony\Component\Yaml\Yaml;

class Services extends AbstractHandler {

  const CLAMAV = 'clamav';

  const SOLR = 'solr';

  const REDIS = 'redis';

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
    catch (\Exception $e) {
      return NULL;
    }

    $services = [];

    if (isset($dc['clamav'])) {
      $services[] = self::CLAMAV;
    }

    if (isset($dc['solr'])) {
      $services[] = self::SOLR;
    }

    if (isset($dc['redis'])) {
      $services[] = self::REDIS;
    }

    ksort($services);

    return $services;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement this.
  }

}
