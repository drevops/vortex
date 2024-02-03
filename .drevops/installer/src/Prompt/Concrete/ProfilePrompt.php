<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

/**
 * Profile prompt.
 */
class ProfilePrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'profile';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Profile';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'What is your custom profile machine name?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return 'standard';
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::PROVISION_OVERRIDE_DB);
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    Validator::notEmpty($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return Strings::toMachineName($value);
  }

}
