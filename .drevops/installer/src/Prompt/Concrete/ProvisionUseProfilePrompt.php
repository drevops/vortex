<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;

/**
 *
 */
class ProvisionUseProfilePrompt extends AbstractConfirmationPrompt {

  final const ID = 'provision_use_profile';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Install from profile';
  }

  public static function question(): string {
    return 'Do you want to provision a site from profile (leave empty for using database)?';
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
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::PROVISION_USE_PROFILE);
  }

}
