<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractChoicePrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;

class DatabaseDownloadSourcePrompt extends AbstractChoicePrompt {

  const CHOICE_URL = 'url';

  const CHOICE_FTP = 'ftp';

  const CHOICE_ACQUIA_BACKUP = 'acquia_backup';

  const CHOICE_DOCKER_REGISTRY = 'docker_registry';

  const CHOICE_NONE = 'none';

  const ID = 'database_download_source';

  /**
   * {@inheritdoc}
   */
  public static function title() {
    return 'Database download source';
  }

  /**
   * {@inheritdoc}
   */
  public static function question() {
    return 'Where does the database dump come from into every environment?';
  }

  /**
   * {@inheritdoc}
   */
  public static function choices() {
    // @todo: Review these as the values are not the actual config keys.
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
