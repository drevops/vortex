<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "services" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Services extends AbstractFieldHandler implements OptionsInterface {

  const CLAMAV = 'clamav';

  const REDIS = 'redis';

  const SOLR = 'solr';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    $v = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    $t = $context->directory;
    $w = $webroot;

    if (in_array('clamav', $v)) {
      File::removeTokenAsync('!SERVICE_ANTIVIRUS');
    }
    else {
      File::removeTokenAsync('SERVICE_ANTIVIRUS');
    }

    if (in_array('solr', $v)) {
      File::removeTokenAsync('!SERVICE_SEARCH');
    }
    else {
      File::removeTokenAsync('SERVICE_SEARCH');
    }

    if (in_array('redis', $v)) {
      File::removeTokenAsync('!SERVICE_CACHE');
    }
    else {
      File::removeTokenAsync('SERVICE_CACHE');
    }

    if (!in_array('clamav', $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/clamav');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/clamav.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.clamav.php');
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/clamav.feature');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'docker-compose.yml', 'antivirus:3310', '');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/clamav":\s*"[^\"]+",?\n/', "\n");
    }

    if (!in_array('solr', $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/solr');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/solr.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.search_api.php');
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/search.feature');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/solr":\s*"[^\"]+",?\n/', "\n");
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/search_api_solr":\s*"[^\"]+",?\n/', "\n");
    }

    if (!in_array('redis', $v)) {
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/config/redis');
      File::remove($t . DIRECTORY_SEPARATOR . '.docker/redis.dockerfile');
      File::remove($t . DIRECTORY_SEPARATOR . $w . DIRECTORY_SEPARATOR . 'sites/default/includes/modules/settings.redis.php');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'docker-compose.yml', 'cache:6379', '');
      File::replaceContentInFile($t . DIRECTORY_SEPARATOR . 'composer.json', '/\s*"drupal\/redis":\s*"[^\"]+",?\n/', "\n");
      File::remove($t . DIRECTORY_SEPARATOR . 'tests/behat/features/redis.feature');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::CLAMAV => 'ClamAV',
      self::SOLR => 'Solr',
      self::REDIS => 'Redis',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'services';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Services';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::MultiSelect;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Optional Docker services to include.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return [self::CLAMAV, self::REDIS, self::SOLR];
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 200;
  }

}
