<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\Yaml;

class Services extends AbstractHandler {

  const CLAMAV = 'clamav';

  const REDIS = 'redis';

  const SOLR = 'solr';

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
    $options = [
      self::CLAMAV => 'ClamAV',
      self::SOLR => 'Solr',
      self::REDIS => 'Redis',
    ];

    // Hide Solr if the search custom module is not selected and Solr is not
    // already discovered in the existing codebase.
    if (isset($responses[CustomModules::id()])) {
      $custom_modules = $responses[CustomModules::id()];
      if (is_array($custom_modules) && !in_array(CustomModules::SEARCH, $custom_modules)) {
        // Check if Solr is discovered in the existing docker-compose.yml.
        $discovered = $this->discover();
        $solr_discovered = is_array($discovered) && in_array(self::SOLR, $discovered);
        if (!$solr_discovered) {
          unset($options[self::SOLR]);
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    $defaults = [self::CLAMAV, self::REDIS, self::SOLR];

    // Filter defaults to only include available options.
    $options = $this->options($responses);
    if (is_array($options)) {
      $defaults = array_values(array_intersect($defaults, array_keys($options)));
    }

    return $defaults;
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

    $services = [];

    if (isset($dc['services']['antivirus'])) {
      $services[] = self::CLAMAV;
    }

    if (isset($dc['services']['search'])) {
      $services[] = self::SOLR;
    }

    if (isset($dc['services']['cache'])) {
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
      File::removeTokenAsync('!SERVICE_ANTIVIRUS');
    }
    else {
      File::removeTokenAsync('SERVICE_ANTIVIRUS');
    }

    if (in_array(self::SOLR, $v)) {
      File::removeTokenAsync('!SERVICE_SEARCH');
    }
    else {
      File::removeTokenAsync('SERVICE_SEARCH');
    }

    if (in_array(self::REDIS, $v)) {
      File::removeTokenAsync('!SERVICE_CACHE');
    }
    else {
      File::removeTokenAsync('SERVICE_CACHE');
    }

    if (!in_array(self::CLAMAV, $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/clamav');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/clamav.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.clamav.php');
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/clamav.feature');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'docker-compose.yml', 'antivirus:3310', '');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/clamav":\s*"[^\"]+",?\n/', "\n");
    }

    if (!in_array(self::SOLR, $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/solr');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/solr.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.search_api.php');
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/search.feature');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/solr":\s*"[^\"]+",?\n/', "\n");
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/search_api_solr":\s*"[^\"]+",?\n/', "\n");
    }

    if (!in_array(self::REDIS, $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/redis');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/redis.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.redis.php');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'docker-compose.yml', 'cache:6379', '');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/redis":\s*"[^\"]+",?\n/', "\n");
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/redis.feature');
    }
  }

}
