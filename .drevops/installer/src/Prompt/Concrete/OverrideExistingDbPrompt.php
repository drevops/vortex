<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;

/**
 * Override existing database prompt.
 */
class OverrideExistingDbPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'override_existing_db';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Override existing database';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to override existing database in the environment?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::PROVISION_OVERRIDE_DB);
  }

}
