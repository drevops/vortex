<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractChoicePrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;

/**
 * Database download source prompt.
 */
class DatabaseDownloadSourcePrompt extends AbstractChoicePrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'database_download_source';

  final const CHOICE_URL = 'url';

  final const CHOICE_FTP = 'ftp';

  final const CHOICE_ACQUIA_BACKUP = 'acquia_backup';

  final const CHOICE_DOCKER_REGISTRY = 'docker_registry';

  final const CHOICE_NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Database download source';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Where does the database dump come from into every environment?';
  }

  /**
   * {@inheritdoc}
   */
  public static function choices(): array {
    // @todo Review these as the values are not the actual config keys.
    return [
      self::CHOICE_URL,
      self::CHOICE_FTP,
      self::CHOICE_ACQUIA_BACKUP,
      self::CHOICE_DOCKER_REGISTRY,
      self::CHOICE_NONE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DB_DOWNLOAD_SOURCE);
  }

}
