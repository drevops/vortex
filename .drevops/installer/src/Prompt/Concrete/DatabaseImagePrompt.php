<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Formatter;
use DrevOps\Installer\Utils\Validator;

/**
 * Database image prompt.
 */
class DatabaseImagePrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'database_image';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Database image name';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return ' What is your database Docker image name and a tag (e.g. drevops/drevops-mariadb-drupal-data:latest)?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return 'drevops/mariadb-drupal-data:latest';
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DB_DOCKER_IMAGE);
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    Validator::dockerImageName($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return strtolower((string) $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatEmpty($value);
  }

}
